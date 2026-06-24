<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnswerChoice extends Model
{
    protected $primaryKey = 'choice_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'choice_id',
        'choice_text',
        'choice_text_en',
        'choice_image',
        'order',
        'status',
        'box_id',
        'next_box_id',
        'next_diagram_id',
        'created_by',
        'updated_by',
    ];

    public function box()
    {
        return $this->belongsTo(QuestionBox::class, 'box_id', 'box_id');
    }

    public function nextBox()
    {
        return $this->belongsTo(QuestionBox::class, 'next_box_id', 'box_id');
    }

    public function nextDiagram()
    {
        return $this->belongsTo(Diagram::class, 'next_diagram_id', 'diagram_id');
    }

    public function ruleConditions()
    {
        return $this->hasMany(RuleCondition::class, 'choice_id', 'choice_id');
    }

    public function assessmentAnswers()
    {
        return $this->hasMany(AssessmentAnswer::class, 'choice_id', 'choice_id');
    }
}
