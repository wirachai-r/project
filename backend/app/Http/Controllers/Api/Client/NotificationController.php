<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\NotificationResource;
use App\Models\Notification;
use App\Services\HealthReminderDispatchService;
use Illuminate\Http\Request;

/**
 * @tags Client NotificationController
 */
class NotificationController extends Controller
{
    public function index(Request $request, HealthReminderDispatchService $reminders)
    {
        // Catch up due reminders when the app opens this page even if the
        // infrastructure scheduler missed a minute. The dispatcher is
        // idempotent per reminder target and local calendar date.
        $reminders->dispatchDue($request->user()->user_id);

        $notifications = Notification::query()->with('campaign:id,target_url,expires_at')->where('visible_in_app', true)
            ->where(fn ($query) => $query->whereNull('campaign_id')->orWhereHas('campaign', fn ($campaign) => $campaign->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->where('user_id', $request->user()->user_id)
            ->whereNull('dismissed_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return NotificationResource::collection($notifications);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::query()
            ->where('user_id', $request->user()->user_id)
            ->where('visible_in_app', true)
            ->where(fn ($query) => $query->whereNull('campaign_id')->orWhereHas('campaign', fn ($campaign) => $campaign->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->whereNull('dismissed_at')
            ->where('is_read', 'N')
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        abort_if($notification->user_id !== $request->user()->user_id, 403);

        $notification->update([
            'is_read' => 'Y',
            'read_at' => now(),
        ]);

        return new NotificationResource($notification);
    }

    public function markAllAsRead(Request $request)
    {
        Notification::query()
            ->where('user_id', $request->user()->user_id)
            ->where('visible_in_app', true)
            ->where(fn ($query) => $query->whereNull('campaign_id')->orWhereHas('campaign', fn ($campaign) => $campaign->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->whereNull('dismissed_at')
            ->where('is_read', 'N')
            ->update([
                'is_read' => 'Y',
                'read_at' => now(),
            ]);

        return response()->json(['message' => 'อ่านการแจ้งเตือนทั้งหมดแล้ว']);
    }

    public function dismiss(Request $request, Notification $notification)
    {
        abort_if($notification->user_id !== $request->user()->user_id, 403);

        $notification->update(['dismissed_at' => now()]);

        return response()->json(['message' => 'นำการแจ้งเตือนออกแล้ว']);
    }

    public function restore(Request $request, Notification $notification)
    {
        abort_if($notification->user_id !== $request->user()->user_id, 403);

        $notification->update(['dismissed_at' => null]);

        return new NotificationResource($notification);
    }
}
