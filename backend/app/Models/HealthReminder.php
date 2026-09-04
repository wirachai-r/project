<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class HealthReminder extends Model
{
    protected $fillable = [
        'user_id', 'health_episode_id', 'title', 'reminder_type', 'frequency', 'time_of_day',
        'days_of_week', 'timezone', 'is_enabled', 'next_run_at', 'last_sent_at',
    ];

    public function healthEpisode()
    {
        return $this->belongsTo(HealthEpisode::class);
    }

    protected $casts = [
        'days_of_week' => 'array',
        'is_enabled' => 'boolean',
        'next_run_at' => 'datetime',
        'last_sent_at' => 'datetime',
    ];

    public function calculateNextRun(?CarbonImmutable $from = null): CarbonImmutable
    {
        $timezone = $this->timezone ?: 'Asia/Bangkok';
        $cursor = ($from ?? CarbonImmutable::now('UTC'))->setTimezone($timezone);
        [$hour, $minute] = array_map('intval', explode(':', $this->time_of_day));
        $candidate = $cursor->setTime($hour, $minute);

        if ($candidate->lessThanOrEqualTo($cursor)) {
            $candidate = $candidate->addDay();
        }

        if ($this->frequency === 'weekly') {
            $days = array_map('intval', $this->days_of_week ?? []);
            while (! in_array($candidate->dayOfWeekIso, $days, true)) {
                $candidate = $candidate->addDay();
            }
        }

        return $candidate->utc();
    }
}
