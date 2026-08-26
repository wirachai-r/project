<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountActivity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'event', 'device_name', 'device_type', 'ip_address',
        'user_agent', 'metadata', 'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];
}
