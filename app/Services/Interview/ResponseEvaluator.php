<?php

namespace App\Services\Interview;

use Illuminate\Support\Str;

class ResponseEvaluator
{
    /**
     * @param  array<string, mixed>  $question
     * @return array<string, mixed>
     */
    public function evaluate(array $question, string $answer): array
    {
        $normalized = Str::of($answer)->lower()->toString();
        $words = str_word_count($answer);

        $accuracy = $this->accuracyScore($normalized, $question['keywords'] ?? []);
        $depth = $this->depthScore($words, $normalized);
        $clarity = $this->clarityScore($answer);
        $approach = $this->approachScore($normalized);

        $finalScore = (int) round(
            ($accuracy * 0.40) +
            ($depth * 0.25) +
            ($clarity * 0.20) +
            ($approach * 0.15)
        );

        $strengths = [];
        $gaps = [];

        if ($accuracy >= 75) {
            $strengths[] = 'Good technical correctness';
        } else {
            $gaps[] = 'Improve conceptual accuracy';
        }

        if ($depth >= 70) {
            $strengths[] = 'Provided enough depth and context';
        } else {
            $gaps[] = 'Add deeper explanation and examples';
        }

        if ($clarity >= 70) {
            $strengths[] = 'Clear communication';
        } else {
            $gaps[] = 'Structure your answer more clearly';
        }

        if ($approach >= 70) {
            $strengths[] = 'Reasoned problem-solving approach';
        } else {
            $gaps[] = 'Explain trade-offs and step-by-step reasoning';
        }

        return [
            'score' => max(0, min(100, $finalScore)),
            'strengths' => array_values(array_unique($strengths)),
            'gaps' => array_values(array_unique($gaps)),
            'ideal_answer' => $question['ideal_answer'] ?? '',
            'next_question_difficulty' => $this->nextDifficulty($finalScore),
            'breakdown' => [
                'accuracy' => $accuracy,
                'depth' => $depth,
                'clarity' => $clarity,
                'problem_solving' => $approach,
            ],
        ];
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function accuracyScore(string $normalizedAnswer, array $keywords): int
    {
        if ($keywords === []) {
            return 60;
        }

        $hits = 0;
        foreach ($keywords as $keyword) {
            if (Str::contains($normalizedAnswer, Str::lower($keyword))) {
                $hits++;
            }
        }

        $ratio = $hits / count($keywords);

        return (int) round(35 + ($ratio * 65));
    }

    private function depthScore(int $wordCount, string $normalizedAnswer): int
    {
        $base = match (true) {
            $wordCount >= 120 => 90,
            $wordCount >= 80 => 78,
            $wordCount >= 45 => 65,
            $wordCount >= 20 => 50,
            default => 35,
        };

        if (Str::contains($normalizedAnswer, ['example', 'for instance', 'trade-off'])) {
            $base += 8;
        }

        return min(100, $base);
    }

    private function clarityScore(string $answer): int
    {
        $sentences = max(1, preg_match_all('/[.!?]+/', $answer));
        $words = max(1, str_word_count($answer));
        $avgWordsPerSentence = $words / $sentences;

        $score = 70;
        if ($avgWordsPerSentence > 25) {
            $score -= 15;
        }
        if ($words < 20) {
            $score -= 20;
        }
        if (str_contains($answer, "\n")) {
            $score += 10;
        }

        return max(20, min(100, $score));
    }

    private function approachScore(string $normalizedAnswer): int
    {
        $signals = ['first', 'then', 'because', 'if', 'risk', 'trade-off', 'alternative'];
        $hits = 0;

        foreach ($signals as $signal) {
            if (Str::contains($normalizedAnswer, $signal)) {
                $hits++;
            }
        }

        return min(100, 45 + ($hits * 10));
    }

    private function nextDifficulty(int $score): string
    {
        return match (true) {
            $score >= 80 => 'hard',
            $score >= 60 => 'medium',
            default => 'easy',
        };
    }
}
