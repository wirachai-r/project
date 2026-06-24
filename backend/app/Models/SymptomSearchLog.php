<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SymptomSearchLog extends Model
{
    protected $fillable = [
        'keyword',
        'ip_address',
        'user_id',
        'symptom_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function symptom()
    {
        return $this->belongsTo(MainSymptom::class, 'symptom_id', 'symptom_id');
    }
}
