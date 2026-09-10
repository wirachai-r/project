<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\RegistrationOtpService;
use App\Support\AccountActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegistrationOtpController extends Controller
{
    public function verify(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'device_type' => ['nullable', 'in:android,ios,web,windows,macos,linux,unknown'],
        ]);
        $email = mb_strtolower(trim($validated['email']));
        $result = DB::transaction(function () use ($email, $validated, $request): array {
            $user = User::where('email', $email)->lockForUpdate()->firstOrFail();
            $record = DB::table('registration_otps')
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if (! $record || now()->greaterThan($record->expires_at)) {
                return ['error' => 'รหัส OTP หมดอายุแล้ว กรุณาขอรหัสใหม่'];
            }
            if ($record->attempts >= 5) {
                return ['error' => 'คุณกรอกรหัส OTP ผิดครบจำนวนครั้งที่กำหนด กรุณาขอรหัสใหม่'];
            }

            if (! Hash::check($validated['otp'], $record->otp_hash)) {
                $attempts = $record->attempts + 1;
                DB::table('registration_otps')->where('email', $email)->update([
                    'attempts' => $attempts,
                    'otp_hash' => $attempts >= 5
                        ? Hash::make(str()->random(32))
                        : $record->otp_hash,
                    'updated_at' => now(),
                ]);

                return $attempts >= 5
                    ? ['error' => 'คุณกรอกรหัส OTP ผิดครบจำนวนครั้งที่กำหนด กรุณาขอรหัสใหม่']
                    : [
                        'error' => 'รหัส OTP ไม่ถูกต้อง เหลืออีก '.(5 - $attempts).' ครั้ง',
                        'attempts_remaining' => 5 - $attempts,
                    ];
            }

            $user->forceFill(['email_verified_at' => now()])->save();
            DB::table('registration_otps')->where('email', $email)->delete();
            $newToken = $user->createToken('auth_token');
            $newToken->accessToken->forceFill([
                'device_name' => $request->string('device_name')->trim()->value() ?: null,
                'device_type' => $request->string('device_type')->trim()->value() ?: 'unknown',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])->save();

            return ['user' => $user, 'token' => $newToken->plainTextToken];
        });

        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['error'],
                'errors' => ['otp' => [$result['error']]],
                'attempts_remaining' => $result['attempts_remaining'] ?? 0,
                'otp_invalidated' => ! isset($result['attempts_remaining']),
            ], 422);
        }

        $user = $result['user'];
        $token = $result['token'];
        AccountActivityLogger::record($user, 'email_verified', $request);

        return response()->json([
            'message' => 'ยืนยันอีเมลสำเร็จ',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function resend(Request $request, RegistrationOtpService $registrationOtp)
    {
        $email = mb_strtolower(trim($request->validate(['email' => ['required', 'email']])['email']));
        $user = User::where('email', $email)->whereNull('email_verified_at')->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['ไม่พบบัญชีที่รอยืนยันด้วยอีเมลนี้'],
            ]);
        }

        $existingOtp = DB::table('registration_otps')->where('email', $email)->first();
        $elapsedSeconds = $existingOtp?->last_resent_at
            ? abs((int) floor(now()->diffInSeconds($existingOtp->last_resent_at)))
            : 60;
        if ($elapsedSeconds < 60) {
            return response()->json([
                'message' => 'กรุณารอก่อนขอรหัส OTP ใหม่',
                'retry_after' => 60 - $elapsedSeconds,
            ], 429);
        }

        $otpTiming = $registrationOtp->issue($user, isResend: true);

        return response()->json([
            'message' => 'ส่งรหัส OTP ใหม่แล้ว',
            ...$otpTiming,
        ]);
    }
}
