<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class NotificationCampaignService
{
    public function dispatch(NotificationCampaign $campaign): void
    {
        if (in_array($campaign->status, ['processing', 'sent', 'partially_failed', 'cancelled'], true)) {
            return;
        }

        $campaign->update(['status' => 'processing']);
        $query = $this->recipients($campaign);

        DB::transaction(function () use ($campaign, $query) {
            $query->select('user_id')->orderBy('user_id')->chunk(500, function ($users) use ($campaign) {
                foreach ($users as $user) {
                    $this->deliverToUser($campaign, $user);
                }
            });
        });

        $this->refreshStats($campaign);
        $campaign->update([
            'status' => $campaign->failed_count > 0 ? 'partially_failed' : 'sent',
            'sent_at' => now(),
        ]);
    }

    public function deliverPersistentTo(User $user): void
    {
        NotificationCampaign::query()
            ->where('is_persistent', true)
            ->whereIn('status', ['sent', 'partially_failed'])
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->each(fn (NotificationCampaign $campaign) => $this->matches($campaign, $user)
                ? $this->deliverToUser($campaign, $user)
                : null);
    }

    public function retryFailed(NotificationCampaign $campaign): void
    {
        $campaign->notifications()
            ->with('user')
            ->where('delivery_status', 'failed')
            ->each(function (Notification $notification) use ($campaign) {
                $sent = $notification->user
                    && app(FcmPushService::class)->sendToUser($notification->user, $campaign);
                $notification->update([
                    'delivery_status' => $sent ? 'sent' : 'failed',
                    'delivery_error' => $sent ? null : 'ไม่พบอุปกรณ์หรือส่ง Push Notification ไม่สำเร็จ',
                    'delivered_at' => $sent ? now() : null,
                ]);
            });
        $this->refreshStats($campaign);
        $campaign->update(['status' => $campaign->failed_count > 0 ? 'partially_failed' : 'sent']);
    }

    public function refreshStats(NotificationCampaign $campaign): void
    {
        $campaign->update([
            'recipient_count' => $campaign->notifications()->count(),
            'sent_count' => $campaign->notifications()->whereIn('delivery_status', ['sent', 'delivered'])->count(),
            'failed_count' => $campaign->notifications()->where('delivery_status', 'failed')->count(),
            'read_count' => $campaign->notifications()->where('is_read', 'Y')->count(),
        ]);
        $campaign->refresh();
    }

    private function deliverToUser(NotificationCampaign $campaign, User $user): void
    {
        $channels = $campaign->channels ?? ['in_app'];
        $notification = Notification::query()->firstOrCreate(
            ['campaign_id' => $campaign->id, 'user_id' => $user->user_id],
            [
                'title' => $campaign->title, 'body' => $campaign->body, 'type' => $campaign->type,
                'is_read' => 'N', 'delivery_status' => 'sent', 'delivered_at' => now(),
                'visible_in_app' => in_array('in_app', $channels, true),
            ]
        );

        if (in_array('push', $channels, true)) {
            $sent = app(FcmPushService::class)->sendToUser($user, $campaign);
            $notification->update([
                'delivery_status' => $sent ? 'sent' : 'failed',
                'delivery_error' => $sent ? null : 'ไม่พบอุปกรณ์หรือส่ง Push Notification ไม่สำเร็จ',
                'delivered_at' => $sent ? now() : null,
            ]);
        }
    }

    private function recipients(NotificationCampaign $campaign): Builder
    {
        $query = User::query();
        if ($campaign->audience === 'individual') {
            return $query->whereIn('user_id', $campaign->audience_filter['user_ids'] ?? []);
        }
        if ($campaign->audience === 'group') {
            $filter = $campaign->audience_filter ?? [];
            $query->when($filter['role'] ?? null, fn ($q, $role) => $q->where('role', $role));
            $query->when($filter['status'] ?? null, fn ($q, $status) => $q->where('status', $status));
        }

        return $query;
    }

    private function matches(NotificationCampaign $campaign, User $user): bool
    {
        if ($campaign->audience === 'all') {
            return true;
        }
        $filter = $campaign->audience_filter ?? [];
        if ($campaign->audience === 'individual') {
            return in_array($user->user_id, $filter['user_ids'] ?? [], true);
        }

        return (! isset($filter['role']) || $filter['role'] === $user->role)
            && (! isset($filter['status']) || $filter['status'] === $user->status);
    }
}
