<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowUpEntryAnswer extends Model
{
    protected $fillable = [
        'follow_up_entry_id', 'question_template_id', 'question_text_snapshot',
        'answer_type_snapshot', 'answer_value', 'answered_at',
    ];

    protected $casts = ['answer_value' => 'array', 'answered_at' => 'datetime'];

    public function entry()
    {
        return $this->belongsTo(FollowUpEntry::class, 'follow_up_entry_id');
    }

    public function template()
    {
        return $this->belongsTo(FollowUpQuestionTemplate::class, 'question_template_id');
    }
}
