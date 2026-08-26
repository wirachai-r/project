<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrivacyCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_export_their_personal_data(): void
    {
        $user = $this->user();
        DB::table('daily_health_records')->insert([
            'user_id' => $user->user_id,
            'recorded_on' => '2026-08-15',
            'status' => 'better',
            'note' => 'ทดสอบ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/api/privacy/export');

        $response->assertOk()
            ->assertHeader('content-type', 'application/json; charset=UTF-8')
            ->assertDownload();

        $payload = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($user->email, $payload['profile']['email']);
        $this->assertSame('better', $payload['daily_health_records'][0]['status']);
        $this->assertArrayNotHasKey('password', $payload['profile']);
    }

    public function test_delete_account_rejects_an_incorrect_password(): void
    {
        $user = $this->user();

        $this->actingAs($user)->deleteJson('/api/privacy/account', [
            'current_password' => 'incorrect',
            'confirmation' => 'DELETE',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->assertNull($user->fresh()->deleted_at);
    }

    public function test_user_can_soft_delete_account_and_revoke_every_token(): void
    {
        $user = $this->user();
        $user->createToken('phone');
        $user->createToken('tablet');

        $this->actingAs($user)->deleteJson('/api/privacy/account', [
            'current_password' => 'old-password',
            'confirmation' => 'DELETE',
        ])->assertOk();

        $this->assertSoftDeleted('users', ['user_id' => $user->user_id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    private function user(): User
    {
        return User::create([
            'user_id' => '000000001',
            'first_name' => 'Privacy',
            'last_name' => 'User',
            'email' => 'privacy@example.com',
            'password' => 'old-password',
        ]);
    }
}
