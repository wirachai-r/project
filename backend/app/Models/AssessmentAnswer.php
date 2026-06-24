<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAnswer extends Model
{
    protected $fillable = [
        'assessment_id',
        'box_id',
        'choice_id',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
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
