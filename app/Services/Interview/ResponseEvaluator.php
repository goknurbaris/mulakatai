<?php

namespace App\Services\Interview;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;

class ResponseEvaluator
{
    /**
     * @param  array<string, mixed>  $question
     * @return array<string, mixed>
     */
    public function evaluate(array $question, string $answer): array
    {
        if (! $this->aiScoringEnabled()) {
            return $this->evaluateDeterministically($question, $answer);
        }

        $aiResult = $this->evaluateWithAi($question, $answer);

        if ($aiResult !== null) {
            return $aiResult;
        }

        return $this->evaluateDeterministically($question, $answer);
    }

    /**
     * @param  array<string, mixed>  $question
     * @return array<string, mixed>|null
     */
    private function evaluateWithAi(array $question, string $answer): ?array
    {
        $baseUrl = (string) config('services.interview_ai.base_url');
        $endpoint = (string) config('services.interview_ai.chat_endpoint');
        $model = (string) config('services.interview_ai.model');
        $apiKey = (string) config('services.interview_ai.api_key');
        $timeout = (int) config('services.interview_ai.timeout', 25);
        $retries = (int) config('services.interview_ai.retries', 2);
        $retrySleepMs = (int) config('services.interview_ai.retry_sleep_ms', 350);

        $payload = [
            'model' => $model,
            'temperature' => 0.2,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<'PROMPT'
You are an interview evaluator.
Evaluate the user's answer and return ONLY JSON with this exact shape:
{
  "score": 0-100 integer,
  "strengths": ["short bullet", "..."],
  "gaps": ["short bullet", "..."],
  "ideal_answer": "concise ideal answer",
  "next_question_difficulty": "easy|medium|hard",
  "breakdown": {
    "accuracy": 0-100 integer,
    "depth": 0-100 integer,
    "clarity": 0-100 integer,
    "problem_solving": 0-100 integer
  }
}
Scoring weights:
- accuracy: 40%
- depth: 25%
- clarity: 20%
- problem_solving: 15%
Do not include markdown or explanations outside JSON.
PROMPT,
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'question' => $question['question'] ?? '',
                        'topic' => $question['topic'] ?? '',
                        'difficulty' => $question['difficulty'] ?? '',
                        'expected_keywords' => $question['keywords'] ?? [],
                        'ideal_reference' => $question['ideal_answer'] ?? '',
                        'candidate_answer' => $answer,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ],
        ];

        try {
            $response = Http::baseUrl($baseUrl)
                ->withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout($timeout)
                ->retry($retries, $retrySleepMs)
                ->post($endpoint, $payload)
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('AI scoring request failed, using deterministic fallback.', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        $rawContent = $response->json('choices.0.message.content');

        if (! is_string($rawContent) || trim($rawContent) === '') {
            Log::warning('AI scoring returned empty content, using deterministic fallback.');

            return null;
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($rawContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            Log::warning('AI scoring returned invalid JSON, using deterministic fallback.', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! is_array($decoded)) {
            Log::warning('AI scoring JSON payload is not an object, using deterministic fallback.');

            return null;
        }

        $normalized = $this->normalizeAiResult($decoded, (string) ($question['ideal_answer'] ?? ''));

        if ($normalized === null) {
            Log::warning('AI scoring payload failed schema validation, using deterministic fallback.');
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $question
     * @return array<string, mixed>
     */
    private function evaluateDeterministically(array $question, string $answer): array
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
            'source' => 'deterministic_fallback',
        ];
    }

    private function aiScoringEnabled(): bool
    {
        return (bool) config('services.interview_ai.enabled')
            && filled(config('services.interview_ai.api_key'))
            && filled(config('services.interview_ai.base_url'))
            && filled(config('services.interview_ai.model'))
            && filled(config('services.interview_ai.chat_endpoint'));
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<string, mixed>|null
     */
    private function normalizeAiResult(array $decoded, string $idealAnswerFallback): ?array
    {
        if (! isset($decoded['score'], $decoded['strengths'], $decoded['gaps'], $decoded['next_question_difficulty'])) {
            return null;
        }

        if (! is_array($decoded['strengths']) || ! is_array($decoded['gaps'])) {
            return null;
        }

        $score = (int) $decoded['score'];
        $difficulty = (string) $decoded['next_question_difficulty'];

        if (! in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            return null;
        }

        $strengths = array_values(array_filter(
            array_map(fn (mixed $item): ?string => is_string($item) && trim($item) !== '' ? trim($item) : null, $decoded['strengths'])
        ));
        $gaps = array_values(array_filter(
            array_map(fn (mixed $item): ?string => is_string($item) && trim($item) !== '' ? trim($item) : null, $decoded['gaps'])
        ));

        if ($strengths === [] || $gaps === []) {
            return null;
        }

        $breakdown = $decoded['breakdown'] ?? [];
        if (! is_array($breakdown)) {
            $breakdown = [];
        }

        $accuracy = (int) ($breakdown['accuracy'] ?? $score);
        $depth = (int) ($breakdown['depth'] ?? $score);
        $clarity = (int) ($breakdown['clarity'] ?? $score);
        $problemSolving = (int) ($breakdown['problem_solving'] ?? $score);

        return [
            'score' => max(0, min(100, $score)),
            'strengths' => $strengths,
            'gaps' => $gaps,
            'ideal_answer' => is_string($decoded['ideal_answer'] ?? null) && trim((string) $decoded['ideal_answer']) !== ''
                ? trim((string) $decoded['ideal_answer'])
                : $idealAnswerFallback,
            'next_question_difficulty' => $difficulty,
            'breakdown' => [
                'accuracy' => max(0, min(100, $accuracy)),
                'depth' => max(0, min(100, $depth)),
                'clarity' => max(0, min(100, $clarity)),
                'problem_solving' => max(0, min(100, $problemSolving)),
            ],
            'source' => 'ai',
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
