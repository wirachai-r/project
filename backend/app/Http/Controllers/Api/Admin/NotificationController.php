<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NotificationRequest;
use App\Http\Resources\Admin\NotificationResource;
use App\Models\Notification;
use App\Models\User;
use App\Support\AdminTableQuery;
use App\Support\NotificationContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @tags Admin NotificationController
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::query()
            ->with('user:user_id,first_name,last_name,email')
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->is_read, fn ($q) => $q->where('is_read', $request->is_read))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch($q, $request->search, 'id', ['title', 'body']))
            ->orderBy('created_at', $request->sort_direction === 'asc' ? 'asc' : 'desc')
            ->paginate((int) ($request->per_page ?? 20));

        return NotificationResource::collection($notifications);
    }

    public function store(NotificationRequest $request)
    {
        $body = NotificationContent::normalizeImageUrls($request->body);

        if ($request->input('audience', 'individual') === 'all') {
            $recipientCount = DB::transaction(function () use ($request, $body) {
                $recipientCount = 0;
                $now = now();

                User::query()
                    ->select('user_id')
                    ->orderBy('user_id')
                    ->chunk(500, function ($users) use ($request, $body, $now, &$recipientCount) {
                        $notifications = $users->map(fn (User $user) => [
                            'title' => $request->title,
                            'body' => $body,
                            'type' => $request->type ?? 'S',
                            'is_read' => 'N',
                            'user_id' => $user->user_id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])->all();

                        if ($notifications !== []) {
                            Notification::insert($notifications);
                            $recipientCount += count($notifications);
                        }
                    });

                return $recipientCount;
            });

            return response()->json([
                'message' => 'ส่งการแจ้งเตือนให้ผู้ใช้ทั้งหมดแล้ว',
                'recipient_count' => $recipientCount,
            ], 201);
        }

        $notification = Notification::create([
            'title' => $request->title,
            'body' => $body,
            'type' => $request->type ?? 'S',
            'is_read' => 'N',
            'user_id' => $request->user_id,
        ]);

        return new NotificationResource($notification->load('user:user_id,first_name,last_name,email'));
    }

    public function show(Notification $notification)
    {
        return new NotificationResource($notification);
    }

    public function update(NotificationRequest $request, Notification $notification)
    {
        $notification->update([
            'title' => $request->title,
            'body' => NotificationContent::normalizeImageUrls($request->body),
            'type' => $request->type ?? $notification->type,
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
