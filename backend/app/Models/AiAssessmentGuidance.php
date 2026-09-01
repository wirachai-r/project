<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAssessmentGuidance extends Model
{
    protected $fillable = ['assessment_id', 'content', 'provider', 'model'];

    protected $casts = ['content' => 'array'];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
}
