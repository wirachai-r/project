<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleComment;
use App\Models\ArticleCommentReport;
use App\Support\AdminTableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticleCommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'visibility' => ['nullable', 'in:visible,hidden'],
            'report_status' => ['nullable', 'in:reported,unreported'],
            'sort_by' => ['nullable', 'in:latest_comment,latest_report'],
            'article_id' => ['nullable', 'string', 'max:10'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $query = ArticleComment::query()
            ->with([
                'article:article_id,title,content,thumbnail',
                'user:user_id,first_name,last_name,email,profile_image',
                'replies' => fn ($query) => $query
                    ->reorder()
                    ->latest()
                    ->with('user:user_id,first_name,last_name,email,profile_image')
                    ->withCount('likes')
                    ->withCount(['reports as pending_reports_count' => fn ($reports) => $reports->where('status', 'pending')]),
            ])
            ->withCount(['likes', 'replies'])
            ->withCount(['reports as pending_reports_count' => fn ($reports) => $reports->where('status', 'pending')])
            ->whereNull('parent_id')
            ->when($validated['article_id'] ?? null, fn ($query, $articleId) => $query->where('article_id', $articleId))
            ->when(($validated['visibility'] ?? null) === 'visible', fn ($query) => $query->whereNull('hidden_at'))
            ->when(($validated['visibility'] ?? null) === 'hidden', fn ($query) => $query->whereNotNull('hidden_at'))
            ->when(($validated['report_status'] ?? null) === 'reported', function ($query) {
                $query->where(function ($nested) {
                    $nested->whereHas('reports', fn ($reports) => $reports->where('status', 'pending'))
                        ->orWhereHas('replies.reports', fn ($reports) => $reports->where('status', 'pending'));
                });
            })
            ->when(($validated['report_status'] ?? null) === 'unreported', function ($query) {
                $query->whereDoesntHave('reports', fn ($reports) => $reports->where('status', 'pending'))
                    ->whereDoesntHave('replies.reports', fn ($reports) => $reports->where('status', 'pending'));
            })
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    AdminTableQuery::fuzzySearch($nested, $search, 'id', ['content']);
                    $nested->orWhereHas('article', fn ($article) => AdminTableQuery::fuzzySearch($article, $search, 'article_id', ['title']))
                        ->orWhereHas('user', function ($user) use ($search) {
                            AdminTableQuery::fuzzySearch($user, $search, 'user_id', ['first_name', 'last_name', 'email']);
                        })
                        ->orWhereHas('replies', fn ($reply) => AdminTableQuery::fuzzySearch($reply, $search, 'id', ['content']));
                });
            })
            ->when(
                ($validated['sort_by'] ?? 'latest_comment') === 'latest_report',
                fn ($query) => $query->orderByDesc(
                    ArticleCommentReport::query()
                        ->selectRaw('MAX(article_comment_reports.created_at)')
                        ->join('article_comments as reported_comments', 'reported_comments.id', '=', 'article_comment_reports.article_comment_id')
                        ->where('article_comment_reports.status', 'pending')
                        ->where(function ($reported) {
                            $reported->whereColumn('reported_comments.id', 'article_comments.id')
                                ->orWhereColumn('reported_comments.parent_id', 'article_comments.id');
                        })
                )->orderByDesc('article_comments.created_at'),
                fn ($query) => $query->orderByDesc(
                    ArticleComment::query()
                        ->from('article_comments as thread_comments')
                        ->selectRaw('MAX(thread_comments.created_at)')
                        ->where(function ($thread) {
                            $thread->whereColumn('thread_comments.id', 'article_comments.id')
                                ->orWhereColumn('thread_comments.parent_id', 'article_comments.id');
                        })
                )
            );

        $articleTotal = (clone $query)
            ->reorder()
            ->distinct()
            ->count('article_id');

        $comments = $query->paginate($validated['per_page'] ?? 20);

        $comments->getCollection()->each(function (ArticleComment $comment): void {
            if ($comment->article) {
                $comment->article->thumbnail = $this->publicImageUrl($comment->article->thumbnail);
            }

            if ($comment->user) {
                $comment->user->profile_image = $this->publicImageUrl($comment->user->profile_image);
            }

            $comment->replies->each(function (ArticleComment $reply): void {
                if ($reply->user) {
                    $reply->user->profile_image = $this->publicImageUrl($reply->user->profile_image);
                }
            });
        });

        return response()->json([
            ...$comments->toArray(),
            'article_total' => $articleTotal,
        ]);
    }

    public function updateVisibility(Request $request, ArticleComment $comment): JsonResponse
    {
        $validated = $request->validate(['hidden' => ['required', 'boolean']]);
        $comment->update(['hidden_at' => $validated['hidden'] ? now() : null]);

        return response()->json(['message' => $validated['hidden'] ? 'ซ่อนความคิดเห็นแล้ว' : 'แสดงความคิดเห็นแล้ว']);
    }

    public function resolveReports(Request $request, ArticleComment $comment): JsonResponse
    {
        $validated = $request->validate(['action' => ['required', 'in:dismiss,hide,delete']]);

        DB::transaction(function () use ($comment, $request, $validated): void {
            $comment->reports()
                ->where('status', 'pending')
                ->update([
                    'status' => $validated['action'] === 'dismiss' ? 'dismissed' : 'resolved',
                    'reviewed_by' => $request->user()->user_id,
                    'reviewed_at' => now(),
                ]);

            if ($validated['action'] === 'hide') {
                $comment->update(['hidden_at' => now()]);
            } elseif ($validated['action'] === 'delete') {
                $comment->delete();
            }
        });

        return response()->json(['message' => 'จัดการรายงานความคิดเห็นแล้ว']);
    }

    public function destroy(ArticleComment $comment): JsonResponse
    {
        $comment->delete();

        return response()->json(['message' => 'ลบความคิดเห็นแล้ว']);
    }

    private function publicImageUrl(?string $path): ?string
    {
        if (! $path || filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return url('/api/media/'.ltrim($path, '/'));
    }
}
