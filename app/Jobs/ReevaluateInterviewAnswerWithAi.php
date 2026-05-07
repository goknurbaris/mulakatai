<?php

namespace App\Jobs;

use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Services\Interview\ResponseEvaluator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ReevaluateInterviewAnswerWithAi implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /**
     * @param  array<string, mixed>  $question
     */
    public function __construct(
        public int $interviewAnswerId,
        public array $question,
        public string $userAnswer,
    ) {
    }

    public function handle(ResponseEvaluator $evaluator): void
    {
        $answer = InterviewAnswer::query()
            ->with('interviewSession')
            ->find($this->interviewAnswerId);

        if ($answer === null) {
            return;
        }

        $evaluation = $evaluator->evaluate($this->question, $this->userAnswer, true);

        if (($evaluation['source'] ?? null) !== 'ai') {
            return;
        }

        $answer->update([
            'ai_score' => $evaluation['score'],
            'feedback_json' => $evaluation,
        ]);

        $session = $answer->interviewSession;
        if ($session === null || $session->status !== 'completed') {
            return;
        }

        $summary = $this->buildSummary($session);

        $session->update([
            'total_score' => round((float) ($session->answers()->avg('ai_score') ?? 0), 2),
            'summary' => $summary,
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
