<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\GoogleLoginRequest; // <-- เพิ่ม
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite; // <-- เพิ่ม

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'user_id'       => $this->generateUserId(),
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'email'         => $request->email,
            'password'      => $request->password,
            'phone'         => $request->phone,
            'sex'           => $request->sex,
            'date_of_birth' => $request->date_of_birth,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'ลงทะเบียนสำเร็จ',
            'token'   => $token,
            'user'    => new UserResource($user),
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
                'message'      => 'บัญชีถูกล็อกชั่วคราว',
                'locked_until' => $user->locked_until,
            ], 429);
        }

        if (!$user || !Hash::check($request->password, $user->getAuthPassword())) {
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

        $user->update([
            'login_attempts' => 0,
            'locked_until'   => null,
            'last_login_at'  => now(),
            'last_login_ip'  => $request->ip(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'token'   => $token,
            'user'    => new UserResource($user),
        ]);
    }

    // --- เพิ่ม Method สำหรับ Google Login ---
    public function googleLogin(GoogleLoginRequest $request)
    {
        try {
            /** @var \Laravel\Socialite\Two\GoogleProvider $provider */
            $provider = Socialite::driver('google');

            // ใช้ stateless() สำหรับ API + userFromToken รับ token จาก Frontend
            $googleUser = $provider->stateless()->userFromToken($request->token);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Google Token ไม่ถูกต้องหรือหมดอายุ',
                'error'   => $e->getMessage()
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
                    'message'      => 'บัญชีถูกล็อกชั่วคราว',
                    'locked_until' => $user->locked_until,
                ], 429);
            }

            // ผูก google_id เพิ่ม หากสมัครผ่าน Email มาก่อนแต่ยังไม่มี google_id
            if (!$user->google_id) {
                $user->google_id = $googleUser->getId();
            }

            // อัปเดตรูปทุกครั้งที่ Google ส่ง URL กลับมา เพื่อเติมข้อมูลเดิมที่ว่าง
            // และรองรับกรณีที่ผู้ใช้เปลี่ยนรูปโปรไฟล์ใน Google
            if ($googleUser->getAvatar()) {
                $user->avatar = $googleUser->getAvatar();
            }
        } else {
            // กรณีผู้ใช้ใหม่: แยกชื่อ และ นามสกุล
            $nameParts = explode(' ', $googleUser->getName(), 2);
            $firstName = $nameParts[0] ?? 'Google';
            $lastName  = $nameParts[1] ?? 'User';

            $user = new User();
            $user->user_id    = $this->generateUserId();
            $user->first_name = $firstName;
            $user->last_name  = $lastName;
            $user->email      = $googleUser->getEmail();
            $user->google_id  = $googleUser->getId();
            $user->avatar     = $googleUser->getAvatar();
            $user->password   = null;
        }

        // อัปเดตข้อมูลการเข้าสู่ระบบและบันทึกข้อมูล (ใช้ update ครอบคลุมทั้งสร้างใหม่และมีอยู่แล้ว)
        $user->login_attempts = 0;
        $user->locked_until   = null;
        $user->last_login_at  = now();
        $user->last_login_ip  = $request->ip();
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'เข้าสู่ระบบด้วย Google สำเร็จ',
            'token'   => $token,
            'user'    => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'ออกจากระบบสำเร็จ']);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    private function generateUserId(): string
    {
        $last = User::max('user_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 9, '0', STR_PAD_LEFT);
    }
}
