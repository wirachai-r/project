<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = [
        'parent_assessment_id',
        'user_id',
        'session_token',
        'symptom_id',
        'diagram_id',
        'assessment_status',
        'started_at',
        'completed_at',
        'is_saved',
    ];

    protected $casts = [
        'is_saved' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Assessment::class, 'parent_assessment_id');
    }

    public function continuations()
    {
        return $this->hasMany(Assessment::class, 'parent_assessment_id');
    }

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

    public function followUps()
    {
        return $this->hasMany(SymptomFollowUp::class);
    }
}
