<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentOrder extends Model
{
    protected $primaryKey = 'treatment_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'treatment_id',
        'treatment_text',
        'treatment_text_en',
        'order',
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
