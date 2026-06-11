<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirstAidCategory extends Model
{
    protected $primaryKey = 'first_aid_category_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'first_aid_category_id',
        'category_name',
        'category_name_en',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function firstAids()
    {
        return $this->hasMany(FirstAid::class, 'first_aid_category_id', 'first_aid_category_id');
    }
}
