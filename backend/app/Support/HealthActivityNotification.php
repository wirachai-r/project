<?php

namespace App\Support;

use App\Models\Notification;

class HealthActivityNotification
{
    public static function create(
        string $userId,
        string $title,
        string $body,
        string $targetType,
        string|int $targetId,
        ?string $dedupeKey = null,
        ?string $targetDate = null,
    ): Notification {
        $attributes = [
            'user_id' => $userId,
            'target_type' => $targetType,
            'target_id' => (string) $targetId,
        ];

        if ($dedupeKey !== null) {
            $existing = Notification::query()
                ->where($attributes)
                ->whereDate('created_at', $dedupeKey)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        return Notification::create($attributes + [
            'title' => $title,
            'body' => $body,
            'target_date' => $targetDate,
            'type' => 'U',
            'delivery_status' => 'sent',
            'delivered_at' => now(),
            'visible_in_app' => true,
        ]);
    }
}
