<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegistrationOtpService
{
    public function issue(User $user): void
    {
        $otp = (string) random_int(100000, 999999);
        DB::table('registration_otps')->updateOrInsert(['email' => $user->email], [
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->notify(new RegistrationOtpNotification($otp));
    }
}
