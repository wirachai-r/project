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
        'references',
        'article_category_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'references' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id', 'article_category_id');
    }

    public function likes()
    {
        return $this->hasMany(ArticleLike::class, 'article_id', 'article_id');
    }

    public function comments()
    {
        return $this->hasMany(ArticleComment::class, 'article_id', 'article_id');
    }
}
