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
        'current_box_id',
        'assessment_status',
        'started_at',
        'completed_at',
        'is_saved',
        'assessment_type',
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

    public function aiGuidance()
    {
        return $this->hasOne(AiAssessmentGuidance::class);
    }

    public function adaptiveAssessment()
    {
        return $this->hasOne(AdaptiveAssessment::class, 'assessment_id');
    }

    public function clarificationSessions()
    {
        return $this->hasMany(AiClarificationSession::class);
    }

    public function followUps()
    {
        return $this->hasMany(SymptomFollowUp::class);
    }

    public function healthEpisode()
    {
        return $this->belongsToMany(HealthEpisode::class, 'health_episode_assessments')
            ->withPivot(['relationship_type', 'attached_at'])->withTimestamps();
    }
}
