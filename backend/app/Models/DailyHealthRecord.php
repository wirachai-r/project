<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyHealthRecord extends Model
{
    protected $fillable = ['user_id', 'recorded_on', 'recorded_at', 'status', 'note'];

    protected $casts = [
        'recorded_on' => 'date:Y-m-d',
        'recorded_at' => 'datetime',
    ];

    public function symptoms()
    {
        return $this->belongsToMany(
            MainSymptom::class,
            'daily_health_record_symptoms',
            'daily_health_record_id',
            'symptom_id',
            'id',
            'symptom_id',
        )->withPivot('display_order')->withTimestamps()->orderByPivot('display_order');
    }

    public function healthEpisodes()
    {
        return $this->belongsToMany(HealthEpisode::class, 'daily_health_record_health_episode')->withTimestamps();
    }
}
