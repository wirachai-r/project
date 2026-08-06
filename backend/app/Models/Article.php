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
        'thumbnail',
        'status',
        'published_at',
        'view_count',
        'article_category_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id', 'article_category_id');
    }
}
