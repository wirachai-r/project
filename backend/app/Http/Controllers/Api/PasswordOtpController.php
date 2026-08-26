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

        if ($user) {
            $otp = (string) random_int(100000, 999999);
            DB::table('password_reset_otps')->updateOrInsert(['email' => $email], [
                'otp_hash' => Hash::make($otp),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
                'reset_token_hash' => null,
                'reset_token_expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user->notify(new PasswordResetOtpNotification($otp));
        }

        return response()->json(['message' => 'หากอีเมลนี้มีอยู่ในระบบ เราได้ส่งรหัส OTP ให้แล้ว']);
    }

    public function verify(VerifyPasswordOtpRequest $request)
    {
        $email = Str::lower($request->validated('email'));
        $record = DB::table('password_reset_otps')->where('email', $email)->first();

        if (! $record || now()->isAfter($record->expires_at) || $record->attempts >= 5) {
            throw ValidationException::withMessages(['otp' => ['รหัส OTP ไม่ถูกต้องหรือหมดอายุแล้ว']]);
        }

        if (! Hash::check($request->validated('otp'), $record->otp_hash)) {
            DB::table('password_reset_otps')->where('email', $email)->increment('attempts');
            throw ValidationException::withMessages(['otp' => ['รหัส OTP ไม่ถูกต้องหรือหมดอายุแล้ว']]);
        }

        $resetToken = Str::random(64);
        DB::table('password_reset_otps')->where('email', $email)->update([
            'otp_hash' => Hash::make(Str::random(32)),
            'reset_token_hash' => hash('sha256', $resetToken),
            'reset_token_expires_at' => now()->addMinutes(10),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'ยืนยัน OTP สำเร็จ', 'reset_token' => $resetToken]);
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
