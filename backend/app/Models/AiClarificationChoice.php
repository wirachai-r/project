<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiClarificationChoice extends Model
{
    protected $fillable = [
        'question_id', 'external_id', 'choice_text', 'maps_to', 'maps_to_choice_id', 'sequence',
    ];

    public function question()
    {
        return $this->belongsTo(AiClarificationQuestion::class, 'question_id');
    }
}
