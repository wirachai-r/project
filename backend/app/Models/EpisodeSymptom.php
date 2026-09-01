<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EpisodeSymptom extends Model
{
    protected $fillable = [
        'health_episode_id', 'symptom_id', 'custom_symptom_text', 'is_primary',
        'status', 'first_observed_at', 'ended_at',
    ];

    protected $casts = ['is_primary' => 'boolean', 'first_observed_at' => 'datetime', 'ended_at' => 'datetime'];

    public function episode()
    {
        return $this->belongsTo(HealthEpisode::class, 'health_episode_id');
    }

    public function symptom()
    {
        return $this->belongsTo(MainSymptom::class, 'symptom_id', 'symptom_id');
    }

    public function entries()
    {
        return $this->hasMany(FollowUpEntry::class);
    }
}
