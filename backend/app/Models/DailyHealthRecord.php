<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyHealthRecord extends Model
{
    protected $fillable = ['user_id', 'recorded_on', 'status', 'note'];

    protected $casts = [
        'recorded_on' => 'date:Y-m-d',
    ];
}
