<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_sessions_and_identify_current_session(): void
    {
        $user = $this->user();
        $current = $user->createToken('auth_token');
        $current->accessToken->update(['device_name' => 'โทรศัพท์ของฉัน', 'device_type' => 'android']);
        $other = $user->createToken('auth_token');
        $other->accessToken->update(['device_name' => 'เว็บ', 'device_type' => 'web']);

        $response = $this->withToken($current->plainTextToken)
            ->getJson('/api/sessions')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $sessions = collect($response->json('data'));
        $this->assertTrue($sessions->firstWhere('id', $current->accessToken->id)['is_current']);
        $this->assertFalse($sessions->firstWhere('id', $other->accessToken->id)['is_current']);
    }

    public function test_user_can_revoke_another_session(): void
    {
        $user = $this->user();
        $current = $user->createToken('current');
        $other = $user->createToken('other');

        $this->withToken($current->plainTextToken)
            ->deleteJson("/api/sessions/{$other->accessToken->id}")
            ->assertOk()
            ->assertJson(['current_session_revoked' => false]);

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $current->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $other->accessToken->id]);
    }

    public function test_user_cannot_revoke_another_users_session(): void
    {
        $user = $this->user();
        $otherUser = $this->user('000000002', 'other@example.com');
        $current = $user->createToken('current');
        $foreign = $otherUser->createToken('foreign');

        $this->withToken($current->plainTextToken)
            ->deleteJson("/api/sessions/{$foreign->accessToken->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $foreign->accessToken->id]);
    }

    public function test_revoke_others_keeps_current_session(): void
    {
        $user = $this->user();
        $current = $user->createToken('current');
        $user->createToken('other-one');
        $user->createToken('other-two');

        $this->withToken($current->plainTextToken)
            ->deleteJson('/api/sessions/others')
            ->assertOk()
            ->assertJson(['revoked_count' => 2]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $current->accessToken->id]);
    }

    private function user(string $id = '000000001', string $email = 'session@example.com'): User
    {
        return User::create([
            'user_id' => $id,
            'first_name' => 'Session',
            'last_name' => 'User',
            'email' => $email,
            'password' => 'old-password',
        ]);
    }
}
