<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleCategory extends Model
{
    protected $primaryKey = 'article_category_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'article_category_id',
        'category_name',
        'category_name_en',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function articles()
    {
        return $this->hasMany(Article::class, 'article_category_id', 'article_category_id');
    }
}
