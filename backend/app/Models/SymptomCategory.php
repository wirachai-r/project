<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SymptomCategory extends Model
{
    protected $primaryKey = 'symptom_category_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'symptom_category_id',
        'category_name',
        'category_name_en',
        'description',
        'icon',
        'status',
        'created_by',
        'updated_by',
    ];
}
