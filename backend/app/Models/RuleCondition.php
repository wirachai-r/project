<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuleCondition extends Model
{
    protected $primaryKey = 'condition_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'condition_id',
        'status',
        'rule_id',
        'box_id',
        'choice_id',
        'logic_operator',
        'created_by',
        'updated_by',
    ];

    public function rule()
    {
        return $this->belongsTo(DiagnosisRule::class, 'rule_id', 'rule_id');
    }

    public function box()
    {
        return $this->belongsTo(QuestionBox::class, 'box_id', 'box_id');
    }

    public function choice()
    {
        return $this->belongsTo(AnswerChoice::class, 'choice_id', 'choice_id');
    }
}
