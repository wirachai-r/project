<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiClarificationAnswer extends Model
{
    protected $fillable = ['question_id', 'choice_id', 'answered_at'];

    protected $casts = ['answered_at' => 'datetime'];

    public function question()
    {
        return $this->belongsTo(AiClarificationQuestion::class, 'question_id');
    }

    public function choice()
    {
        return $this->belongsTo(AiClarificationChoice::class, 'choice_id');
    }
}
