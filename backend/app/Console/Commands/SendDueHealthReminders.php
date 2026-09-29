<?php

namespace App\Console\Commands;

use App\Services\HealthReminderDispatchService;
use Illuminate\Console\Command;

class SendDueHealthReminders extends Command
{
    protected $signature = 'health-reminders:send-due';

    protected $description = 'Create in-app notifications for due health reminders';

    public function handle(HealthReminderDispatchService $dispatch): int
    {
        $dispatch->dispatchDue();

        return self::SUCCESS;
    }
}
