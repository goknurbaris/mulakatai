<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class InterviewSession extends Model
{
    protected $fillable = [
        'user_id',
        'role',
        'level',
        'focus_topic',
        'interview_objective',
        'status',
        'started_at',
        'completed_at',
        'current_question_index',
        'total_score',
        'questions_snapshot',
        'summary',
    ];

    protected $casts = [
        'questions_snapshot' => 'array',
        'summary' => 'array',
        'total_score' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(InterviewAnswer::class);
    }

    public function learningPlan(): HasOne
    {
        return $this->hasOne(LearningPlan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
