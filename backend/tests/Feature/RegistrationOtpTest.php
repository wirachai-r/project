<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_otp_is_invalidated_after_five_incorrect_attempts(): void
    {
        $user = $this->unverifiedUser();
        $this->otp($user->email, '123456');

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->postJson('/api/auth/verify-registration-otp', [
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

        $this->postJson('/api/auth/verify-registration-otp', [
            'email' => $user->email,
            'otp' => '123456',
        ])->assertUnprocessable()->assertJson([
            'message' => 'คุณกรอกรหัส OTP ผิดครบจำนวนครั้งที่กำหนด กรุณาขอรหัสใหม่',
        ]);
    }

    public function test_resend_replaces_the_old_registration_otp_and_resets_attempts(): void
    {
        Notification::fake();
        $user = $this->unverifiedUser();
        $this->otp($user->email, '123456', attempts: 4, createdAt: now()->subSeconds(61));

        $this->postJson('/api/auth/resend-registration-otp', [
            'email' => $user->email,
        ])->assertOk()->assertJson([
            'expires_in' => 300,
            'resend_available_in' => 60,
        ]);

        $record = DB::table('registration_otps')->where('email', $user->email)->first();
        $this->assertSame(0, $record->attempts);
        $this->assertFalse(Hash::check('123456', $record->otp_hash));
        Notification::assertSentTo($user, RegistrationOtpNotification::class);
    }

    private function otp(
        string $email,
        string $otp,
        int $attempts = 0,
        mixed $createdAt = null,
    ): void {
        DB::table('registration_otps')->insert([
            'email' => $email,
            'otp_hash' => Hash::make($otp),
            'attempts' => $attempts,
            'expires_at' => now()->addMinutes(5),
            'created_at' => $createdAt ?? now(),
            'updated_at' => now(),
        ]);
    }

    private function unverifiedUser(): User
    {
        return User::create([
            'user_id' => '000000001',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@example.com',
            'password' => 'old-password',
            'email_verified_at' => null,
        ]);
    }
}
