<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegistrationOtpService
{
    public function issue(User $user, bool $isResend = false): array
    {
        $otp = sprintf('%06d', random_int(0, 999999));
        DB::table('registration_otps')->updateOrInsert(['email' => $user->email], [
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
            'last_resent_at' => $isResend ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->notify(new RegistrationOtpNotification($otp));

        return [
            'expires_in' => 300,
            'resend_available_in' => $isResend ? 60 : 0,
        ];
    }
}
