<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = [
        'user_id',
        'session_token',
        'symptom_id',
        'diagram_id',
        'assessment_status',
        'started_at',
        'completed_at',
    ];

    public function symptom()
    {
        return $this->belongsTo(MainSymptom::class, 'symptom_id', 'symptom_id');
    }

    public function diagram()
    {
        return $this->belongsTo(Diagram::class, 'diagram_id', 'diagram_id');
    }

    public function answers()
    {
        return $this->hasMany(AssessmentAnswer::class, 'assessment_id');
    }

    public function results()
    {
        return $this->hasMany(AssessmentResult::class, 'assessment_id');
    }
}
