<?php

namespace App\Console\Commands;

use App\Models\DailyHealthRecord;
use App\Models\FollowUpEntry;
use App\Models\HealthEpisode;
use App\Models\HealthReminder;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendDueHealthReminders extends Command
{
    protected $signature = 'health-reminders:send-due';

    protected $description = 'Create in-app notifications for due health reminders';

    public function handle(): int
    {
        HealthReminder::query()
            ->where('is_enabled', true)
            ->where('next_run_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($reminders): void {
                foreach ($reminders as $reminder) {
                    DB::transaction(function () use ($reminder): void {
                        $locked = HealthReminder::query()->lockForUpdate()->find($reminder->id);
                        if (! $locked || ! $locked->is_enabled || $locked->next_run_at?->isFuture()) {
                            return;
                        }

                        $activeEpisodeCount = HealthEpisode::query()
                            ->where('user_id', $locked->user_id)->where('status', 'A')->count();
                        $todayCheckInCount = DailyHealthRecord::query()
                            ->where('user_id', $locked->user_id)->whereDate('recorded_on', now()->toDateString())->count();
                        $alreadyCompleted = match ($locked->reminder_type) {
                            'daily_record' => $todayCheckInCount > 0,
                            'follow_up' => ! $locked->healthEpisode
                                || $locked->healthEpisode->status !== 'A'
                                || FollowUpEntry::query()
                                    ->whereHas('episodeSymptom', fn ($query) => $query->where('health_episode_id', $locked->health_episode_id))
                                    ->whereDate('recorded_at', now()->toDateString())
                                    ->exists(),
                            default => false,
                        };

                        if (! $alreadyCompleted) {
                            $body = match (true) {
                                $locked->reminder_type === 'follow_up' => 'ถึงเวลาบันทึกการเปลี่ยนแปลงของอาการวันนี้',
                                $activeEpisodeCount > 0 && $todayCheckInCount === 0 => "คุณมี {$activeEpisodeCount} รายการที่กำลังติดตาม และยังไม่ได้ Check-in วันนี้",
                                $activeEpisodeCount > 0 => "วันนี้คุณ Check-in แล้ว {$todayCheckInCount} ครั้ง และยังบันทึกการเปลี่ยนแปลงของอาการได้",
                                $todayCheckInCount > 0 => "วันนี้คุณ Check-in แล้ว {$todayCheckInCount} ครั้ง และยังบันทึกเพิ่มได้",
                                default => 'ใช้เวลาสั้นๆ เพื่อบันทึกว่าตอนนี้คุณรู้สึกอย่างไร',
                            };

                            Notification::create([
                                'user_id' => $locked->user_id,
                                'title' => $locked->title,
                                'body' => $body,
                                'type' => 'U',
                                'is_read' => 'N',
                            ]);
                        }
                        $locked->last_sent_at = now();
                        $locked->next_run_at = $locked->calculateNextRun();
                        $locked->save();
                    });
                }
            });

        return self::SUCCESS;
    }
}
