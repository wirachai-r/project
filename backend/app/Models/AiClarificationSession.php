<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiClarificationSession extends Model
{
    protected $fillable = [
        'assessment_id', 'box_id', 'status', 'resolved_to', 'resolved_at', 'expires_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function box()
    {
        return $this->belongsTo(QuestionBox::class, 'box_id', 'box_id');
    }

    public function questions()
    {
        return $this->hasMany(AiClarificationQuestion::class, 'session_id');
    }
}
