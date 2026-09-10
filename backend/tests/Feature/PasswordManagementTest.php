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

    public function test_request_sends_otp_only_for_an_existing_account(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJson([
                'expires_in' => 300,
                'resend_available_in' => 0,
            ]);
        $this->postJson('/api/auth/forgot-password', ['email' => 'missing@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        Notification::assertSentTo($user, PasswordResetOtpNotification::class);
        $this->assertDatabaseHas('password_reset_otps', ['email' => $user->email, 'attempts' => 0]);
    }

    public function test_request_enforces_a_resend_cooldown(): void
    {
        Notification::fake();
        $user = $this->user();

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJson(['resend_available_in' => 60]);
        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertTooManyRequests()
            ->assertJsonStructure(['retry_after']);
    }

    public function test_resend_replaces_the_old_password_otp_and_resets_attempts(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->otp($user->email, '123456', attempts: 4, createdAt: now()->subSeconds(61));

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJson(['expires_in' => 300, 'resend_available_in' => 60]);

        $record = DB::table('password_reset_otps')->where('email', $user->email)->first();
        $this->assertSame(0, $record->attempts);
        $this->assertFalse(Hash::check('123456', $record->otp_hash));
    }

    public function test_valid_otp_returns_short_lived_reset_token(): void
    {
        $user = $this->user();
        $this->otp($user->email, '004821');

        $response = $this->postJson('/api/auth/verify-password-otp', [
            'email' => $user->email,
            'otp' => '004821',
        ])->assertOk()->assertJsonStructure(['reset_token']);

        $this->assertSame(64, strlen($response->json('reset_token')));
        $this->postJson('/api/auth/verify-password-otp', [
            'email' => $user->email,
            'otp' => '004821',
        ])->assertUnprocessable()->assertJson([
            'message' => 'รหัส OTP นี้ถูกใช้งานแล้ว กรุณาขอรหัสใหม่',
        ]);
    }

    public function test_otp_is_locked_after_five_incorrect_attempts(): void
    {
        $user = $this->user();
        $this->otp($user->email, '123456');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->postJson('/api/auth/verify-password-otp', [
                'email' => $user->email,
                'otp' => '654321',
            ])->assertUnprocessable();

            $response->assertJson([
                'message' => $attempt === 5
                    ? 'คุณกรอกรหัส OTP ผิดครบจำนวนครั้งที่กำหนด กรุณาขอรหัสใหม่'
                    : 'รหัส OTP ไม่ถูกต้อง เหลืออีก '.(5 - $attempt).' ครั้ง',
                'attempts_remaining' => 5 - $attempt,
                'otp_invalidated' => $attempt === 5,
            ]);
        }

        $this->assertDatabaseHas('password_reset_otps', [
            'email' => $user->email,
            'attempts' => 5,
        ]);

        $this->postJson('/api/auth/verify-password-otp', [
            'email' => $user->email,
            'otp' => '123456',
        ])->assertUnprocessable()->assertJson([
            'message' => 'คุณกรอกรหัส OTP ผิดครบจำนวนครั้งที่กำหนด กรุณาขอรหัสใหม่',
        ]);
    }

    public function test_expired_otp_returns_a_specific_message(): void
    {
        $user = $this->user();
        $this->otp($user->email, '123456', now()->subSecond());

        $this->postJson('/api/auth/verify-password-otp', [
            'email' => $user->email,
            'otp' => '123456',
        ])->assertUnprocessable()->assertJson([
            'message' => 'รหัส OTP หมดอายุแล้ว กรุณาขอรหัสใหม่',
            'attempts_remaining' => 0,
            'otp_invalidated' => true,
        ]);
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

    private function otp(
        string $email,
        string $otp,
        mixed $expiresAt = null,
        int $attempts = 0,
        mixed $createdAt = null,
    ): void {
        DB::table('password_reset_otps')->insert([
            'email' => $email,
            'otp_hash' => Hash::make($otp),
            'attempts' => $attempts,
            'expires_at' => $expiresAt ?? now()->addMinutes(5),
            'created_at' => $createdAt ?? now(),
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
