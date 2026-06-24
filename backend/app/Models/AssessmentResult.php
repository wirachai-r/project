<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentResult extends Model
{
    protected $fillable = [
        'assessment_id',
        'urgency_level',
        'should_see_doctor',
        'recommendation',
        'rule_id',
        'disease_id',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function rule()
    {
        return $this->belongsTo(DiagnosisRule::class, 'rule_id', 'rule_id');
    }

    public function disease()
    {
        return $this->belongsTo(Disease::class, 'disease_id', 'disease_id');
    }
}
