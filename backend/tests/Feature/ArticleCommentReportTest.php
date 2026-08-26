<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleCommentReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_the_same_comment_multiple_times(): void
    {
        $user = User::create([
            'user_id' => '000000001',
            'first_name' => 'Report',
            'last_name' => 'User',
            'email' => 'report@example.com',
            'password' => 'password',
        ]);
        $category = ArticleCategory::create([
            'article_category_id' => 'CAT001',
            'category_name' => 'Test',
        ]);
        $article = Article::create([
            'article_id' => 'ART0000001',
            'title' => 'Test article',
            'content' => 'Test content',
            'article_category_id' => $category->article_category_id,
        ]);
        $comment = ArticleComment::create([
            'article_id' => $article->article_id,
            'user_id' => $user->user_id,
            'content' => 'Test comment',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/article-comments/{$comment->id}/report", [
                'reason' => 'spam',
                'details' => 'First report',
            ])
            ->assertCreated();

        $this->withToken($token)
            ->postJson("/api/article-comments/{$comment->id}/report", [
                'reason' => 'misleading',
                'details' => 'Second report',
            ])
            ->assertCreated();

        $this->assertDatabaseCount('article_comment_reports', 2);
        $this->assertDatabaseHas('article_comment_reports', [
            'article_comment_id' => $comment->id,
            'user_id' => $user->user_id,
            'reason' => 'spam',
            'details' => 'First report',
        ]);
        $this->assertDatabaseHas('article_comment_reports', [
            'article_comment_id' => $comment->id,
            'user_id' => $user->user_id,
            'reason' => 'misleading',
            'details' => 'Second report',
        ]);
    }
}
