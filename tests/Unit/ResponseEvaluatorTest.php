<?php

namespace Tests\Unit;

use App\Services\Interview\ResponseEvaluator;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResponseEvaluatorTest extends TestCase
{
    public function test_it_uses_ai_response_when_payload_is_valid(): void
    {
        config([
            'services.interview_ai.enabled' => true,
            'services.interview_ai.base_url' => 'https://example.test',
            'services.interview_ai.chat_endpoint' => '/v1/chat/completions',
            'services.interview_ai.model' => 'test-model',
            'services.interview_ai.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://example.test/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'score' => 86,
                                'strengths' => ['Clear structure', 'Correct fundamentals'],
                                'gaps' => ['Give more concrete examples'],
                                'ideal_answer' => 'An ideal answer with crisp trade-offs.',
                                'next_question_difficulty' => 'hard',
                                'breakdown' => [
                                    'accuracy' => 84,
                                    'depth' => 80,
                                    'clarity' => 92,
                                    'problem_solving' => 85,
                                ],
                            ], JSON_THROW_ON_ERROR),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = (new ResponseEvaluator())->evaluate($this->question(), $this->answer());

        $this->assertSame(86, $result['score']);
        $this->assertSame('hard', $result['next_question_difficulty']);
        $this->assertSame('ai', $result['source']);
        $this->assertSame('An ideal answer with crisp trade-offs.', $result['ideal_answer']);
    }

    public function test_it_falls_back_when_ai_returns_invalid_payload(): void
    {
        config([
            'services.interview_ai.enabled' => true,
            'services.interview_ai.base_url' => 'https://example.test',
            'services.interview_ai.chat_endpoint' => '/v1/chat/completions',
            'services.interview_ai.model' => 'test-model',
            'services.interview_ai.api_key' => 'test-key',
        ]);

        Http::fake([
            'https://example.test/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '{"score": "oops"}',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = (new ResponseEvaluator())->evaluate($this->question(), $this->answer());

        $this->assertSame('deterministic_fallback', $result['source']);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('strengths', $result);
        $this->assertArrayHasKey('gaps', $result);
    }

    /**
     * @return array<string, mixed>
     */
    private function question(): array
    {
        return [
            'topic' => 'React State',
            'difficulty' => 'medium',
            'question' => 'When do you use useState vs useReducer?',
            'keywords' => ['complex state', 'state transitions', 'reducer'],
            'ideal_answer' => 'Use useState for simple local state and useReducer for complex transitions.',
        ];
    }

    private function answer(): string
    {
        return 'First I would check the complexity of state transitions. Then I would use useReducer for predictable updates and useState for simple local values.';
    }
}
