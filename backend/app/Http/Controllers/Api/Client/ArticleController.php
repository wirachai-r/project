<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ArticleCategoryResource;
use App\Http\Resources\Client\ArticleResource;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleView;
use Illuminate\Http\Request;

/**
 * @tags Client ArticleController
 */

class ArticleController extends Controller
{
    public function categories()
    {
        $categories = ArticleCategory::query()
            ->where('status', '1')
            ->orderBy('category_name')
            ->get();

        return ArticleCategoryResource::collection($categories);
    }

    public function index(Request $request)
    {
        $articles = Article::query()
            ->with('category')
            ->where('status', '1')
            ->when($request->article_category_id, fn($q) => $q->where('article_category_id', $request->article_category_id))
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->orderBy('published_at', 'desc')
            ->paginate(20);

        return ArticleResource::collection($articles);
    }

    public function show(Article $article)
    {
        abort_if($article->status !== '1', 404);

        $article->increment('view_count');

        return new ArticleResource($article->load('category'));
    }

    public function recordView(Request $request, Article $article)
    {
        ArticleView::create([
            'user_id'       => $request->user()?->user_id,
            'article_id'    => $article->article_id,
            'read_duration' => $request->read_duration ?? 0,
            'is_completed'  => $request->is_completed ?? 'N',
        ]);

        return response()->json(['message' => 'บันทึกการอ่านสำเร็จ']);
    }
}
