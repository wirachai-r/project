<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdaptiveQuestionOption extends Model
{
    protected $fillable = [
        'adaptive_question_id', 'option_text', 'option_value', 'target_symptom_id',
        'answer_effect', 'display_order', 'status',
    ];

    public function question()
    {
        return $this->belongsTo(AdaptiveQuestion::class, 'adaptive_question_id');
    }
}
