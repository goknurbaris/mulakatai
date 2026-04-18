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
            'role' => 'frontend-react',
            'level' => 'junior',
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
            'role' => 'frontend-react',
            'level' => 'junior',
        ]);

        $session = InterviewSession::query()->firstOrFail();

        $this->post(route('interviews.answer', $session), [
            'answer' => 'too short',
        ])->assertSessionHasErrors('answer');
    }
}
