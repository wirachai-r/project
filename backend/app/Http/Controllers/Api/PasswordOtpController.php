<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyPasswordOtpRequest;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordOtpController extends Controller
{
    public function request(ForgotPasswordRequest $request)
    {
        $email = Str::lower($request->validated('email'));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['ไม่พบบัญชีที่ใช้อีเมลนี้ในระบบ'],
            ]);
        }

        $existingOtp = DB::table('password_reset_otps')->where('email', $email)->first();
        $elapsedSeconds = $existingOtp?->last_resent_at
            ? abs((int) floor(now()->diffInSeconds($existingOtp->last_resent_at)))
            : 60;
        if ($elapsedSeconds < 60) {
            return response()->json([
                'message' => 'กรุณารอก่อนขอรหัส OTP ใหม่',
                'retry_after' => 60 - $elapsedSeconds,
            ], 429);
        }

        $otp = sprintf('%06d', random_int(0, 999999));
        $expiresAt = now()->addMinutes(5);
        DB::table('password_reset_otps')->updateOrInsert(['email' => $email], [
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => $expiresAt,
            'last_resent_at' => $existingOtp ? now() : null,
            'reset_token_hash' => null,
            'reset_token_expires_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->notify(new PasswordResetOtpNotification($otp));

        return response()->json([
            'message' => 'ส่งรหัส OTP ไปยังอีเมลแล้ว',
            'expires_in' => 300,
            'resend_available_in' => $existingOtp ? 60 : 0,
        ]);
    }

    public function verify(VerifyPasswordOtpRequest $request)
    {
        $email = Str::lower($request->validated('email'));
        $result = DB::transaction(function () use ($email, $request): array {
            $record = DB::table('password_reset_otps')
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if (! $record || now()->isAfter($record->expires_at)) {
                return ['error' => 'รหัส OTP หมดอายุแล้ว กรุณาขอรหัสใหม่'];
            }
            if ($record->reset_token_hash !== null) {
                return ['error' => 'รหัส OTP นี้ถูกใช้งานแล้ว กรุณาขอรหัสใหม่'];
            }
            if ($record->attempts >= 5) {
                return ['error' => 'คุณกรอกรหัส OTP ผิดครบจำนวนครั้งที่กำหนด กรุณาขอรหัสใหม่'];
            }

            if (! Hash::check($request->validated('otp'), $record->otp_hash)) {
                $attempts = $record->attempts + 1;
                DB::table('password_reset_otps')->where('email', $email)->update([
                    'attempts' => $attempts,
                    'otp_hash' => $attempts >= 5
                        ? Hash::make(Str::random(32))
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

            $resetToken = Str::random(64);
            DB::table('password_reset_otps')->where('email', $email)->update([
                'otp_hash' => Hash::make(Str::random(32)),
                'reset_token_hash' => hash('sha256', $resetToken),
                'reset_token_expires_at' => now()->addMinutes(10),
                'updated_at' => now(),
            ]);

            return ['reset_token' => $resetToken];
        });

        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['error'],
                'errors' => ['otp' => [$result['error']]],
                'attempts_remaining' => $result['attempts_remaining'] ?? 0,
                'otp_invalidated' => ! isset($result['attempts_remaining']),
            ], 422);
        }

        return response()->json(['message' => 'ยืนยัน OTP สำเร็จ', 'reset_token' => $result['reset_token']]);
    }

    public function reset(ResetPasswordRequest $request)
    {
        $email = Str::lower($request->validated('email'));
        $record = DB::table('password_reset_otps')->where('email', $email)->first();
        $validToken = $record && $record->reset_token_hash && $record->reset_token_expires_at
            && now()->isBefore($record->reset_token_expires_at)
            && hash_equals($record->reset_token_hash, hash('sha256', $request->validated('reset_token')));

        $user = $validToken ? User::whereRaw('LOWER(email) = ?', [$email])->first() : null;
        if (! $user) {
            throw ValidationException::withMessages(['reset_token' => ['สิทธิ์ตั้งรหัสผ่านไม่ถูกต้องหรือหมดอายุแล้ว']]);
        }

        DB::transaction(function () use ($user, $request, $email): void {
            $user->forceFill([
                'password' => $request->validated('password'),
                'remember_token' => Str::random(60),
                'login_attempts' => 0,
                'locked_until' => null,
            ])->save();
            $user->tokens()->delete();
            DB::table('password_reset_otps')->where('email', $email)->delete();
            event(new PasswordReset($user));
        });

        return response()->json(['message' => 'ตั้งรหัสผ่านใหม่สำเร็จ กรุณาเข้าสู่ระบบอีกครั้ง']);
    }
}
