<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagram extends Model
{
    protected $primaryKey = 'diagram_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'diagram_id',
        'diagram_name',
        'diagram_name_en',
        'description',
        'status',
        'entry_box_id',
        'created_by',
        'updated_by',
    ];

    // Many-to-many กับ main_symptoms ผ่าน symptom_diagrams
    public function symptoms()
    {
        return $this->belongsToMany(
            MainSymptom::class,
            'symptom_diagrams',
            'diagram_id',
            'symptom_id',
            'diagram_id',
            'symptom_id'
        );
    }

    public function entryBox()
    {
        return $this->belongsTo(QuestionBox::class, 'entry_box_id', 'box_id');
    }

    public function questionBoxes()
    {
        return $this->hasMany(QuestionBox::class, 'diagram_id', 'diagram_id');
    }

    public function diagnosisRules()
    {
        return $this->hasMany(DiagnosisRule::class, 'diagram_id', 'diagram_id');
    }

    public function suggestedByRules()
    {
        return $this->belongsToMany(
            DiagnosisRule::class,
            'rule_next_diagrams',
            'diagram_id',
            'rule_id',
            'diagram_id',
            'rule_id'
        )->withPivot(['display_order', 'prompt_text', 'target_box_id'])->withTimestamps();
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'diagram_id', 'diagram_id');
    }
}
