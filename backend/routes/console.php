<?php

use App\Models\NotificationCampaign;
use App\Services\NotificationCampaignService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('health-reminders:send-due')->everyMinute()->withoutOverlapping();
Schedule::call(function () {
    NotificationCampaign::query()->where('status', 'scheduled')->where('scheduled_at', '<=', now())->each(fn (NotificationCampaign $campaign) => app(NotificationCampaignService::class)->dispatch($campaign));
})->name('dispatch-scheduled-notification-campaigns')->everyMinute()->withoutOverlapping();
Schedule::call(fn () => DB::table('ai_clarification_sessions')
    ->whereNotNull('expires_at')
    ->where('expires_at', '<=', now())
    ->delete())
    ->name('prune-ai-clarification-sessions')
    ->daily()
    ->withoutOverlapping();
