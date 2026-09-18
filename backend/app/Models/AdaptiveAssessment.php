<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdaptiveAssessment extends Model
{
    protected $fillable = ['user_id', 'session_token', 'initial_symptom_id', 'status', 'question_count', 'completed_at', 'assessment_id'];

    protected $casts = ['completed_at' => 'datetime'];

    public function initialSymptom()
    {
        return $this->belongsTo(MainSymptom::class, 'initial_symptom_id', 'symptom_id');
    }

    public function answers()
    {
        return $this->hasMany(AdaptiveAssessmentAnswer::class);
    }

    public function results()
    {
        return $this->hasMany(AdaptiveAssessmentResult::class)->orderBy('display_order');
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
}
