<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdaptiveQuestionRule extends Model
{
    protected $fillable = [
        'initial_symptom_id', 'adaptive_question_id', 'question_stage',
        'priority', 'is_required', 'status', 'evidence_source',
        'evidence_status', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function question()
    {
        return $this->belongsTo(AdaptiveQuestion::class, 'adaptive_question_id');
    }

    public function initialSymptom()
    {
        return $this->belongsTo(MainSymptom::class, 'initial_symptom_id', 'symptom_id');
    }
}
