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

    public function test_persistent_campaign_is_delivered_to_a_user_created_later(): void
    {
        $admin = $this->createUser('ADM000001', 'admin@example.com', 'Admin');

        $this->actingAs($admin)->postJson('/api/admin/notifications', [
            'title' => 'Welcome', 'body' => 'Welcome message', 'audience' => 'all',
            'channels' => ['in_app'], 'is_persistent' => true,
        ])->assertCreated();

        $newUser = $this->createUser('USR000003', 'new@example.com');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $newUser->user_id, 'title' => 'Welcome',
        ]);
    }

    public function test_admin_can_schedule_and_cancel_a_campaign(): void
    {
        $admin = $this->createUser('ADM000001', 'admin@example.com', 'Admin');
        $response = $this->actingAs($admin)->postJson('/api/admin/notifications', [
            'title' => 'Later', 'body' => 'Scheduled message', 'audience' => 'all',
            'channels' => ['in_app'], 'scheduled_at' => now()->addHour()->toIso8601String(),
        ])->assertCreated()->assertJsonPath('data.status', 'scheduled');

        $this->actingAs($admin)
            ->postJson('/api/admin/notifications/'.$response->json('data.id').'/cancel')
            ->assertOk()->assertJsonPath('data.status', 'cancelled');
    }

    public function test_admin_notification_index_keeps_the_standard_pagination_contract(): void
    {
        $admin = $this->createUser('ADM000001', 'admin@example.com', 'Admin');

        $this->actingAs($admin)->getJson('/api/admin/notifications')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
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
