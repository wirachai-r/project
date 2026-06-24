<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NotificationRequest;
use App\Http\Resources\Admin\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

/**
 * @tags Admin NotificationController
 */

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::query()
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->is_read, fn($q) => $q->where('is_read', $request->is_read))
            ->when($request->search, fn($q) => $q->where('title', 'like', '%'.$request->search.'%'))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return NotificationResource::collection($notifications);
    }

    public function store(NotificationRequest $request)
    {
        $notification = Notification::create([
            'title'   => $request->title,
            'body'    => $request->body,
            'type'    => $request->type ?? 'S',
            'is_read' => 'N',
            'user_id' => $request->user_id,
        ]);

        return new NotificationResource($notification);
    }

    public function show(Notification $notification)
    {
        return new NotificationResource($notification);
    }

    public function update(NotificationRequest $request, Notification $notification)
    {
        $notification->update([
            'title' => $request->title,
            'body'  => $request->body,
            'type'  => $request->type ?? $notification->type,
        ]);

        return new NotificationResource($notification);
    }

    public function markAsRead(Notification $notification)
    {
        $notification->update([
            'is_read' => 'Y',
            'read_at' => now(),
        ]);

        return new NotificationResource($notification);
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return response()->json(['message' => 'ลบการแจ้งเตือนสำเร็จ']);
    }
}
