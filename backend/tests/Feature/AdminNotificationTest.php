<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_a_notification_to_all_users(): void
    {
        $admin = $this->createUser('ADM000001', 'admin@example.com', 'Admin');
        $firstUser = $this->createUser('USR000001', 'first@example.com');
        $secondUser = $this->createUser('USR000002', 'second@example.com');

        $response = $this->actingAs($admin)->postJson('/api/admin/notifications', [
            'title' => 'แจ้งเตือนระบบ',
            'body' => 'เนื้อหาการแจ้งเตือน',
            'type' => 'S',
            'audience' => 'all',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('recipient_count', 3);

        $this->assertSame(3, Notification::query()->count());
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->user_id, 'is_read' => 'N']);
        $this->assertDatabaseHas('notifications', ['user_id' => $firstUser->user_id, 'is_read' => 'N']);
        $this->assertDatabaseHas('notifications', ['user_id' => $secondUser->user_id, 'is_read' => 'N']);
    }

    public function test_individual_notification_still_requires_a_user(): void
    {
        $admin = $this->createUser('ADM000001', 'admin@example.com', 'Admin');

        $this->actingAs($admin)->postJson('/api/admin/notifications', [
            'title' => 'แจ้งเตือนระบบ',
            'body' => 'เนื้อหาการแจ้งเตือน',
            'audience' => 'individual',
        ])->assertUnprocessable()->assertJsonValidationErrors('user_id');
    }

    private function createUser(string $userId, string $email, string $role = 'User'): User
    {
        return User::query()->create([
            'user_id' => $userId,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => '1',
        ]);
    }
}
