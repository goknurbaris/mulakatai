<?php

namespace App\Http\Controllers;

use App\Jobs\ReevaluateInterviewAnswerWithAi;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Services\Interview\LearningPlanBuilder;
use App\Services\Interview\QuestionBank;
use App\Services\Interview\ResponseEvaluator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

class InterviewSessionController extends Controller
{
    public function history(Request $request, QuestionBank $questionBank): View
    {
        $validated = $request->validate([
            'role' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['in_progress', 'completed'])],
            'level' => ['nullable', Rule::in(['junior', 'mid'])],
            'topic' => ['nullable', 'string'],
        ]);

        $roleOptions = $questionBank->roleOptions();
        $topicOptions = collect($questionBank->topicOptionsByRole())
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->all();
        $sessionsQuery = Auth::user()->interviewSessions();
        $query = (clone $sessionsQuery)->latest();
        $completedSessions = (clone $sessionsQuery)->where('status', 'completed');
        $totalSessions = (clone $sessionsQuery)->count();
        $completedCount = (clone $completedSessions)->count();
        $inProgressCount = max(0, $totalSessions - $completedCount);
        $averageCompletedScore = (float) ((clone $completedSessions)->avg('total_score') ?? 0);
        $completionRate = $totalSessions > 0
            ? (int) round(($completedCount / $totalSessions) * 100)
            : 0;
        $completedSessionsForAnalytics = (clone $completedSessions)
            ->whereNotNull('total_score')
            ->orderBy('created_at')
            ->get(['role', 'focus_topic', 'total_score', 'created_at']);
        $trend30 = $this->buildDailyTrend($completedSessionsForAnalytics->all(), 30);
        $trend7 = array_slice($trend30, -7);
        $rolePerformance = $completedSessionsForAnalytics
            ->groupBy('role')
            ->map(function ($group, $role) use ($roleOptions): array {
                return [
                    'key' => $role,
                    'label' => $roleOptions[$role] ?? ucfirst((string) $role),
                    'count' => $group->count(),
                    'avg_score' => round((float) $group->avg('total_score'), 1),
                ];
            })
            ->sortByDesc('avg_score')
            ->values()
            ->all();
        $topicPerformance = $completedSessionsForAnalytics
            ->filter(static fn ($session): bool => filled($session->focus_topic))
            ->groupBy('focus_topic')
            ->map(function ($group, $topic): array {
                return [
                    'topic' => (string) $topic,
                    'count' => $group->count(),
                    'avg_score' => round((float) $group->avg('total_score'), 1),
                ];
            })
            ->sortByDesc('count')
            ->take(6)
            ->values()
            ->all();

        if (filled($validated['role'] ?? null) && array_key_exists($validated['role'], $roleOptions)) {
            $query->where('role', $validated['role']);
        }

        if (filled($validated['status'] ?? null)) {
            $query->where('status', $validated['status']);
        }

        if (filled($validated['level'] ?? null)) {
            $query->where('level', $validated['level']);
        }

        if (filled($validated['topic'] ?? null)) {
            $query->where('focus_topic', $validated['topic']);
        }

        return view('interviews.history', [
            'sessions' => $query->paginate(6)->withQueryString(),
            'roleOptions' => $roleOptions,
            'topicOptions' => $topicOptions,
            'selectedRole' => $validated['role'] ?? '',
            'selectedStatus' => $validated['status'] ?? '',
            'selectedLevel' => $validated['level'] ?? '',
            'selectedTopic' => $validated['topic'] ?? '',
            'stats' => [
                'total_sessions' => $totalSessions,
                'completed_sessions' => $completedCount,
                'in_progress_sessions' => $inProgressCount,
                'average_completed_score' => round($averageCompletedScore, 1),
                'completion_rate' => $completionRate,
            ],
            'analytics' => [
                'trend_7_days' => $trend7,
                'trend_30_days' => $trend30,
                'role_performance' => $rolePerformance,
                'topic_performance' => $topicPerformance,
            ],
        ]);
    }

    public function start(QuestionBank $questionBank): View
    {
        return view('interviews.start', [
            'roleOptions' => $questionBank->roleOptions(),
            'topicOptionsByRole' => $questionBank->topicOptionsByRole(),
        ]);
    }

    public function store(Request $request, QuestionBank $questionBank): Response
    {
        $roleKeys = array_keys($questionBank->roleOptions());

        $validated = $request->validate([
            'role' => ['required', Rule::in($roleKeys)],
            'level' => ['required', 'in:junior,mid'],
        ]);

        $focusValidated = $request->validate([
            'focus_topic' => ['required', 'string', Rule::in($questionBank->topicOptionsForRole($validated['role']))],
            'interview_objective' => ['nullable', 'string', 'max:120'],
        ]);

        $questions = $questionBank->forRoleLevel($validated['role'], $validated['level'], $focusValidated['focus_topic']);

        abort_if($questions === [], 422, 'No interview questions available for this role.');

        $session = InterviewSession::create([
            'user_id' => Auth::id(),
            'role' => $validated['role'],
            'level' => $validated['level'],
            'focus_topic' => $focusValidated['focus_topic'],
            'interview_objective' => $focusValidated['interview_objective'] ?? null,
            'questions_snapshot' => $questions,
            'current_question_index' => 0,
            'status' => 'in_progress',
        ]);

        return redirect()->route('interviews.show', $session);
    }

    public function show(InterviewSession $interviewSession): Response|View
    {
        $this->assertOwnership($interviewSession);

        if ($interviewSession->status === 'completed') {
            return redirect()->route('interviews.result', $interviewSession);
        }

        $currentIndex = $interviewSession->current_question_index;
        $questions = $interviewSession->questions_snapshot;
        $question = $questions[$currentIndex] ?? null;
        $nextDifficultyHint = null;

        if ($currentIndex > 0) {
            $previousAnswer = $interviewSession->answers()
                ->where('question_index', $currentIndex - 1)
                ->first();
            $difficultyHint = $previousAnswer?->feedback_json['next_question_difficulty'] ?? null;

            if (is_string($difficultyHint) && in_array($difficultyHint, ['easy', 'medium', 'hard'], true)) {
                $nextDifficultyHint = strtoupper($difficultyHint);
            }
        }

        if ($question === null) {
            return redirect()->route('interviews.result', $interviewSession);
        }

        return view('interviews.question', [
            'session' => $interviewSession,
            'question' => $question,
            'questionNumber' => $currentIndex + 1,
            'totalQuestions' => count($questions),
            'nextDifficultyHint' => $nextDifficultyHint,
        ]);
    }

    public function submitAnswer(
        Request $request,
        InterviewSession $interviewSession,
        ResponseEvaluator $evaluator,
        LearningPlanBuilder $learningPlanBuilder,
    ): Response {
        $this->assertOwnership($interviewSession);

        if ($interviewSession->status === 'completed') {
            return redirect()->route('interviews.result', $interviewSession);
        }

        $validated = $request->validate([
            'answer' => ['required', 'string', 'min:10'],
        ]);

        $currentIndex = $interviewSession->current_question_index;
        $questions = $interviewSession->questions_snapshot;
        $question = $questions[$currentIndex] ?? null;

        abort_if($question === null, 422, 'Interview question could not be loaded.');

        $shouldQueueAiEvaluation = $this->shouldQueueAiEvaluation($interviewSession, $question);
        $evaluation = $evaluator->evaluate(
            $question,
            $validated['answer'],
            false
        );

        $answer = InterviewAnswer::updateOrCreate(
            [
                'interview_session_id' => $interviewSession->id,
                'question_index' => $currentIndex,
            ],
            [
                'topic' => $question['topic'],
                'question_text' => $question['question'],
                'user_answer' => $validated['answer'],
                'ai_score' => $evaluation['score'],
                'feedback_json' => $evaluation,
            ]
        );

        if ($shouldQueueAiEvaluation) {
            ReevaluateInterviewAnswerWithAi::dispatch(
                (int) $answer->id,
                $question,
                $validated['answer']
            );
        }

        $nextIndex = $currentIndex + 1;
        $totalQuestions = count($questions);

        if ($nextIndex >= $totalQuestions) {
            $averageScore = (float) ($interviewSession->answers()->avg('ai_score') ?? 0);
            $summary = $this->buildSummary($interviewSession);

            $interviewSession->update([
                'current_question_index' => $nextIndex,
                'status' => 'completed',
                'total_score' => round($averageScore, 2),
                'summary' => $summary,
            ]);

            $interviewSession->learningPlan()->updateOrCreate(
                ['interview_session_id' => $interviewSession->id],
                ['plan_json' => $learningPlanBuilder->build($summary['gaps'])]
            );

            return redirect()->route('interviews.result', $interviewSession);
        }

        $questions = $this->applyAdaptiveDifficulty(
            $questions,
            $nextIndex,
            (string) ($evaluation['next_question_difficulty'] ?? '')
        );

        $interviewSession->update([
            'current_question_index' => $nextIndex,
            'questions_snapshot' => $questions,
        ]);

        return redirect()->route('interviews.show', $interviewSession);
    }

    public function result(InterviewSession $interviewSession, QuestionBank $questionBank): Response|View
    {
        $this->assertOwnership($interviewSession);

        if ($interviewSession->status !== 'completed') {
            return redirect()->route('interviews.show', $interviewSession);
        }

        $answers = $interviewSession->answers()->orderBy('question_index')->get();
        $learningPlan = $this->normalizedLearningPlan($interviewSession->learningPlan?->plan_json ?? []);

        return view('interviews.result', [
            'session' => $interviewSession,
            'roleLabel' => $questionBank->roleLabel($interviewSession->role),
            'answers' => $answers,
            'summary' => $interviewSession->summary ?? ['strengths' => [], 'gaps' => []],
            'learningPlan' => $learningPlan,
        ]);
    }

    public function exportResult(InterviewSession $interviewSession, QuestionBank $questionBank): Response|StreamedResponse
    {
        $this->assertOwnership($interviewSession);

        if ($interviewSession->status !== 'completed') {
            return redirect()->route('interviews.show', $interviewSession);
        }

        $payload = [
            'session_id' => $interviewSession->id,
            'role' => $questionBank->roleLabel($interviewSession->role),
            'level' => $interviewSession->level,
            'focus_topic' => $interviewSession->focus_topic,
            'interview_objective' => $interviewSession->interview_objective,
            'total_score' => $interviewSession->total_score,
            'summary' => $interviewSession->summary ?? ['strengths' => [], 'gaps' => []],
            'answers' => $interviewSession->answers()
                ->orderBy('question_index')
                ->get(['question_index', 'topic', 'question_text', 'user_answer', 'ai_score', 'feedback_json'])
                ->toArray(),
            'learning_plan' => $this->normalizedLearningPlan($interviewSession->learningPlan?->plan_json ?? []),
            'exported_at' => now()->toIso8601String(),
        ];

        $filename = sprintf('interview-result-%d.json', $interviewSession->id);

        return response()->streamDownload(
            static function () use ($payload): void {
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            },
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    public function resume(InterviewSession $interviewSession): Response
    {
        $this->assertOwnership($interviewSession);

        if ($interviewSession->status === 'completed') {
            return redirect()->route('interviews.result', $interviewSession);
        }

        return redirect()->route('interviews.show', $interviewSession);
    }

    public function destroy(InterviewSession $interviewSession): Response
    {
        $this->assertOwnership($interviewSession);

        $interviewSession->delete();

        return redirect()->route('interviews.history');
    }

    public function toggleLearningPlanItem(InterviewSession $interviewSession, int $dayIndex): Response
    {
        $this->assertOwnership($interviewSession);

        if ($interviewSession->status !== 'completed') {
            return redirect()->route('interviews.show', $interviewSession);
        }

        $learningPlan = $interviewSession->learningPlan;
        abort_if($learningPlan === null, 404);

        $plan = $this->normalizedLearningPlan($learningPlan->plan_json ?? []);
        abort_if(! isset($plan[$dayIndex]), 404);

        $plan[$dayIndex]['completed'] = ! $plan[$dayIndex]['completed'];
        $learningPlan->update(['plan_json' => array_values($plan)]);

        return redirect()->route('interviews.result', $interviewSession);
    }

    /**
     * @return array{strengths: array<int, string>, gaps: array<int, string>}
     */
    private function buildSummary(InterviewSession $interviewSession): array
    {
        $answers = $interviewSession->answers()->get();
        $strengthCounter = [];
        $gapCounter = [];

        foreach ($answers as $answer) {
            foreach (($answer->feedback_json['strengths'] ?? []) as $strength) {
                $strengthCounter[$strength] = ($strengthCounter[$strength] ?? 0) + 1;
            }

            foreach (($answer->feedback_json['gaps'] ?? []) as $gap) {
                $gapCounter[$gap] = ($gapCounter[$gap] ?? 0) + 1;
            }
        }

        arsort($strengthCounter);
        arsort($gapCounter);

        $strengths = array_slice(array_keys($strengthCounter), 0, 3);
        $gaps = array_slice(array_keys($gapCounter), 0, 3);

        return [
            'strengths' => $strengths !== [] ? $strengths : ['Steady participation throughout the interview'],
            'gaps' => $gaps !== [] ? $gaps : ['Push toward more concise and structured answers'],
        ];
    }

    private function assertOwnership(InterviewSession $interviewSession): void
    {
        abort_unless((int) $interviewSession->user_id === (int) Auth::id(), 403);
    }

    /**
     * @param  array<string, mixed>  $question
     */
    private function shouldQueueAiEvaluation(InterviewSession $interviewSession, array $question): bool
    {
        if (! $this->aiScoringConfigured()) {
            return false;
        }

        $forcedLevels = $this->parseCsvConfig((string) config('services.interview_ai.staged_force_levels', 'mid'));
        if (in_array(strtolower($interviewSession->level), $forcedLevels, true)) {
            return true;
        }

        $forcedDifficulties = $this->parseCsvConfig((string) config('services.interview_ai.staged_force_difficulties', 'hard'));
        $questionDifficulty = is_string($question['difficulty'] ?? null)
            ? strtolower((string) $question['difficulty'])
            : '';
        if ($questionDifficulty !== '' && in_array($questionDifficulty, $forcedDifficulties, true)) {
            return true;
        }

        $minAnswerCount = max(1, (int) config('services.interview_ai.staged_min_answer_count', 3));
        $recentScores = $interviewSession->answers()
            ->orderByDesc('question_index')
            ->limit($minAnswerCount)
            ->pluck('ai_score');

        if ($recentScores->count() < $minAnswerCount) {
            return false;
        }

        $minScore = (float) config('services.interview_ai.staged_min_score', 70);
        $recentAverage = (float) $recentScores->avg();

        return $recentAverage >= $minScore;
    }

    private function aiScoringConfigured(): bool
    {
        return (bool) config('services.interview_ai.enabled')
            && filled(config('services.interview_ai.api_key'))
            && filled(config('services.interview_ai.base_url'))
            && filled(config('services.interview_ai.model'))
            && filled(config('services.interview_ai.chat_endpoint'));
    }

    /**
     * @return array<int, string>
     */
    private function parseCsvConfig(string $value): array
    {
        return array_values(array_filter(
            array_map(static fn (string $item): string => strtolower(trim($item)), explode(',', $value)),
            static fn (string $item): bool => $item !== ''
        ));
    }

    /**
     * @param  array<int, mixed>  $plan
     * @return array<int, array{day: string, focus: string, task: string, completed: bool}>
     */
    private function normalizedLearningPlan(array $plan): array
    {
        $normalized = [];

        foreach ($plan as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $day = is_string($item['day'] ?? null) && trim($item['day']) !== ''
                ? trim($item['day'])
                : 'Day '.($index + 1);
            $focus = is_string($item['focus'] ?? null) ? trim($item['focus']) : '';
            $task = is_string($item['task'] ?? null) ? trim($item['task']) : '';

            if ($focus === '' || $task === '') {
                continue;
            }

            $normalized[] = [
                'day' => $day,
                'focus' => $focus,
                'task' => $task,
                'completed' => (bool) ($item['completed'] ?? false),
            ];
        }

        return array_values($normalized);
    }

    /**
     * @param  array<int, object>  $sessions
     * @return array<int, array<string, int|float|string|null>>
     */
    private function buildDailyTrend(array $sessions, int $days): array
    {
        $startDate = now()->startOfDay()->subDays($days - 1);
        $dailyBuckets = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $dateKey = $startDate->copy()->addDays($offset)->format('Y-m-d');
            $dailyBuckets[$dateKey] = ['sum' => 0.0, 'count' => 0];
        }

        foreach ($sessions as $session) {
            if (! $session->created_at instanceof Carbon) {
                continue;
            }

            $dateKey = $session->created_at->format('Y-m-d');
            if (! array_key_exists($dateKey, $dailyBuckets)) {
                continue;
            }

            $dailyBuckets[$dateKey]['sum'] += (float) $session->total_score;
            $dailyBuckets[$dateKey]['count']++;
        }

        $trend = [];
        foreach ($dailyBuckets as $dateKey => $bucket) {
            $trend[] = [
                'date' => $dateKey,
                'label' => Carbon::createFromFormat('Y-m-d', $dateKey)->format('m/d'),
                'avg_score' => $bucket['count'] > 0 ? round($bucket['sum'] / $bucket['count'], 1) : null,
                'session_count' => $bucket['count'],
            ];
        }

        return $trend;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    private function applyAdaptiveDifficulty(array $questions, int $startIndex, string $targetDifficulty): array
    {
        if (! in_array($targetDifficulty, ['easy', 'medium', 'hard'], true)) {
            return $questions;
        }

        $completedQuestions = array_slice($questions, 0, $startIndex);
        $remainingQuestions = array_values(array_slice($questions, $startIndex));

        if ($remainingQuestions === []) {
            return $questions;
        }

        $difficultyRank = ['easy' => 0, 'medium' => 1, 'hard' => 2];
        $targetRank = $difficultyRank[$targetDifficulty];

        $scoredQuestions = array_map(
            static function (array $question, int $position) use ($difficultyRank, $targetRank): array {
                $difficulty = is_string($question['difficulty'] ?? null)
                    ? strtolower((string) $question['difficulty'])
                    : 'medium';
                $rank = $difficultyRank[$difficulty] ?? 1;

                return [
                    'question' => $question,
                    'distance' => abs($rank - $targetRank),
                    'position' => $position,
                ];
            },
            $remainingQuestions,
            array_keys($remainingQuestions)
        );

        usort(
            $scoredQuestions,
            static fn (array $left, array $right): int => [$left['distance'], $left['position']] <=> [$right['distance'], $right['position']]
        );

        $reorderedRemaining = array_map(
            static fn (array $item): array => $item['question'],
            $scoredQuestions
        );

        return array_values(array_merge($completedQuestions, $reorderedRemaining));
    }
}
