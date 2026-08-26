<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\DeleteAccountRequest;
use App\Support\AccountActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PrivacyController extends Controller
{
    public function export(Request $request): Response
    {
        $user = $request->user();
        AccountActivityLogger::record($user, 'personal_data_exported', $request);
        $assessmentIds = DB::table('assessments')
            ->where('user_id', $user->user_id)
            ->pluck('id');

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'profile' => $user->only([
                'user_id', 'first_name', 'last_name', 'email', 'phone',
                'date_of_birth', 'sex', 'profile_image', 'created_at', 'updated_at',
            ]),
            'assessments' => DB::table('assessments')->whereIn('id', $assessmentIds)->get(),
            'assessment_answers' => DB::table('assessment_answers')->whereIn('assessment_id', $assessmentIds)->get(),
            'assessment_results' => DB::table('assessment_results')->whereIn('assessment_id', $assessmentIds)->get(),
            'follow_ups' => DB::table('symptom_follow_ups')->where('user_id', $user->user_id)->get(),
            'daily_health_records' => DB::table('daily_health_records')->where('user_id', $user->user_id)->get(),
            'bookmarks' => DB::table('user_bookmarks')->where('user_id', $user->user_id)->get(),
            'article_views' => DB::table('article_views')->where('user_id', $user->user_id)->get(),
        ];

        $filename = 'personal-data-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(
            static function () use ($payload): void {
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            },
            $filename,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->validated('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['รหัสผ่านปัจจุบันไม่ถูกต้อง'],
            ]);
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });

        return response()->json([
            'message' => 'ลบบัญชีเรียบร้อยแล้ว',
        ]);
    }
}
