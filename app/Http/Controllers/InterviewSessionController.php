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
use Symfony\Component\HttpFoundation\Response;

class InterviewSessionController extends Controller
{
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

        $interviewSession->update([
            'current_question_index' => $nextIndex,
        ]);

        return redirect()->route('interviews.show', $interviewSession);
    }

    public function result(InterviewSession $interviewSession): Response|View
    {
        if ($interviewSession->status !== 'completed') {
            return redirect()->route('interviews.show', $interviewSession);
        }

        $answers = $interviewSession->answers()->orderBy('question_index')->get();

        return view('interviews.result', [
            'session' => $interviewSession,
            'answers' => $answers,
            'summary' => $interviewSession->summary ?? ['strengths' => [], 'gaps' => []],
            'learningPlan' => $interviewSession->learningPlan?->plan_json ?? [],
        ]);
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
}
