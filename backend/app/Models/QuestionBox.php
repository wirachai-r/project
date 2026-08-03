<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionBox extends Model
{
    protected $primaryKey = 'box_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'box_id',
        'question_text',
        'question_text_en',
        'question_image',
        'question_type',
        'min_required',
        'yes_next_box_id',
        'yes_next_diagram_id',
        'no_next_box_id',
        'no_next_diagram_id',
        'detail',
        'status',
        'diagram_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'min_required' => 'integer',
    ];

    public function diagram()
    {
        return $this->belongsTo(Diagram::class, 'diagram_id', 'diagram_id');
    }

    public function choices()
    {
        return $this->hasMany(AnswerChoice::class, 'box_id', 'box_id');
    }

    // alias เผื่อโค้ดที่อื่นเรียกชื่อ answerChoices() แทน choices()
    public function answerChoices()
    {
        return $this->choices();
    }

    public function ruleConditions()
    {
        return $this->hasMany(RuleCondition::class, 'box_id', 'box_id');
    }

    public function assessmentAnswers()
    {
        return $this->hasMany(AssessmentAnswer::class, 'box_id', 'box_id');
    }

    public function yesNextBox()
    {
        return $this->belongsTo(QuestionBox::class, 'yes_next_box_id', 'box_id');
    }

    public function yesNextDiagram()
    {
        return $this->belongsTo(Diagram::class, 'yes_next_diagram_id', 'diagram_id');
    }

    public function noNextBox()
    {
        return $this->belongsTo(QuestionBox::class, 'no_next_box_id', 'box_id');
    }

    public function noNextDiagram()
    {
        return $this->belongsTo(Diagram::class, 'no_next_diagram_id', 'diagram_id');
    }

    public function isChecklist(): bool
    {
        return $this->question_type === 'M';
    }
}
