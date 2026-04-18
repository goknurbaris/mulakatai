<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('interview_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('question_index');
            $table->string('topic');
            $table->text('question_text');
            $table->text('user_answer');
            $table->unsignedTinyInteger('ai_score');
            $table->json('feedback_json');
            $table->timestamps();

            $table->unique(['interview_session_id', 'question_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_answers');
    }
};
