<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationCampaign extends Model
{
    protected $fillable = [
        'title', 'body', 'type', 'audience', 'audience_filter', 'channels', 'target_url',
        'status', 'is_persistent', 'starts_at', 'expires_at', 'scheduled_at', 'sent_at',
        'cancelled_at', 'recipient_count', 'sent_count', 'failed_count', 'read_count', 'created_by',
    ];

    protected $casts = [
        'audience_filter' => 'array', 'channels' => 'array', 'is_persistent' => 'boolean',
        'starts_at' => 'datetime', 'expires_at' => 'datetime', 'scheduled_at' => 'datetime',
        'sent_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'campaign_id');
    }
}
