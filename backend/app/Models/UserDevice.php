<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id', 'device_token', 'device_type', 'device_name', 'status', 'last_active_at',
    ];

    protected function casts(): array
    {
        return ['last_active_at' => 'datetime'];
    }
}
