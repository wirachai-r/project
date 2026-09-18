<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdaptiveAssessmentAnswer extends Model
{
    protected $fillable = ['adaptive_assessment_id', 'symptom_id', 'answer'];

    public function symptom()
    {
        return $this->belongsTo(MainSymptom::class, 'symptom_id', 'symptom_id');
    }
}
