<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdaptiveAssessmentAnswer extends Model
{
    protected $fillable = [
        'adaptive_assessment_id', 'adaptive_question_id', 'symptom_id', 'answer', 'answer_payload',
    ];

    protected $casts = ['answer_payload' => 'array'];

    public function symptom()
    {
        return $this->belongsTo(MainSymptom::class, 'symptom_id', 'symptom_id');
    }

    public function question()
    {
        return $this->belongsTo(AdaptiveQuestion::class, 'adaptive_question_id');
    }
}
