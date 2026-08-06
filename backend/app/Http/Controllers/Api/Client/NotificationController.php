<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

/**
 * @tags Client NotificationController
 */

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::query()
            ->where('user_id', $request->user()->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return NotificationResource::collection($notifications);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::query()
            ->where('user_id', $request->user()->user_id)
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
            ->where('is_read', 'N')
            ->update([
                'is_read' => 'Y',
                'read_at' => now(),
            ]);

        return response()->json(['message' => 'อ่านการแจ้งเตือนทั้งหมดแล้ว']);
    }
}
