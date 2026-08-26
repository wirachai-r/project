<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationDismissTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_dismiss_and_restore_a_notification_without_deleting_it(): void
    {
        $user = User::create([
            'user_id' => '000000001',
            'first_name' => 'Notification',
            'last_name' => 'User',
            'email' => 'notification@example.com',
            'password' => 'password',
        ]);
        $token = $user->createToken('test')->plainTextToken;
        $notification = Notification::create([
            'user_id' => $user->user_id,
            'title' => 'ทดสอบ',
            'body' => 'ข้อความทดสอบ',
            'type' => 'S',
            'is_read' => 'N',
        ]);

        $this->withToken($token)
            ->patchJson("/api/notifications/{$notification->id}/dismiss")
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
        ]);
        $this->assertNotNull($notification->fresh()->dismissed_at);

        $this->withToken($token)
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->withToken($token)
            ->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJson(['unread_count' => 0]);

        $this->withToken($token)
            ->patchJson("/api/notifications/{$notification->id}/restore")
            ->assertOk();

        $this->assertNull($notification->fresh()->dismissed_at);
    }
}
