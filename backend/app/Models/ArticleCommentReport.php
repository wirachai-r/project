<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleCommentReport extends Model
{
    protected $fillable = ['article_comment_id', 'user_id', 'reason', 'details', 'status', 'reviewed_by', 'reviewed_at'];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function comment()
    {
        return $this->belongsTo(ArticleComment::class, 'article_comment_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
