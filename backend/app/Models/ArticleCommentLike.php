<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleCommentLike extends Model
{
    protected $fillable = ['article_comment_id', 'user_id'];
}
