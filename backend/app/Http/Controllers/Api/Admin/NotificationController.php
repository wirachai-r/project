<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NotificationRequest;
use App\Models\NotificationCampaign;
use App\Services\NotificationCampaignService;
use App\Support\AdminTableQuery;
use App\Support\NotificationContent;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $rows = NotificationCampaign::query()->tap(fn ($q) => AdminTableQuery::fuzzySearch($q, $request->search, 'id', ['title', 'body']))->when($request->type, fn ($q, $v) => $q->where('type', $v))->when($request->status, fn ($q, $v) => $q->where('status', $v))->orderBy('created_at', $request->sort_direction === 'asc' ? 'asc' : 'desc')->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json([
            'data' => collect($rows->items())->map(fn ($campaign) => $this->serialize($campaign))->values(),
            'links' => [
                'first' => $rows->url(1),
                'last' => $rows->url($rows->lastPage()),
                'prev' => $rows->previousPageUrl(),
                'next' => $rows->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $rows->currentPage(),
                'from' => $rows->firstItem(),
                'last_page' => $rows->lastPage(),
                'path' => $rows->path(),
                'per_page' => $rows->perPage(),
                'to' => $rows->lastItem(),
                'total' => $rows->total(),
            ],
        ]);
    }

    public function store(NotificationRequest $request, NotificationCampaignService $service)
    {
        $d = $request->validated();
        $filter = match ($d['audience']) {
            'individual' => ['user_ids' => $d['user_ids'] ?? [$d['user_id']]],'group' => array_filter($d['audience_filter'] ?? []),default => null
        };
        $scheduled = $d['scheduled_at'] ?? null;
        $campaign = NotificationCampaign::create(['title' => $d['title'], 'body' => NotificationContent::normalizeImageUrls($d['body']), 'type' => $d['type'] ?? 'S', 'audience' => $d['audience'], 'audience_filter' => $filter, 'channels' => $d['channels'], 'target_url' => $d['target_url'] ?? null, 'is_persistent' => $d['is_persistent'] ?? false, 'starts_at' => $d['starts_at'] ?? null, 'expires_at' => $d['expires_at'] ?? null, 'scheduled_at' => $scheduled, 'status' => $scheduled && now()->lt($scheduled) ? 'scheduled' : 'queued', 'created_by' => $request->user()?->user_id]);
        if ($campaign->status === 'queued') {
            $service->dispatch($campaign);
        } $campaign->refresh();

        return response()->json(['data' => $this->serialize($campaign), 'recipient_count' => $campaign->recipient_count, 'message' => $campaign->status === 'scheduled' ? 'ตั้งเวลาการแจ้งเตือนแล้ว' : 'ส่งการแจ้งเตือนแล้ว'], 201);
    }

    public function show(NotificationCampaign $notification, NotificationCampaignService $service)
    {
        $service->refreshStats($notification);

        return response()->json(['data' => $this->serialize($notification)]);
    }

    public function update(NotificationRequest $request, NotificationCampaign $notification)
    {
        abort_unless(in_array($notification->status, ['draft', 'scheduled'], true), 422, 'แก้ไขได้เฉพาะฉบับร่างหรือรายการที่ตั้งเวลา');
        $data = $request->validated();
        $data['body'] = NotificationContent::normalizeImageUrls($data['body']);
        $data['audience_filter'] = match ($data['audience']) {
            'individual' => ['user_ids' => $data['user_ids'] ?? [$data['user_id']]],
            'group' => array_filter($data['audience_filter'] ?? []),
            default => null,
        };
        unset($data['user_id'], $data['user_ids']);
        $notification->update($data);

        return response()->json(['data' => $this->serialize($notification->fresh())]);
    }

    public function destroy(NotificationCampaign $notification)
    {
        abort_if(in_array($notification->status, ['sent', 'partially_failed', 'cancelled'], true), 422, 'รายการที่ส่งแล้วไม่สามารถลบได้');
        $notification->delete();

        return response()->json(['message' => 'ลบรายการแล้ว']);
    }

    public function cancel(NotificationCampaign $notification)
    {
        abort_if(in_array($notification->status, ['sent', 'partially_failed', 'cancelled'], true), 422, 'ไม่สามารถยกเลิกรายการนี้ได้');
        $notification->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return response()->json(['data' => $this->serialize($notification)]);
    }

    public function retry(NotificationCampaign $notification, NotificationCampaignService $service)
    {
        abort_unless($notification->status === 'partially_failed', 422, 'ไม่มีรายการที่ส่งล้มเหลว');
        $service->retryFailed($notification);

        return response()->json(['data' => $this->serialize($notification->fresh())]);
    }

    private function serialize(NotificationCampaign $c): array
    {
        return ['id' => $c->id, 'title' => $c->title, 'body' => NotificationContent::resolveImageUrls($c->body, request()), 'body_text' => trim(html_entity_decode(strip_tags($c->body))), 'type' => $c->type, 'audience' => $c->audience, 'audience_filter' => $c->audience_filter, 'channels' => $c->channels, 'target_url' => $c->target_url, 'status' => $c->status, 'is_persistent' => $c->is_persistent, 'starts_at' => $c->starts_at, 'expires_at' => $c->expires_at, 'scheduled_at' => $c->scheduled_at, 'sent_at' => $c->sent_at, 'recipient_count' => $c->recipient_count, 'sent_count' => $c->sent_count, 'failed_count' => $c->failed_count, 'read_count' => $c->read_count, 'created_at' => $c->created_at];
    }
}
