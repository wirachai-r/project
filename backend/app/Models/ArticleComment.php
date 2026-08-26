<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleComment extends Model
{
    protected $fillable = ['article_id', 'user_id', 'parent_id', 'content', 'hidden_at'];

    protected $casts = ['hidden_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id', 'article_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public function likes()
    {
        return $this->hasMany(ArticleCommentLike::class);
    }

    public function reports()
    {
        return $this->hasMany(ArticleCommentReport::class);
    }
}
