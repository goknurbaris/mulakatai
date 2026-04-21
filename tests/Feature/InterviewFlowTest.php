<?php

namespace Tests\Feature;

use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ])->assertRedirect();

        $session = InterviewSession::query()->firstOrFail();

        $this->assertSame('fullstack', $session->role);
        $this->assertSame('API Design', $session->focus_topic);
        $this->assertCount(10, $session->questions_snapshot);
        $this->assertSame('API Design', $session->questions_snapshot[0]['topic']);
    }

    public function test_guest_is_redirected_to_login_for_app_routes(): void
    {
        $this->get(route('interviews.start'))->assertRedirect(route('login'));
        $this->get(route('interviews.history'))->assertRedirect(route('login'));
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

    public function test_history_supports_filters_and_pagination(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach (range(1, 8) as $index) {
            InterviewSession::create([
                'user_id' => $user->id,
                'role' => 'backend',
                'level' => 'mid',
                'focus_topic' => 'Caching',
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
        ]));

        $response->assertOk()
            ->assertSee('Apply filters')
            ->assertSee('Completed')
            ->assertDontSee('/resume')
            ->assertSee('Next');
    }
}
