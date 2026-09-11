<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleCommentReport;
use App\Support\AdminTableQuery;
use Illuminate\Http\Request;

class ArticleCommentReportController extends Controller
{
    public function index(Request $request)
    {
        $reasons = array_filter((array) $request->input('reason', []));
        $reports = ArticleCommentReport::query()
            ->with([
                'comment' => fn ($comment) => $comment
                    ->withCount([
                        'likes',
                        'reports as pending_reports_count' => fn ($reports) => $reports->where('status', 'pending'),
                    ]),
                'comment.user:user_id,first_name,last_name,email,profile_image',
                'comment.article:article_id,title',
                'reporter:user_id,first_name,last_name',
            ])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($reasons, fn ($q) => $q->whereIn('reason', $reasons))
            ->when($request->search, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->whereHas('comment', fn ($comment) => AdminTableQuery::fuzzySearch($comment, $search, 'id', ['content']))
                        ->orWhereHas('comment.article', fn ($article) => AdminTableQuery::fuzzySearch($article, $search, 'article_id', ['title']))
                        ->orWhereHas('reporter', function ($reporter) use ($search) {
                            AdminTableQuery::fuzzySearch($reporter, $search, 'user_id', ['first_name', 'last_name']);
                        });
                });
            })
            ->orderBy('created_at', $request->sort_direction === 'asc' ? 'asc' : 'desc')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        $reports->getCollection()->each(function (ArticleCommentReport $report): void {
            if ($report->comment?->user) {
                $report->comment->user->profile_image = $this->publicImageUrl(
                    $report->comment->user->profile_image
                );
            }
        });

        return response()->json($reports);
    }

    public function resolve(Request $request, ArticleCommentReport $report)
    {
        $validated = $request->validate(['action' => ['required', 'in:dismiss,hide,delete']]);
        $comment = $report->comment;

        if ($validated['action'] === 'hide') {
            $comment->update(['hidden_at' => now()]);
        } elseif ($validated['action'] === 'delete') {
            $comment->delete();
        }

        $report->update([
            'status' => $validated['action'] === 'dismiss' ? 'dismissed' : 'resolved',
            'reviewed_by' => $request->user()->user_id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['message' => 'จัดการรายงานแล้ว']);
    }

    private function publicImageUrl(?string $path): ?string
    {
        if (! $path || filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return url('/api/media/'.ltrim($path, '/'));
    }
}
