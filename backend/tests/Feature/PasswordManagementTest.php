<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_sends_otp_without_revealing_unknown_emails(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();
        $this->postJson('/api/auth/forgot-password', ['email' => 'missing@example.com'])->assertOk();

        Notification::assertSentTo($user, PasswordResetOtpNotification::class);
        $this->assertDatabaseHas('password_reset_otps', ['email' => $user->email, 'attempts' => 0]);
    }

    public function test_valid_otp_returns_short_lived_reset_token(): void
    {
        $user = $this->user();
        $this->otp($user->email, '123456');

        $response = $this->postJson('/api/auth/verify-password-otp', [
            'email' => $user->email,
            'otp' => '123456',
        ])->assertOk()->assertJsonStructure(['reset_token']);

        $this->assertSame(64, strlen($response->json('reset_token')));
    }

    public function test_otp_is_locked_after_five_incorrect_attempts(): void
    {
        $user = $this->user();
        $this->otp($user->email, '123456');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/verify-password-otp', [
                'email' => $user->email,
                'otp' => '654321',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/auth/verify-password-otp', [
            'email' => $user->email,
            'otp' => '123456',
        ])->assertUnprocessable();
    }

    public function test_verified_otp_can_reset_password_once_and_revoke_tokens(): void
    {
        $user = $this->user();
        $user->createToken('phone');
        $this->otp($user->email, '123456');
        $resetToken = $this->postJson('/api/auth/verify-password-otp', [
            'email' => $user->email,
            'otp' => '123456',
        ])->json('reset_token');

        $payload = [
            'email' => $user->email,
            'reset_token' => $resetToken,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];
        $this->postJson('/api/auth/reset-password', $payload)->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->postJson('/api/auth/reset-password', $payload)->assertUnprocessable();
    }

    public function test_authenticated_user_can_change_password_and_revoke_other_tokens(): void
    {
        $user = $this->user();
        $currentToken = $user->createToken('current');
        $user->createToken('other');

        $this->withToken($currentToken->plainTextToken)->putJson('/api/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    private function otp(string $email, string $otp): void
    {
        DB::table('password_reset_otps')->insert([
            'email' => $email,
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function user(): User
    {
        return User::create([
            'user_id' => '000000001',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@example.com',
            'password' => 'old-password',
        ]);
    }
}
