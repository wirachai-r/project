<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthReminderDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_reminder_always_creates_an_in_app_notification(): void
    {
        Carbon::setTestNow('2026-09-29 10:30:00');
        [$user, $reminderId] = $this->fixtureDueReminder();

        $this->artisan('health-reminders:send-due')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->user_id,
            'target_type' => 'daily_health_record',
            'target_id' => (string) $reminderId,
            'target_date' => '2026-09-29 00:00:00',
            'is_read' => 'N',
            'visible_in_app' => true,
        ]);
        $this->assertNotNull(DB::table('health_reminders')->where('id', $reminderId)->value('last_sent_at'));
        $this->assertTrue(
            Carbon::parse(DB::table('health_reminders')->where('id', $reminderId)->value('next_run_at'))->isFuture(),
        );
    }

    public function test_dispatch_is_idempotent_for_the_same_reminder_day(): void
    {
        Carbon::setTestNow('2026-09-29 10:30:00');
        [$user, $reminderId] = $this->fixtureDueReminder();

        $this->artisan('health-reminders:send-due')->assertSuccessful();
        DB::table('health_reminders')->where('id', $reminderId)->update(['next_run_at' => now()->subMinute()]);
        $this->artisan('health-reminders:send-due')->assertSuccessful();

        $this->assertSame(1, DB::table('notifications')
            ->where('user_id', $user->user_id)
            ->where('target_type', 'daily_health_record')
            ->where('target_id', (string) $reminderId)
            ->whereDate('target_date', '2026-09-29')
            ->count());
    }

    public function test_changing_time_after_delivery_allows_another_notification_today(): void
    {
        Carbon::setTestNow('2026-09-29 10:30:00');
        [$user, $reminderId] = $this->fixtureDueReminder();

        $this->artisan('health-reminders:send-due')->assertSuccessful();

        Carbon::setTestNow('2026-09-29 10:35:00');
        $this->actingAs($user)->putJson("/api/health-reminders/{$reminderId}", [
            'title' => 'บันทึกสุขภาพประจำวัน',
            'reminder_type' => 'daily_record',
            'frequency' => 'daily',
            'time_of_day' => '17:40',
            'timezone' => 'Asia/Bangkok',
            'is_enabled' => true,
        ])->assertOk();

        $this->assertNull(DB::table('health_reminders')->where('id', $reminderId)->value('last_sent_at'));

        Carbon::setTestNow('2026-09-29 10:41:00');
        $this->artisan('health-reminders:send-due')->assertSuccessful();

        $this->assertSame(2, DB::table('notifications')
            ->where('user_id', $user->user_id)
            ->where('target_type', 'daily_health_record')
            ->where('target_id', (string) $reminderId)
            ->whereDate('target_date', '2026-09-29')
            ->count());
    }

    public function test_due_reminder_is_recorded_even_when_health_check_in_is_already_complete(): void
    {
        Carbon::setTestNow('2026-09-29 10:30:00');
        [$user, $reminderId] = $this->fixtureDueReminder();
        DB::table('daily_health_records')->insert([
            'user_id' => $user->user_id,
            'recorded_on' => '2026-09-29',
            'recorded_at' => now(),
            'status' => 'good',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('health-reminders:send-due')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->user_id,
            'target_type' => 'daily_health_record',
            'target_id' => (string) $reminderId,
            'body' => 'วันนี้คุณ Check-in แล้ว 1 ครั้ง และยังบันทึกเพิ่มได้',
            'is_read' => 'Y',
            'delivery_status' => 'skipped',
            'visible_in_app' => false,
        ]);

        $this->actingAs($user)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_notification_index_catches_up_a_due_reminder_when_scheduler_missed_it(): void
    {
        Carbon::setTestNow('2026-09-29 10:30:00');
        [$user, $reminderId] = $this->fixtureDueReminder();

        $this->actingAs($user)->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.target_type', 'daily_health_record')
            ->assertJsonPath('data.0.target_id', (string) $reminderId);

        $this->assertDatabaseCount('notifications', 1);
    }

    private function fixtureDueReminder(): array
    {
        $user = User::create([
            'user_id' => '000000001',
            'first_name' => 'Reminder',
            'last_name' => 'User',
            'email' => 'reminder@example.test',
            'password' => 'password',
        ]);
        $reminderId = DB::table('health_reminders')->insertGetId([
            'user_id' => $user->user_id,
            'title' => 'บันทึกสุขภาพประจำวัน',
            'reminder_type' => 'daily_record',
            'frequency' => 'daily',
            'time_of_day' => '17:25',
            'timezone' => 'Asia/Bangkok',
            'is_enabled' => true,
            'next_run_at' => now()->subMinute(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $reminderId];
    }
}
