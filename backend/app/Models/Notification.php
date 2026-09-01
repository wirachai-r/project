<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'campaign_id',
        'title',
        'body',
        'type',
        'is_read',
        'read_at',
        'dismissed_at',
        'user_id',
        'delivery_status',
        'delivery_error',
        'delivered_at',
        'visible_in_app',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'visible_in_app' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function campaign()
    {
        return $this->belongsTo(NotificationCampaign::class, 'campaign_id');
    }
}
