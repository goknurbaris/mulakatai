<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class InterviewSession extends Model
{
    protected $fillable = [
        'role',
        'level',
        'focus_topic',
        'status',
        'current_question_index',
        'total_score',
        'questions_snapshot',
        'summary',
    ];

    protected $casts = [
        'questions_snapshot' => 'array',
        'summary' => 'array',
        'total_score' => 'float',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(InterviewAnswer::class);
    }

    public function learningPlan(): HasOne
    {
        return $this->hasOne(LearningPlan::class);
    }
}
