<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowUpQuestionTemplate extends Model
{
    protected $fillable = [
        'question_text', 'description', 'answer_type', 'options', 'unit', 'response_rules',
        'is_required', 'applies_to_all_symptoms', 'status',
    ];

    protected $casts = [
        'options' => 'array',
        'response_rules' => 'array',
        'is_required' => 'boolean',
        'applies_to_all_symptoms' => 'boolean',
    ];

    public function symptoms()
    {
        return $this->belongsToMany(MainSymptom::class, 'symptom_follow_up_questions', 'question_template_id', 'symptom_id')
            ->withPivot(['sequence', 'is_required_override', 'status'])->withTimestamps();
    }
}
