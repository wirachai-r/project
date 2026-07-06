<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
// use OpenApi\Attributes as OA;

/**
 * @tags Auth
 */

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'user_id'    => $this->generateUserId(),
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'password'   => $request->password,
            'phone'      => $request->phone,
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
