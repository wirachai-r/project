<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ArticleCategoryResource;
use App\Http\Resources\Client\ArticleResource;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleComment;
use App\Models\ArticleCommentLike;
use App\Models\ArticleCommentReport;
use App\Models\ArticleLike;
use App\Models\ArticleView;
use App\Support\AdminTableQuery;
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
        $validated = $request->validate([
            'sort' => ['nullable', 'in:latest,popular'],
        ]);
        $sort = $validated['sort'] ?? 'latest';

        $articles = Article::query()
            ->with('category')
            ->where('status', '1')
            ->when($request->article_category_id, fn ($q) => $q->where('article_category_id', $request->article_category_id))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch(
                $q,
                $request->string('search')->toString(),
                null,
                ['title', 'title_en'],
            ))
            ->when(
                $sort === 'popular',
                fn ($q) => $q->orderByDesc('view_count')->orderByDesc('published_at'),
                fn ($q) => $q->orderByDesc('published_at'),
            )
            ->paginate(20);

        return ArticleResource::collection($articles);
    }

    public function show(Article $article)
    {
        abort_if($article->status !== '1', 404);

        $article->increment('view_count');

        return new ArticleResource($article->load('category')->loadCount(['likes', 'comments']));
    }

    public function comments(Request $request, Article $article)
    {
        $userId = $request->user('sanctum')?->user_id;
        $comments = $article->comments()
            ->whereNull('parent_id')
            ->whereNull('hidden_at')
            ->with('user:user_id,first_name,last_name,profile_image,avatar')
            ->with(['replies' => function ($query) use ($userId) {
                $query->whereNull('hidden_at')
                    ->with('user:user_id,first_name,last_name,profile_image,avatar')
                    ->withCount('likes');

                if ($userId) {
                    $query->withExists(['likes as liked' => fn ($likes) => $likes->where('user_id', $userId)]);
                }
            }])
            ->withCount(['likes', 'replies'])
            ->when($userId, fn ($query) => $query->withExists([
                'likes as liked' => fn ($likes) => $likes->where('user_id', $userId),
            ]))
            ->latest()
            ->paginate(20);

        $comments->getCollection()->each(function (ArticleComment $comment): void {
            if ($comment->user) {
                $comment->user->profile_image = $this->publicImageUrl(
                    $comment->user->profile_image ?: $comment->user->avatar
                );
            }

            $comment->replies->each(function (ArticleComment $reply): void {
                if ($reply->user) {
                    $reply->user->profile_image = $this->publicImageUrl(
                        $reply->user->profile_image ?: $reply->user->avatar
                    );
                }
            });
        });

        return response()->json($comments);
    }

    public function toggleLike(Request $request, Article $article)
    {
        $like = ArticleLike::query()
            ->where('article_id', $article->article_id)
            ->where('user_id', $request->user()->user_id)
            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            ArticleLike::create([
                'article_id' => $article->article_id,
                'user_id' => $request->user()->user_id,
            ]);
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => $article->likes()->count(),
        ]);
    }

    public function engagement(Request $request, Article $article)
    {
        return response()->json([
            'liked' => $article->likes()->where('user_id', $request->user()->user_id)->exists(),
            'likes_count' => $article->likes()->count(),
            'comments_count' => $article->comments()->count(),
        ]);
    }

    public function storeComment(Request $request, Article $article)
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:article_comments,id'],
        ]);

        if (isset($validated['parent_id'])) {
            $parent = ArticleComment::findOrFail($validated['parent_id']);
            abort_if($parent->article_id !== $article->article_id || $parent->parent_id !== null, 422);
        }

        $comment = $article->comments()->create([
            'user_id' => $request->user()->user_id,
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $comment->load('user:user_id,first_name,last_name,profile_image,avatar');
        if ($comment->user) {
            $comment->user->profile_image = $this->publicImageUrl(
                $comment->user->profile_image ?: $comment->user->avatar
            );
        }

        return response()->json(['data' => $comment], 201);
    }

    public function toggleCommentLike(Request $request, ArticleComment $comment)
    {
        $like = ArticleCommentLike::where('article_comment_id', $comment->id)->where('user_id', $request->user()->user_id)->first();
        $like ? $like->delete() : ArticleCommentLike::create(['article_comment_id' => $comment->id, 'user_id' => $request->user()->user_id]);

        return response()->json(['liked' => ! $like, 'likes_count' => $comment->likes()->count()]);
    }

    public function reportComment(Request $request, ArticleComment $comment)
    {
        abort_if(
            $comment->user_id === $request->user()->user_id,
            403,
            'ไม่สามารถรายงานความคิดเห็นของตัวเองได้'
        );

        $validated = $request->validate([
            'reason' => ['required', 'in:spam,inappropriate,misleading,harassment,other'],
            'details' => ['nullable', 'string'],
        ]);
        $report = ArticleCommentReport::create([
            ...$validated,
            'article_comment_id' => $comment->id,
            'user_id' => $request->user()->user_id,
        ]);

        return response()->json(['data' => $report], 201);
    }

    public function destroyComment(Request $request, ArticleComment $comment)
    {
        abort_if($comment->user_id !== $request->user()->user_id, 403);
        $comment->delete();

        return response()->json(['message' => 'ลบความคิดเห็นแล้ว']);
    }

    public function recordView(Request $request, Article $article)
    {
        ArticleView::create([
            'user_id' => $request->user()?->user_id,
            'article_id' => $article->article_id,
            'read_duration' => $request->read_duration ?? 0,
            'is_completed' => $request->is_completed ?? 'N',
        ]);

        return response()->json(['message' => 'บันทึกการอ่านสำเร็จ']);
    }

    private function publicImageUrl(?string $path): ?string
    {
        if (! $path || filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return url('/api/media/'.ltrim($path, '/'));
    }
}
