<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleCommentReport;
use Illuminate\Http\Request;

class ArticleCommentReportController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(ArticleCommentReport::query()
            ->with(['comment.user:user_id,first_name,last_name', 'comment.article:article_id,title', 'reporter:user_id,first_name,last_name'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->reason, fn ($q) => $q->where('reason', $request->reason))
            ->when($request->search, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->whereHas('comment', fn ($comment) => $comment->where('content', 'like', "%{$search}%"))
                        ->orWhereHas('comment.article', fn ($article) => $article->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('reporter', function ($reporter) use ($search) {
                            $reporter->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', $request->sort_direction === 'asc' ? 'asc' : 'desc')
            ->paginate($request->integer('per_page', 20)));
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
}
