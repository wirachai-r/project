<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $primaryKey = 'article_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'article_id',
        'title',
        'title_en',
        'content',
        'content_en',
        'cover_image',
        'status',
        'article_category_id',
        'created_by',
        'updated_by',
    ];

    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id', 'article_category_id');
    }
}
