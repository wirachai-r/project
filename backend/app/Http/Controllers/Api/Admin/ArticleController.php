<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleRequest;
use App\Http\Resources\Admin\ArticleResource;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $articles = Article::query()
            ->with('category')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->article_category_id, fn($q) => $q->where('article_category_id', $request->article_category_id))
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return ArticleResource::collection($articles);
    }

    public function store(ArticleRequest $request)
    {
        $article = Article::create([
            'article_id'          => $this->generateId(),
            'title'               => $request->title,
            'title_en'            => $request->title_en,
            'content'             => $request->content,
            'content_en'          => $request->content_en,
            'cover_image'         => $request->cover_image,
            'status'              => $request->status ?? '1',
            'article_category_id' => $request->article_category_id,
            'created_by'          => $request->user()->user_id,
            'updated_by'          => $request->user()->user_id,
        ]);

        return new ArticleResource($article->load('category'));
    }

    public function show(Article $article)
    {
        return new ArticleResource($article->load('category'));
    }

    public function update(ArticleRequest $request, Article $article)
    {
        $article->update([
            'title'               => $request->title,
            'title_en'            => $request->title_en,
            'content'             => $request->content,
            'content_en'          => $request->content_en,
            'cover_image'         => $request->cover_image,
            'status'              => $request->status ?? $article->status,
            'article_category_id' => $request->article_category_id,
            'updated_by'          => $request->user()->user_id,
        ]);

        return new ArticleResource($article->load('category'));
    }

    public function destroy(Article $article)
    {
        $article->delete();

        return response()->json(['message' => 'ลบบทความสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = Article::max('article_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
