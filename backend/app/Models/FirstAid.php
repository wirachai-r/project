<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirstAid extends Model
{
    protected $primaryKey = 'first_aid_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'first_aid_id',
        'title',
        'title_en',
        'content',
        'content_en',
        'cover_image',
        'status',
        'first_aid_category_id',
        'created_by',
        'updated_by',
    ];

    public function category()
    {
        return $this->belongsTo(FirstAidCategory::class, 'first_aid_category_id', 'first_aid_category_id');
    }
}
