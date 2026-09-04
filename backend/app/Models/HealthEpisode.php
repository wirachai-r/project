<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthEpisode extends Model
{
    protected $fillable = [
        'user_id', 'source_assessment_id', 'status', 'started_at', 'paused_at',
        'ended_at', 'end_reason', 'end_note',
    ];

    protected $casts = ['started_at' => 'datetime', 'paused_at' => 'datetime', 'ended_at' => 'datetime'];

    public function symptoms()
    {
        return $this->hasMany(EpisodeSymptom::class);
    }

    public function sourceAssessment()
    {
        return $this->belongsTo(Assessment::class, 'source_assessment_id');
    }

    public function assessments()
    {
        return $this->belongsToMany(Assessment::class, 'health_episode_assessments')
            ->withPivot(['relationship_type', 'attached_at'])->withTimestamps();
    }

    public function dailyHealthRecords()
    {
        return $this->belongsToMany(DailyHealthRecord::class, 'daily_health_record_health_episode')->withTimestamps();
    }

    public function reminders()
    {
        return $this->hasMany(HealthReminder::class);
    }
}
