<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiClarificationQuestion extends Model
{
    protected $fillable = ['session_id', 'question_text', 'explanation', 'source', 'sequence'];

    public function session()
    {
        return $this->belongsTo(AiClarificationSession::class, 'session_id');
    }

    public function choices()
    {
        return $this->hasMany(AiClarificationChoice::class, 'question_id')->orderBy('sequence');
    }

    public function answer()
    {
        return $this->hasOne(AiClarificationAnswer::class, 'question_id');
    }
}
