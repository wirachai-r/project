<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdaptiveQuestionRule extends Model
{
    protected $fillable = [
        'initial_symptom_id', 'adaptive_question_id', 'question_stage',
        'priority', 'is_required', 'status',
    ];

    protected $casts = ['is_required' => 'boolean'];

    public function question()
    {
        return $this->belongsTo(AdaptiveQuestion::class, 'adaptive_question_id');
    }
}
