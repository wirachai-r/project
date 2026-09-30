<?php

namespace App\Services;

use App\Models\DailyHealthRecord;
use App\Models\FollowUpEntry;
use App\Models\HealthEpisode;
use App\Models\HealthReminder;
use App\Models\Notification;
use App\Support\HealthTime;
use Illuminate\Support\Facades\DB;

class HealthReminderDispatchService
{
    public function dispatchDue(?string $userId = null): int
    {
        $created = 0;

        HealthReminder::query()
            ->where('is_enabled', true)
            ->where('next_run_at', '<=', now())
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->orderBy('id')
            ->chunkById(100, function ($reminders) use (&$created): void {
                foreach ($reminders as $reminder) {
                    $created += DB::transaction(fn (): int => $this->dispatchLocked($reminder->id));
                }
            });

        return $created;
    }

    private function dispatchLocked(int $reminderId): int
    {
        $reminder = HealthReminder::query()
            ->with('healthEpisode')
            ->lockForUpdate()
            ->find($reminderId);

        if (! $reminder || ! $reminder->is_enabled || $reminder->next_run_at?->isFuture()) {
            return 0;
        }

        $timezone = $reminder->timezone ?: HealthTime::TIMEZONE;
        $today = now('UTC')->setTimezone($timezone)->toDateString();
        [$todayStartUtc, $todayEndUtc] = HealthTime::utcRange($today, $today, $timezone);
        $todayCheckInCount = DailyHealthRecord::query()
            ->where('user_id', $reminder->user_id)
            ->whereDate('recorded_on', $today)
            ->count();
        $activeEpisodeCount = HealthEpisode::query()
            ->where('user_id', $reminder->user_id)
            ->where('status', 'A')
            ->count();
        $followUpCompleted = $reminder->reminder_type === 'follow_up'
            && FollowUpEntry::query()
                ->whereHas('episodeSymptom', fn ($query) => $query
                    ->where('health_episode_id', $reminder->health_episode_id))
                ->whereBetween('recorded_at', [$todayStartUtc, $todayEndUtc])
                ->exists();
        $followUpUnavailable = $reminder->reminder_type === 'follow_up'
            && (! $reminder->healthEpisode || $reminder->healthEpisode->status !== 'A');
        $alreadyCompleted = $reminder->reminder_type === 'daily_record'
            ? $todayCheckInCount > 0
            : $followUpCompleted || $followUpUnavailable;

        $body = match (true) {
            $reminder->reminder_type === 'follow_up' && ! $reminder->healthEpisode => 'ไม่พบรายการติดตามอาการที่เชื่อมกับการแจ้งเตือนนี้',
            $reminder->reminder_type === 'follow_up' && $reminder->healthEpisode->status !== 'A' => 'รายการติดตามอาการนี้สิ้นสุดแล้ว',
            $followUpCompleted => 'วันนี้คุณบันทึกการเปลี่ยนแปลงของอาการนี้แล้ว',
            $reminder->reminder_type === 'follow_up' => 'ถึงเวลาบันทึกการเปลี่ยนแปลงของอาการวันนี้',
            $todayCheckInCount > 0 => "วันนี้คุณ Check-in แล้ว {$todayCheckInCount} ครั้ง และยังบันทึกเพิ่มได้",
            $activeEpisodeCount > 0 => "คุณมี {$activeEpisodeCount} รายการที่กำลังติดตาม และยังไม่ได้ Check-in วันนี้",
            default => 'ใช้เวลาสั้นๆ เพื่อบันทึกว่าตอนนี้คุณรู้สึกอย่างไร',
        };

        $targetType = $reminder->reminder_type === 'follow_up'
            ? 'health_episode'
            : 'daily_health_record';
        $targetId = $reminder->reminder_type === 'follow_up'
            ? (string) $reminder->health_episode_id
            : (string) $reminder->id;

        // last_sent_at is cleared when the user changes the reminder schedule.
        // In that case the newly selected time is a distinct occurrence even
        // when an older notification already exists for the same local date.
        $notification = $reminder->last_sent_at === null
            ? null
            : Notification::query()
                ->where('user_id', $reminder->user_id)
                ->where('target_type', $targetType)
                ->where('target_id', $targetId)
                ->whereDate('target_date', $today)
                ->first();
        if (! $notification) {
            $notification = Notification::create([
                'user_id' => $reminder->user_id,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'target_date' => $today,
                'title' => $reminder->title,
                'body' => $body,
                'type' => 'U',
                // Keep an idempotency/audit record in the database, but do not
                // surface a reminder after the user already completed the task.
                'is_read' => $alreadyCompleted ? 'Y' : 'N',
                'read_at' => $alreadyCompleted ? now() : null,
                'delivery_status' => $alreadyCompleted ? 'skipped' : 'sent',
                'delivery_error' => $alreadyCompleted ? 'Task already completed for the local date' : null,
                'delivered_at' => $alreadyCompleted ? null : now(),
                'visible_in_app' => ! $alreadyCompleted,
            ]);
        }

        $reminder->last_sent_at = now();
        $reminder->next_run_at = $reminder->calculateNextRun();
        $reminder->save();

        return $notification->wasRecentlyCreated ? 1 : 0;
    }
}
