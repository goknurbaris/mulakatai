<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class InterviewAnswer extends Model
{
    protected $fillable = [
        'interview_session_id',
        'question_index',
        'topic',
        'question_text',
        'user_answer',
        'ai_score',
        'feedback_json',
    ];

    protected $casts = [
        'feedback_json' => 'array',
    ];

    public function interviewSession(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class);
    }
}
