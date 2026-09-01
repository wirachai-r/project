<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\DailyHealthRecord;
use App\Models\FollowUpEntry;
use App\Models\UserBookmark;
use App\Services\HealthTrendStatistics;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HealthDashboardController extends Controller
{
    public function show(Request $request, HealthTrendStatistics $statistics)
    {
        $validated = $request->validate([
            'days' => ['sometimes', 'integer', 'in:7,30,90,365'],
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d', 'before_or_equal:to'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:today'],
        ]);
        $to = isset($validated['to'])
            ? CarbonImmutable::parse($validated['to'])->endOfDay()
            : CarbonImmutable::now()->endOfDay();
        $from = isset($validated['from'])
            ? CarbonImmutable::parse($validated['from'])->startOfDay()
            : $to->startOfDay()->subDays(((int) ($validated['days'] ?? 30)) - 1);
        if ($from->diffInDays($to) > 364) {
            throw ValidationException::withMessages([
                'from' => ['ช่วงวันที่ต้องไม่เกิน 365 วัน'],
            ]);
        }
        $days = (int) $from->diffInDays($to) + 1;
        $userId = $request->user()->user_id;
        $assessments = Assessment::query()
            ->with(['symptom', 'results'])
            ->where('user_id', $userId)
            ->where('assessment_status', 'C')
            ->whereBetween('completed_at', [$from, $to])
            ->latest('completed_at')
            ->get();

        $urgent = $assessments->filter(fn ($assessment) => $assessment->results->contains(fn ($result) => in_array($result->urgency_level, ['R', 'P', 'Y']))
        )->count();

        $symptoms = $assessments->groupBy('symptom_id')->map(function ($items) {
            return [
                'symptom_id' => $items->first()->symptom_id,
                'symptom_name' => $items->first()->symptom?->symptom_name ?? 'ไม่ระบุอาการ',
                'symptom_image' => $items->first()->symptom?->symptom_image,
                'count' => $items->count(),
            ];
        })->sortByDesc('count')->values()->take(5);

        $followUps = FollowUpEntry::query()
            ->with('episodeSymptom.symptom')
            ->whereHas('episodeSymptom.episode', fn ($query) => $query->where('user_id', $userId))
            ->whereBetween('recorded_at', [$from, $to])
            ->oldest('recorded_at')
            ->get();
        $primaryFollowUps = $followUps->filter(fn ($entry) => $entry->episodeSymptom->is_primary)->values();
        $dailyRecords = DailyHealthRecord::query()
            ->where('user_id', $userId)
            ->whereDate('recorded_on', '>=', $from->toDateString())
            ->whereDate('recorded_on', '<=', $to->toDateString())
            ->oldest('recorded_on')
            ->get();

        return response()->json([
            'summary' => [
                'assessment_count' => $assessments->count(),
                'urgent_count' => $urgent,
                'bookmark_count' => UserBookmark::where('user_id', $userId)->count(),
                'follow_up_count' => $followUps->count(),
            ],
            'top_symptoms' => $symptoms,
            'period_days' => $days,
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'statistical_analysis' => $statistics->analyze($primaryFollowUps, $dailyRecords, $from, $to),
            'severity_trend' => $primaryFollowUps->map(fn ($item) => [
                'severity' => $item->severity,
                'recorded_at' => $item->recorded_at,
            ]),
            'symptom_trends' => $followUps->groupBy('episode_symptom_id')->map(fn ($items) => [
                'episode_symptom_id' => $items->first()->episode_symptom_id,
                'symptom_name' => $items->first()->episodeSymptom->symptom?->symptom_name
                    ?? $items->first()->episodeSymptom->custom_symptom_text,
                'is_primary' => $items->first()->episodeSymptom->is_primary,
                'entries' => $items->map(fn ($item) => [
                    'severity' => $item->severity,
                    'temperature' => $item->temperature,
                    'recorded_at' => $item->recorded_at,
                ])->values(),
            ])->values(),
            'temperature_trend' => $primaryFollowUps
                ->whereNotNull('temperature')
                ->map(fn ($item) => [
                    'temperature' => $item->temperature,
                    'recorded_at' => $item->recorded_at,
                ])->values(),
            'daily_status_trend' => $dailyRecords->map(fn ($item) => [
                'status' => $item->status,
                'recorded_on' => $item->recorded_on->format('Y-m-d'),
            ]),
            'latest_assessment' => $assessments->first() ? [
                'id' => $assessments->first()->id,
                'symptom_name' => $assessments->first()->symptom?->symptom_name,
                'completed_at' => $assessments->first()->completed_at,
            ] : null,
        ]);
    }
}
