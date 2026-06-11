<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiseaseCategory extends Model
{
    protected $primaryKey = 'disease_category_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'disease_category_id',
        'category_name',
        'category_name_en',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function diseases()
    {
        return $this->hasMany(Disease::class, 'disease_category_id', 'disease_category_id');
    }
}
