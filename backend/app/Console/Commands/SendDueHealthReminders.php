<?php

namespace App\Console\Commands;

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

                        Notification::create([
                            'user_id' => $locked->user_id,
                            'title' => $locked->title,
                            'body' => 'ถึงเวลาบันทึกและติดตามสุขภาพของคุณแล้ว',
                            'type' => 'U',
                            'is_read' => 'N',
                        ]);
                        $locked->last_sent_at = now();
                        $locked->next_run_at = $locked->calculateNextRun();
                        $locked->save();
                    });
                }
            });

        return self::SUCCESS;
    }
}
