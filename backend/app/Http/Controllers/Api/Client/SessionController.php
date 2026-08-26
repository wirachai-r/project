<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Support\AccountActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class SessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()?->id;
        $sessions = $request->user()->tokens()
            ->latest('last_used_at')
            ->latest('created_at')
            ->get()
            ->map(fn (PersonalAccessToken $token) => [
                'id' => $token->id,
                'device_name' => $token->device_name ?: 'อุปกรณ์ที่ไม่ทราบชื่อ',
                'device_type' => $token->device_type ?: 'unknown',
                'ip_address' => $token->ip_address,
                'last_active_at' => $token->last_used_at ?? $token->created_at,
                'created_at' => $token->created_at,
                'is_current' => $token->id === $currentTokenId,
            ]);

        return response()->json(['data' => $sessions]);
    }

    public function destroy(Request $request, int $session): JsonResponse
    {
        $token = $request->user()->tokens()->whereKey($session)->firstOrFail();
        $isCurrent = $token->id === $request->user()->currentAccessToken()?->id;
        AccountActivityLogger::record($request->user(), 'session_revoked', $request, [
            'device_name' => $token->device_name,
            'was_current' => $isCurrent,
        ]);
        $token->delete();

        return response()->json([
            'message' => 'ออกจากระบบอุปกรณ์เรียบร้อยแล้ว',
            'current_session_revoked' => $isCurrent,
        ]);
    }

    public function destroyOthers(Request $request): JsonResponse
    {
        $currentTokenId = $request->user()->currentAccessToken()?->id;
        $deleted = $request->user()->tokens()
            ->when($currentTokenId, fn ($query) => $query->whereKeyNot($currentTokenId))
            ->delete();
        AccountActivityLogger::record($request->user(), 'other_sessions_revoked', $request, [
            'revoked_count' => $deleted,
        ]);

        return response()->json([
            'message' => 'ออกจากระบบอุปกรณ์อื่นทั้งหมดแล้ว',
            'revoked_count' => $deleted,
        ]);
    }
}
