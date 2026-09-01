<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleRequest;
use App\Http\Resources\Admin\ArticleResource;
use App\Models\Article;
use App\Support\AdminTableQuery;
use App\Support\NotificationContent;
use Illuminate\Http\Request;

/**
 * @tags Admin ArticleController
 */
class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $articles = Article::query()
            ->with('category')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->article_category_id, fn ($q) => $q->where('article_category_id', $request->article_category_id))
            ->when($request->filled('article_category_ids'), fn ($q) => $q->whereIn(
                'article_category_id',
                array_filter((array) $request->input('article_category_ids')),
            ))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch($q, $request->search, 'article_id', ['title', 'title_en']))
            ->when(
                in_array($request->sort_by, ['id', 'title', 'published_at', 'updated_at']),
                function ($q) use ($request) {
                    $direction = $request->sort_direction === 'asc' ? 'asc' : 'desc';

                    if ($request->sort_by === 'id') {
                        $q->orderBy('article_id', $direction);
                    } elseif ($request->sort_by === 'title') {
                        $q->orderBy('title', $direction);
                    } elseif ($request->sort_by === 'published_at') {
                        $q->orderBy('published_at', $direction);
                    } elseif ($request->sort_by === 'updated_at') {
                        $q->orderBy('updated_at', $direction);
                    }
                },
                fn ($q) => $q->orderBy('article_id', 'desc')
            )
            ->orderBy('article_id', 'desc')
            ->paginate($perPage);

        return ArticleResource::collection($articles);
    }

    public function store(ArticleRequest $request)
    {
        $article = Article::create([
            'article_id' => $this->generateId(),
            'title' => $request->title,
            'title_en' => $request->title_en,
            'content' => NotificationContent::normalizeImageUrls($request->content),
            'content_en' => NotificationContent::normalizeImageUrls($request->content_en),
            'thumbnail' => $request->thumbnail,
            'status' => $request->status ?? '1',
            'published_at' => $request->status === '1' ? now() : null,
            'article_category_id' => $request->article_category_id,
            'references' => $request->input('references', []),
            'created_by' => $request->user()->user_id,
            'updated_by' => $request->user()->user_id,
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
            'title' => $request->has('title') ? $request->title : $article->title,
            'title_en' => $request->has('title_en') ? $request->title_en : $article->title_en,
            'content' => $request->has('content') ? NotificationContent::normalizeImageUrls($request->content) : $article->content,
            'content_en' => $request->has('content_en') ? NotificationContent::normalizeImageUrls($request->content_en) : $article->content_en,
            'thumbnail' => $request->has('thumbnail') ? $request->thumbnail : $article->thumbnail,
            'status' => $request->status ?? $article->status,
            'published_at' => $request->status === '1' && ! $article->published_at ? now() : $article->published_at,
            'article_category_id' => $request->has('article_category_id') ? $request->article_category_id : $article->article_category_id,
            'references' => $request->has('references') ? $request->input('references') : $article->references,
            'updated_by' => $request->user()->user_id,
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
        $next = $last ? (int) $last + 1 : 1;

        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
