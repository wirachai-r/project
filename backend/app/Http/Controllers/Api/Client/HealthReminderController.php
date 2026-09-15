<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\HealthReminderRequest;
use App\Models\HealthReminder;
use App\Support\HealthActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthReminderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => HealthReminder::query()
            ->where('user_id', $request->user()->user_id)
            ->orderBy('time_of_day')
            ->get()]);
    }

    public function store(HealthReminderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $reminder = HealthReminder::firstOrNew([
            'user_id' => $request->user()->user_id,
            'health_episode_id' => $data['health_episode_id'] ?? null,
            'reminder_type' => $data['reminder_type'],
        ]);
        $reminder->fill($data);
        $reminder->next_run_at = $reminder->calculateNextRun();
        $reminder->save();

        HealthActivityNotification::create(
            $request->user()->user_id,
            'ตั้งค่าการแจ้งเตือนแล้ว',
            $reminder->reminder_type === 'follow_up'
                ? 'ระบบบันทึกเวลาเตือนติดตามอาการเรียบร้อยแล้ว'
                : 'ระบบบันทึกเวลาเตือนสุขภาพประจำวันเรียบร้อยแล้ว',
            'health_reminder',
            $reminder->id,
        );

        return response()->json(['data' => $reminder], 201);
    }

    public function update(HealthReminderRequest $request, HealthReminder $reminder): JsonResponse
    {
        $this->authorizeOwner($request, $reminder);
        $reminder->fill($request->validated());
        $reminder->next_run_at = $reminder->is_enabled ? $reminder->calculateNextRun() : null;
        $reminder->save();

        return response()->json(['data' => $reminder]);
    }

    public function destroy(Request $request, HealthReminder $reminder): JsonResponse
    {
        $this->authorizeOwner($request, $reminder);
        $reminder->delete();

        return response()->json(['message' => 'ลบการแจ้งเตือนเรียบร้อยแล้ว']);
    }

    private function authorizeOwner(Request $request, HealthReminder $reminder): void
    {
        abort_unless($reminder->user_id === $request->user()->user_id, 403);
    }
}
