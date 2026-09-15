<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NavigationCountController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $counts = DB::query()
            ->selectSub(
                fn ($query) => $query->from('article_comment_reports')
                    ->selectRaw('count(*)')
                    ->where('status', 'pending'),
                'pending_comment_reports',
            )
            ->selectSub(
                fn ($query) => $query->from('user_feedback')
                    ->selectRaw('count(*)')
                    ->where('status', 'pending'),
                'pending_feedback',
            )
            ->selectSub(
                fn ($query) => $query->from('notifications')
                    ->selectRaw('count(*)')
                    ->where('user_id', $request->user()->user_id)
                    ->where('visible_in_app', true)
                    ->where(fn ($notification) => $notification
                        ->whereNull('campaign_id')
                        ->orWhereExists(fn ($campaign) => $campaign
                            ->selectRaw('1')
                            ->from('notification_campaigns')
                            ->whereColumn('notification_campaigns.id', 'notifications.campaign_id')
                            ->where(fn ($active) => $active
                                ->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now()))))
                    ->whereNull('dismissed_at')
                    ->where('is_read', 'N'),
                'unread_notifications',
            )
            ->first();

        return response()->json([
            'pending_comment_reports' => (int) $counts->pending_comment_reports,
            'pending_feedback' => (int) $counts->pending_feedback,
            'unread_notifications' => (int) $counts->unread_notifications,
        ]);
    }
}
