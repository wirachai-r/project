<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    protected $primaryKey = 'disease_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'disease_id',
        'disease_name',
        'disease_name_en',
        'description',
        'disease_image',
        'status',
        'disease_category_id',
        'created_by',
        'updated_by',
    ];

    public function category()
    {
        return $this->belongsTo(DiseaseCategory::class, 'disease_category_id', 'disease_category_id');
    }

    public function treatmentOrders()
    {
        return $this->hasMany(TreatmentOrder::class, 'disease_id', 'disease_id');
    }
}
