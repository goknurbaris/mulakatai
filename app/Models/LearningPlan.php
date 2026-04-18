<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class LearningPlan extends Model
{
    protected $fillable = [
        'interview_session_id',
        'plan_json',
    ];

    protected $casts = [
        'plan_json' => 'array',
    ];

    public function interviewSession(): BelongsTo
    {
        return $this->belongsTo(InterviewSession::class);
    }
}
