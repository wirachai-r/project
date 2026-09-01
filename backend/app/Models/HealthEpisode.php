<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthEpisode extends Model
{
    protected $fillable = ['user_id', 'source_assessment_id', 'status', 'started_at', 'ended_at'];

    protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime'];

    public function symptoms()
    {
        return $this->hasMany(EpisodeSymptom::class);
    }

    public function sourceAssessment()
    {
        return $this->belongsTo(Assessment::class, 'source_assessment_id');
    }
}
