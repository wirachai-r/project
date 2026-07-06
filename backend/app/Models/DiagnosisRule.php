<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosisRule extends Model
{
    protected $primaryKey = 'rule_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'rule_id',
        'urgency_level',
        'time_frame',
        'time_frame_en',
        'note',
        'note_en',
        'medical_reference',
        'status',
        'diagram_id',
        'created_by',
        'updated_by',
    ];

    public function diagram()
    {
        return $this->belongsTo(Diagram::class, 'diagram_id', 'diagram_id');
    }

    // Many-to-many ผ่าน rule_diseases pivot
    public function diseases()
    {
        return $this->belongsToMany(
            Disease::class,
            'rule_diseases',
            'rule_id',
            'disease_id',
            'rule_id',
            'disease_id'
        )->withPivot('display_order')->orderBy('rule_diseases.display_order');
    }

    public function conditions()
    {
        return $this->hasMany(RuleCondition::class, 'rule_id', 'rule_id');
    }

    public function assessmentResults()
    {
        return $this->hasMany(AssessmentResult::class, 'rule_id', 'rule_id');
    }
}
