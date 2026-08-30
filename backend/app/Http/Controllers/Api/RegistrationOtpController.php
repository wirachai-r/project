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
        $user = User::where('email', $email)->firstOrFail();
        $record = DB::table('registration_otps')->where('email', $email)->first();

        if (! $record || $record->attempts >= 5 || now()->greaterThan($record->expires_at)) {
            throw ValidationException::withMessages(['otp' => ['รหัส OTP ไม่ถูกต้องหรือหมดอายุแล้ว']]);
        }
        if (! Hash::check($validated['otp'], $record->otp_hash)) {
            DB::table('registration_otps')->where('email', $email)->increment('attempts');
            throw ValidationException::withMessages(['otp' => ['รหัส OTP ไม่ถูกต้องหรือหมดอายุแล้ว']]);
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
        $token = $newToken->plainTextToken;
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
        if ($user) {
            $registrationOtp->issue($user);
        }

        return response()->json(['message' => 'หากบัญชียังไม่ได้ยืนยัน เราได้ส่ง OTP ใหม่ให้แล้ว']);
    }
}
