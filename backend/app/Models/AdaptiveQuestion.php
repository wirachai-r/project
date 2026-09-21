<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdaptiveQuestion extends Model
{
    protected $fillable = [
        'question_symptom_id', 'question_text', 'explanation_text', 'answer_type',
        'status', 'evidence_source', 'approved_by', 'approved_at',
    ];

    protected $casts = ['approved_at' => 'datetime'];

    public function symptom()
    {
        return $this->belongsTo(MainSymptom::class, 'question_symptom_id', 'symptom_id');
    }

    public function options()
    {
        return $this->hasMany(AdaptiveQuestionOption::class)->orderBy('display_order');
    }

    public function rules()
    {
        return $this->hasMany(AdaptiveQuestionRule::class);
    }
}
