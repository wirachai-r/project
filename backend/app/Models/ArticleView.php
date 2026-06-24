<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleView extends Model
{
    protected $fillable = [
        'user_id',
        'article_id',
        'read_duration',
        'is_completed',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id', 'article_id');
    }
}
