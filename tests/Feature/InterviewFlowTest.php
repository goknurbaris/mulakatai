<?php

namespace Tests\Feature;

use App\Models\InterviewSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterviewFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_complete_a_text_interview_session(): void
    {
        $this->post(route('interviews.store'), [
            'role' => 'frontend',
            'level' => 'junior',
            'focus_topic' => 'React State',
        ])->assertRedirect();

        /** @var InterviewSession $session */
        $session = InterviewSession::query()->firstOrFail();

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
}
