<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SymptomFollowUp extends Model
{
    protected $fillable = ['assessment_id', 'user_id', 'severity', 'temperature', 'note', 'recorded_at'];

    protected $casts = [
        'severity' => 'integer',
        'temperature' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }
}
