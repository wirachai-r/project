<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowUpEntry extends Model
{
    protected $fillable = ['episode_symptom_id', 'severity', 'temperature', 'note', 'recorded_at'];

    protected $casts = ['severity' => 'integer', 'temperature' => 'float', 'recorded_at' => 'datetime'];

    public function episodeSymptom()
    {
        return $this->belongsTo(EpisodeSymptom::class);
    }

    public function answers()
    {
        return $this->hasMany(FollowUpEntryAnswer::class);
    }
}
