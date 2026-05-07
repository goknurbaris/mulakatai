<?php

namespace Tests\Unit;

use App\Services\Interview\QuestionBank;
use Tests\TestCase;

class QuestionBankTest extends TestCase
{
    public function test_frontend_questions_change_by_level(): void
    {
        $bank = new QuestionBank();

        $juniorQuestions = $bank->forRoleLevel('frontend', 'junior');
        $midQuestions = $bank->forRoleLevel('frontend', 'mid');

        $this->assertCount(10, $juniorQuestions);
        $this->assertCount(10, $midQuestions);
        $this->assertNotSame($juniorQuestions[0]['question'], $midQuestions[0]['question']);
    }

    public function test_backend_questions_change_by_level(): void
    {
        $bank = new QuestionBank();

        $juniorQuestions = $bank->forRoleLevel('backend', 'junior');
        $midQuestions = $bank->forRoleLevel('backend', 'mid');

        $this->assertNotSame($juniorQuestions[0]['question'], $midQuestions[0]['question']);
    }

    public function test_fullstack_questions_change_by_level(): void
    {
        $bank = new QuestionBank();

        $juniorQuestions = $bank->forRoleLevel('fullstack', 'junior');
        $midQuestions = $bank->forRoleLevel('fullstack', 'mid');

        $this->assertNotSame($juniorQuestions[0]['question'], $midQuestions[0]['question']);
    }
}

