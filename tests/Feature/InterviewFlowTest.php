<?php

namespace Tests\Feature;

use App\Jobs\ReevaluateInterviewAnswerWithAi;
use App\Models\InterviewSession;
use App\Models\LearningPlan;
use App\Models\InterviewAnswer;
use App\Models\User;
use App\Services\Interview\ResponseEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InterviewFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_complete_a_text_interview_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('interviews.store'), [
            'role' => 'frontend',
            'level' => 'junior',
            'focus_topic' => 'React State',
        ])->assertRedirect();

        /** @var InterviewSession $session */
        $session = InterviewSession::query()->firstOrFail();
        $this->assertSame($user->id, $session->user_id);
        $this->assertNotNull($session->started_at);

        for ($index = 0; $index < 10; $index++) {
            $this->get(route('interviews.show', $session))
                ->assertOk();

            $response = $this->post(route('interviews.answer', $session), [
                'answer' => 'First I identify the core requirement, then I explain trade-offs and give an example solution for this question.',
            ]);

            if ($index < 9) {
                $response->assertRedirect(route('interviews.show', $session));
            } else {
                $response->assertRedirect(route('interviews.result', $session));
            }

            $session->refresh();
        }

        $session->refresh();

        $this->assertSame('completed', $session->status);
        $this->assertNotNull($session->total_score);
        $this->assertNotNull($session->completed_at);
        $this->assertCount(10, $session->answers);
        $this->assertNotNull($session->learningPlan);

        $this->get(route('interviews.result', $session))
            ->assertOk()
            ->assertSee('Interview Summary');
    }

    public function test_answer_must_have_minimum_length(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('interviews.store'), [
            'role' => 'backend',
            'level' => 'junior',
            'focus_topic' => 'Eloquent',
        ]);

        $session = InterviewSession::query()->firstOrFail();

        $this->post(route('interviews.answer', $session), [
            'answer' => 'too short',
        ])->assertSessionHasErrors('answer');
    }

    public function test_user_can_start_interview_with_fullstack_field(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('interviews.store'), [
            'role' => 'fullstack',
            'level' => 'mid',
            'focus_topic' => 'API Design',
            'interview_objective' => 'Prepare for a product company fullstack interview',
        ])->assertRedirect();

        $session = InterviewSession::query()->firstOrFail();

        $this->assertSame('fullstack', $session->role);
        $this->assertSame('API Design', $session->focus_topic);
        $this->assertSame('Prepare for a product company fullstack interview', $session->interview_objective);
        $this->assertCount(10, $session->questions_snapshot);
        $this->assertSame('API Design', $session->questions_snapshot[0]['topic']);
    }

    public function test_next_question_is_reordered_by_adaptive_difficulty_signal(): void
    {
        $this->actingAs(User::factory()->create());
        $this->app->instance(ResponseEvaluator::class, new class extends ResponseEvaluator
        {
            public function evaluate(array $question, string $answer, bool $allowAi = true): array
            {
                return [
                    'score' => 91,
                    'strengths' => ['Strong answer'],
                    'gaps' => ['Minor detail'],
                    'ideal_answer' => (string) ($question['ideal_answer'] ?? ''),
                    'next_question_difficulty' => 'hard',
                    'breakdown' => [
                        'accuracy' => 92,
                        'depth' => 90,
                        'clarity' => 90,
                        'problem_solving' => 92,
                    ],
                    'source' => 'deterministic_fallback',
                ];
            }
        });

        $this->post(route('interviews.store'), [
            'role' => 'backend',
            'level' => 'mid',
            'focus_topic' => 'Eloquent',
        ])->assertRedirect();

        $session = InterviewSession::query()->firstOrFail();

        $this->post(route('interviews.answer', $session), [
            'answer' => 'I would split orchestration from business logic, keep validation explicit, and discuss trade-offs clearly.',
        ])->assertRedirect(route('interviews.show', $session));

        $session->refresh();

        $this->assertSame('hard', $session->questions_snapshot[1]['difficulty']);
        $this->assertSame('Architecture', $session->questions_snapshot[1]['topic']);
    }

    public function test_guest_is_redirected_to_login_for_app_routes(): void
    {
        $this->get(route('interviews.start'))->assertRedirect(route('login'));
        $this->get(route('interviews.history'))->assertRedirect(route('login'));
    }

    public function test_question_page_displays_adaptive_target_hint_after_first_answer(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('interviews.store'), [
            'role' => 'frontend',
            'level' => 'junior',
            'focus_topic' => 'React State',
        ])->assertRedirect();

        $session = InterviewSession::query()->firstOrFail();

        $this->post(route('interviews.answer', $session), [
            'answer' => 'First I identify state boundaries, then I explain trade-offs and give an example with reducers.',
        ])->assertRedirect(route('interviews.show', $session));

        $this->get(route('interviews.show', $session))
            ->assertOk()
            ->assertSee('Adaptive target:');
    }

    public function test_junior_session_starts_with_deterministic_scoring_before_threshold(): void
    {
        $this->actingAs(User::factory()->create());
        config([
            'services.interview_ai.enabled' => true,
            'services.interview_ai.base_url' => 'https://example.test',
            'services.interview_ai.chat_endpoint' => '/v1/chat/completions',
            'services.interview_ai.model' => 'test-model',
            'services.interview_ai.api_key' => 'test-key',
        ]);
        Queue::fake();

        $this->app->instance(ResponseEvaluator::class, new class extends ResponseEvaluator
        {
            public function evaluate(array $question, string $answer, bool $allowAi = true): array
            {
                return [
                    'score' => 75,
                    'strengths' => ['Strong answer'],
                    'gaps' => ['Minor detail'],
                    'ideal_answer' => (string) ($question['ideal_answer'] ?? ''),
                    'next_question_difficulty' => 'medium',
                    'breakdown' => [
                        'accuracy' => 76,
                        'depth' => 74,
                        'clarity' => 75,
                        'problem_solving' => 75,
                    ],
                    'source' => $allowAi ? 'ai' : 'deterministic_fallback',
                ];
            }
        });

        $this->post(route('interviews.store'), [
            'role' => 'frontend',
            'level' => 'junior',
            'focus_topic' => 'React State',
        ])->assertRedirect();

        $session = InterviewSession::query()->firstOrFail();

        $this->post(route('interviews.answer', $session), [
            'answer' => 'First I define boundaries, then I explain trade-offs and provide examples to support the decision.',
        ])->assertRedirect(route('interviews.show', $session));

        $firstAnswer = InterviewAnswer::query()
            ->where('interview_session_id', $session->id)
            ->where('question_index', 0)
            ->firstOrFail();

        $this->assertSame('deterministic_fallback', $firstAnswer->feedback_json['source'] ?? null);
        Queue::assertNotPushed(ReevaluateInterviewAnswerWithAi::class);
    }

    public function test_mid_level_session_queues_ai_reevaluation_when_enabled(): void
    {
        $this->actingAs(User::factory()->create());
        config([
            'services.interview_ai.enabled' => true,
            'services.interview_ai.base_url' => 'https://example.test',
            'services.interview_ai.chat_endpoint' => '/v1/chat/completions',
            'services.interview_ai.model' => 'test-model',
            'services.interview_ai.api_key' => 'test-key',
        ]);
        Queue::fake();

        $this->app->instance(ResponseEvaluator::class, new class extends ResponseEvaluator
        {
            public function evaluate(array $question, string $answer, bool $allowAi = true): array
            {
                return [
                    'score' => 85,
                    'strengths' => ['Strong answer'],
                    'gaps' => ['Minor detail'],
                    'ideal_answer' => (string) ($question['ideal_answer'] ?? ''),
                    'next_question_difficulty' => 'hard',
                    'breakdown' => [
                        'accuracy' => 86,
                        'depth' => 84,
                        'clarity' => 85,
                        'problem_solving' => 85,
                    ],
                    'source' => $allowAi ? 'ai' : 'deterministic_fallback',
                ];
            }
        });

        $this->post(route('interviews.store'), [
            'role' => 'backend',
            'level' => 'mid',
            'focus_topic' => 'Eloquent',
        ])->assertRedirect();

        $session = InterviewSession::query()->firstOrFail();

        $this->post(route('interviews.answer', $session), [
            'answer' => 'I separate business logic from controllers, validate strongly, and evaluate trade-offs with alternatives.',
        ])->assertRedirect(route('interviews.show', $session));

        $firstAnswer = InterviewAnswer::query()
            ->where('interview_session_id', $session->id)
            ->where('question_index', 0)
            ->firstOrFail();

        $this->assertSame('deterministic_fallback', $firstAnswer->feedback_json['source'] ?? null);
        Queue::assertPushed(ReevaluateInterviewAnswerWithAi::class);
    }

    public function test_user_can_see_history_and_resume_in_progress_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $session = InterviewSession::create([
            'user_id' => $user->id,
            'role' => 'backend',
            'level' => 'mid',
            'focus_topic' => 'Caching',
            'status' => 'in_progress',
            'current_question_index' => 2,
            'questions_snapshot' => [
                ['topic' => 'Caching', 'difficulty' => 'medium', 'question' => 'Q1'],
            ],
        ]);

        $this->get(route('interviews.history'))
            ->assertOk()
            ->assertSee('In progress');

        $this->get(route('interviews.resume', $session))
            ->assertRedirect(route('interviews.show', $session));
    }

    public function test_user_cannot_access_another_users_session(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $session = InterviewSession::create([
            'user_id' => $owner->id,
            'role' => 'frontend',
            'level' => 'junior',
            'focus_topic' => 'React State',
            'status' => 'in_progress',
            'current_question_index' => 0,
            'questions_snapshot' => [
                ['topic' => 'React State', 'difficulty' => 'easy', 'question' => 'Q1'],
            ],
        ]);

        $this->actingAs($other)
            ->get(route('interviews.show', $session))
            ->assertForbidden();
    }

    public function test_user_can_toggle_learning_plan_item_completion(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $session = InterviewSession::create([
            'user_id' => $user->id,
            'role' => 'backend',
            'level' => 'mid',
            'focus_topic' => 'Caching',
            'status' => 'completed',
            'current_question_index' => 10,
            'total_score' => 78,
            'questions_snapshot' => [
                ['topic' => 'Caching', 'difficulty' => 'medium', 'question' => 'Q1'],
            ],
        ]);

        LearningPlan::create([
            'interview_session_id' => $session->id,
            'plan_json' => [
                ['day' => 'Day 1', 'focus' => 'Caching', 'task' => 'Task 1', 'completed' => false],
                ['day' => 'Day 2', 'focus' => 'Queues', 'task' => 'Task 2', 'completed' => false],
            ],
        ]);

        $this->patch(route('interviews.learning-plan.toggle', [$session, 0]))
            ->assertRedirect(route('interviews.result', $session));

        $session->refresh();
        $this->assertTrue((bool) ($session->learningPlan?->plan_json[0]['completed'] ?? false));

        $this->get(route('interviews.result', $session))
            ->assertOk()
            ->assertSee('Progress:')
            ->assertSee('Mark as pending');
    }

    public function test_user_can_export_completed_interview_result_as_json(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $session = InterviewSession::create([
            'user_id' => $user->id,
            'role' => 'backend',
            'level' => 'mid',
            'focus_topic' => 'Caching',
            'interview_objective' => 'Prepare for platform role',
            'status' => 'completed',
            'current_question_index' => 10,
            'total_score' => 82.5,
            'summary' => ['strengths' => ['Clear communication'], 'gaps' => ['More depth']],
            'questions_snapshot' => [
                ['topic' => 'Caching', 'difficulty' => 'medium', 'question' => 'Q1'],
            ],
        ]);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_index' => 0,
            'topic' => 'Caching',
            'question_text' => 'How would you cache frequently read data?',
            'user_answer' => 'I would use TTL and invalidate on updates.',
            'ai_score' => 80,
            'feedback_json' => [
                'strengths' => ['Clear communication'],
                'gaps' => ['More depth'],
                'ideal_answer' => 'Use TTL and event-driven invalidation.',
                'next_question_difficulty' => 'medium',
                'breakdown' => ['accuracy' => 80, 'depth' => 75, 'clarity' => 85, 'problem_solving' => 80],
            ],
        ]);

        LearningPlan::create([
            'interview_session_id' => $session->id,
            'plan_json' => [
                ['day' => 'Day 1', 'focus' => 'Caching', 'task' => 'Read cache docs', 'completed' => false],
            ],
        ]);

        $response = $this->get(route('interviews.export', $session));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/json');
        $this->assertStringContainsString('Prepare for platform role', $response->streamedContent());
        $this->assertStringContainsString('Clear communication', $response->streamedContent());
    }

    public function test_history_supports_filters_and_pagination(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach (range(1, 8) as $index) {
            InterviewSession::create([
                'user_id' => $user->id,
                'role' => 'backend',
                'level' => $index <= 4 ? 'junior' : 'mid',
                'focus_topic' => $index <= 4 ? 'Caching' : 'Queues',
                'status' => $index <= 7 ? 'completed' : 'in_progress',
                'current_question_index' => 0,
                'questions_snapshot' => [
                    ['topic' => 'Caching', 'difficulty' => 'medium', 'question' => 'Q1'],
                ],
            ]);
        }

        $response = $this->get(route('interviews.history', [
            'role' => 'backend',
            'status' => 'completed',
            'level' => 'junior',
            'topic' => 'Caching',
        ]));

        $response->assertOk()
            ->assertSee('Apply filters')
            ->assertSee('All levels')
            ->assertSee('Completion Rate')
            ->assertSee('Score Trend (7 Days)')
            ->assertSee('Role Performance')
            ->assertSee('Top Focus Topics')
            ->assertSee('Completed')
            ->assertDontSee('/resume')
            ->assertDontSee('Next');
    }

    public function test_user_can_delete_own_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $session = InterviewSession::create([
            'user_id' => $user->id,
            'role' => 'frontend',
            'level' => 'junior',
            'focus_topic' => 'React State',
            'status' => 'in_progress',
            'current_question_index' => 0,
            'questions_snapshot' => [
                ['topic' => 'React State', 'difficulty' => 'easy', 'question' => 'Q1'],
            ],
        ]);

        $this->delete(route('interviews.destroy', $session))
            ->assertRedirect(route('interviews.history'));

        $this->assertDatabaseMissing('interview_sessions', ['id' => $session->id]);
    }

    public function test_user_can_retake_completed_interview_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $session = InterviewSession::create([
            'user_id' => $user->id,
            'role' => 'backend',
            'level' => 'mid',
            'focus_topic' => 'Eloquent',
            'interview_objective' => 'Prepare for interviews',
            'status' => 'completed',
            'current_question_index' => 10,
            'total_score' => 85,
            'questions_snapshot' => [
                ['topic' => 'Eloquent', 'difficulty' => 'medium', 'question' => 'Q1'],
            ],
        ]);

        $response = $this->post(route('interviews.retake', $session));

        $newSession = InterviewSession::query()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->where('id', '!=', $session->id)
            ->latest('id')
            ->firstOrFail();

        $response->assertRedirect(route('interviews.show', $newSession));
        $this->assertSame('backend', $newSession->role);
        $this->assertSame('mid', $newSession->level);
        $this->assertSame('Eloquent', $newSession->focus_topic);
        $this->assertSame('Prepare for interviews', $newSession->interview_objective);
        $this->assertSame(0, $newSession->current_question_index);
        $this->assertCount(10, $newSession->questions_snapshot);
    }

    public function test_user_cannot_delete_another_users_session(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $session = InterviewSession::create([
            'user_id' => $owner->id,
            'role' => 'backend',
            'level' => 'mid',
            'focus_topic' => 'Caching',
            'status' => 'in_progress',
            'current_question_index' => 0,
            'questions_snapshot' => [
                ['topic' => 'Caching', 'difficulty' => 'medium', 'question' => 'Q1'],
            ],
        ]);

        $this->actingAs($other)
            ->delete(route('interviews.destroy', $session))
            ->assertForbidden();

        $this->assertDatabaseHas('interview_sessions', ['id' => $session->id]);
    }

    public function test_user_cannot_toggle_another_users_learning_plan_item(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $session = InterviewSession::create([
            'user_id' => $owner->id,
            'role' => 'frontend',
            'level' => 'junior',
            'focus_topic' => 'React State',
            'status' => 'completed',
            'current_question_index' => 10,
            'total_score' => 81,
            'questions_snapshot' => [
                ['topic' => 'React State', 'difficulty' => 'easy', 'question' => 'Q1'],
            ],
        ]);

        LearningPlan::create([
            'interview_session_id' => $session->id,
            'plan_json' => [
                ['day' => 'Day 1', 'focus' => 'React State', 'task' => 'Task 1', 'completed' => false],
            ],
        ]);

        $this->actingAs($other)
            ->patch(route('interviews.learning-plan.toggle', [$session, 0]))
            ->assertForbidden();
    }
}
