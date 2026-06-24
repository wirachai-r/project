<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentOrder extends Model
{
    protected $primaryKey = 'order_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'order_id',
        'order_name',
        'order_name_en',
        'description',
        'urgency_type',
        'order_sequence',
        'status',
        'disease_id',
        'created_by',
        'updated_by',
    ];

    public function disease()
    {
        return $this->belongsTo(Disease::class, 'disease_id', 'disease_id');
    }
}
