<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest; // <-- เพิ่ม
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\RegistrationOtpService;
use App\Support\AccountActivityLogger;
use App\Support\GoogleAvatarStorage;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

// <-- เพิ่ม

class AuthController extends Controller
{
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'หากอีเมลนี้มีอยู่ในระบบ เราได้ส่งลิงก์ตั้งรหัสผ่านใหม่ให้แล้ว',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'login_attempts' => 0,
                    'locked_until' => null,
                ])->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['ลิงก์ตั้งรหัสผ่านไม่ถูกต้องหรือหมดอายุแล้ว'],
            ]);
        }

        return response()->json(['message' => 'ตั้งรหัสผ่านใหม่สำเร็จ กรุณาเข้าสู่ระบบอีกครั้ง']);
    }

    public function register(RegisterRequest $request, RegistrationOtpService $registrationOtp)
    {
        $email = mb_strtolower(trim($request->email));
        $user = User::where('email', $email)->whereNull('email_verified_at')->first() ?? new User([
            'user_id' => $this->generateUserId(),
            'email' => $email,
        ]);
        $user->fill([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'password' => $request->password,
            'phone' => $request->phone,
            'sex' => $request->sex,
            'date_of_birth' => $request->date_of_birth,
        ]);
        $user->save();

        $otpTiming = $registrationOtp->issue($user);
        AccountActivityLogger::record($user, 'account_registered', $request);

        return response()->json([
            'message' => 'ลงทะเบียนสำเร็จ กรุณายืนยัน OTP ที่ส่งไปยังอีเมล',
            'requires_verification' => true,
            'email' => $user->email,
            ...$otpTiming,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user && $user->status === '2') {
            return response()->json(['message' => 'บัญชีนี้ถูกระงับการใช้งาน'], 403);
        }

        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            return response()->json([
                'message' => 'บัญชีถูกล็อกชั่วคราว',
                'locked_until' => $user->locked_until,
            ], 429);
        }

        if (! $user || ! Hash::check($request->password, $user->getAuthPassword())) {
            if ($user) {
                $user->increment('login_attempts');
                if ($user->login_attempts >= 5) {
                    $user->update(['locked_until' => now()->addMinutes(15)]);
                }
            }

            throw ValidationException::withMessages([
                'email' => ['อีเมลหรือรหัสผ่านไม่ถูกต้อง'],
            ]);
        }

        if ($user->email_verified_at === null) {
            return response()->json([
                'message' => 'กรุณายืนยันอีเมลด้วย OTP ก่อนเข้าสู่ระบบ',
                'requires_verification' => true,
                'email' => $user->email,
            ], 403);
        }

        $user->update([
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $token = $this->createSessionToken($user, $request);
        AccountActivityLogger::record($user, 'login', $request, ['method' => 'password']);

        return response()->json([
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    // --- เพิ่ม Method สำหรับ Google Login ---
    public function googleLogin(GoogleLoginRequest $request)
    {
        try {
            /** @var GoogleProvider $provider */
            $provider = Socialite::driver('google');

            // ใช้ stateless() สำหรับ API + userFromToken รับ token จาก Frontend
            $googleUser = $provider->stateless()->userFromToken($request->token);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'message' => 'Google Token ไม่ถูกต้องหรือหมดอายุ',
            ], 401);
        }

        // ค้นหา User จาก google_id หรือ email
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            // ตรวจสอบสถานะการระงับการใช้งาน
            if ($user->status === '2') {
                return response()->json(['message' => 'บัญชีนี้ถูกระงับการใช้งาน'], 403);
            }

            // ตรวจสอบการถูกล็อกชั่วคราว
            if ($user->locked_until && $user->locked_until->isFuture()) {
                return response()->json([
                    'message' => 'บัญชีถูกล็อกชั่วคราว',
                    'locked_until' => $user->locked_until,
                ], 429);
            }

            // ผูก google_id เพิ่ม หากสมัครผ่าน Email มาก่อนแต่ยังไม่มี google_id
            if (! $user->google_id) {
                $user->google_id = $googleUser->getId();
            }

            // อัปเดตรูปทุกครั้งที่ Google ส่ง URL กลับมา เพื่อเติมข้อมูลเดิมที่ว่าง
            // และรองรับกรณีที่ผู้ใช้เปลี่ยนรูปโปรไฟล์ใน Google
            if ($googleUser->getAvatar()) {
                $cachedAvatar = GoogleAvatarStorage::cache($user, $googleUser->getAvatar());
                if ($cachedAvatar || ! $user->avatar) {
                    $user->avatar = $cachedAvatar ?? $googleUser->getAvatar();
                }
            }
        } else {
            // กรณีผู้ใช้ใหม่: แยกชื่อ และ นามสกุล
            $nameParts = explode(' ', $googleUser->getName(), 2);
            $firstName = $nameParts[0] ?? 'Google';
            $lastName = $nameParts[1] ?? 'User';

            $user = new User;
            $user->user_id = $this->generateUserId();
            $user->first_name = $firstName;
            $user->last_name = $lastName;
            $user->email = $googleUser->getEmail();
            $user->email_verified_at = now();
            $user->google_id = $googleUser->getId();
            $user->avatar = GoogleAvatarStorage::cache($user, $googleUser->getAvatar())
                ?? $googleUser->getAvatar();
            $user->password = null;
        }

        // อัปเดตข้อมูลการเข้าสู่ระบบและบันทึกข้อมูล (ใช้ update ครอบคลุมทั้งสร้างใหม่และมีอยู่แล้ว)
        $user->login_attempts = 0;
        $user->locked_until = null;
        $user->last_login_at = now();
        $user->last_login_ip = $request->ip();
        $user->save();

        $token = $this->createSessionToken($user, $request);
        AccountActivityLogger::record($user, 'login', $request, ['method' => 'google']);

        return response()->json([
            'message' => 'เข้าสู่ระบบด้วย Google สำเร็จ',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        AccountActivityLogger::record($request->user(), 'logout', $request);
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'ออกจากระบบสำเร็จ']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $remoteImageField = filter_var($user->profile_image, FILTER_VALIDATE_URL)
            ? 'profile_image'
            : (filter_var($user->avatar, FILTER_VALIDATE_URL) ? 'avatar' : null);
        if ($remoteImageField) {
            $cachedAvatar = GoogleAvatarStorage::cache($user, $user->{$remoteImageField});
            if ($cachedAvatar) {
                $user->forceFill([$remoteImageField => $cachedAvatar])->saveQuietly();
            }
        }

        return new UserResource($user);
    }

    private function generateUserId(): string
    {
        $last = User::max('user_id');
        $next = $last ? (int) $last + 1 : 1;

        return str_pad($next, 9, '0', STR_PAD_LEFT);
    }

    private function createSessionToken(User $user, Request $request): string
    {
        $newToken = $user->createToken('auth_token');
        $newToken->accessToken->forceFill([
            'device_name' => $request->string('device_name')->trim()->value() ?: null,
            'device_type' => $request->string('device_type')->trim()->value() ?: 'unknown',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ])->save();

        return $newToken->plainTextToken;
    }
}
