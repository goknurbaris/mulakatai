<?php

namespace App\Http\Controllers;

use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Services\Interview\LearningPlanBuilder;
use App\Services\Interview\QuestionBank;
use App\Services\Interview\ResponseEvaluator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InterviewSessionController extends Controller
{
    public function history(Request $request, QuestionBank $questionBank): View
    {
        $validated = $request->validate([
            'role' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['in_progress', 'completed'])],
            'level' => ['nullable', Rule::in(['junior', 'mid'])],
        ]);

        $roleOptions = $questionBank->roleOptions();
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

        if (filled($validated['role'] ?? null) && array_key_exists($validated['role'], $roleOptions)) {
            $query->where('role', $validated['role']);
        }

        if (filled($validated['status'] ?? null)) {
            $query->where('status', $validated['status']);
        }

        if (filled($validated['level'] ?? null)) {
            $query->where('level', $validated['level']);
        }

        return view('interviews.history', [
            'sessions' => $query->paginate(6)->withQueryString(),
            'roleOptions' => $roleOptions,
            'selectedRole' => $validated['role'] ?? '',
            'selectedStatus' => $validated['status'] ?? '',
            'selectedLevel' => $validated['level'] ?? '',
            'stats' => [
                'total_sessions' => $totalSessions,
                'completed_sessions' => $completedCount,
                'in_progress_sessions' => $inProgressCount,
                'average_completed_score' => round($averageCompletedScore, 1),
                'completion_rate' => $completionRate,
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
        ]);

        $questions = $questionBank->forRoleLevel($validated['role'], $validated['level'], $focusValidated['focus_topic']);

        abort_if($questions === [], 422, 'No interview questions available for this role.');

        $session = InterviewSession::create([
            'user_id' => Auth::id(),
            'role' => $validated['role'],
            'level' => $validated['level'],
            'focus_topic' => $focusValidated['focus_topic'],
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

        if ($question === null) {
            return redirect()->route('interviews.result', $interviewSession);
        }

        return view('interviews.question', [
            'session' => $interviewSession,
            'question' => $question,
            'questionNumber' => $currentIndex + 1,
            'totalQuestions' => count($questions),
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

        $evaluation = $evaluator->evaluate($question, $validated['answer']);

        InterviewAnswer::updateOrCreate(
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

        return view('interviews.result', [
            'session' => $interviewSession,
            'roleLabel' => $questionBank->roleLabel($interviewSession->role),
            'answers' => $answers,
            'summary' => $interviewSession->summary ?? ['strengths' => [], 'gaps' => []],
            'learningPlan' => $interviewSession->learningPlan?->plan_json ?? [],
        ]);
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
