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

    public function diseases()
    {
        return $this->belongsToMany(
            Disease::class,
            'assessment_result_diseases',
            'assessment_result_id',
            'disease_id',
            'id',
            'disease_id'
        )->withPivot([
            'display_order',
            'match_percent',
            'supporting_symptom_count',
            'evaluated_symptom_count',
        ])->orderBy('assessment_result_diseases.display_order');
    }
}
