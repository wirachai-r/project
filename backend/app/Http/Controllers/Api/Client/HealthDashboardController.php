<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\DailyHealthRecord;
use App\Models\FollowUpEntry;
use App\Models\HealthEpisode;
use App\Models\UserBookmark;
use App\Services\HealthTrendStatistics;
use App\Support\HealthTime;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HealthDashboardController extends Controller
{
    public function show(Request $request, HealthTrendStatistics $statistics)
    {
        $validated = $request->validate([
            'days' => ['sometimes', 'integer', 'in:7,30,90,365'],
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d', 'before_or_equal:to'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $localTo = isset($validated['to'])
            ? HealthTime::localDate($validated['to'])
            : HealthTime::today();
        if ($localTo->isAfter(HealthTime::today())) {
            throw ValidationException::withMessages(['to' => ['The to field must be a date before or equal to today.']]);
        }
        $localFrom = isset($validated['from'])
            ? HealthTime::localDate($validated['from'])
            : $localTo->subDays(((int) ($validated['days'] ?? 30)) - 1);
        if ($localFrom->diffInDays($localTo) > 364) {
            throw ValidationException::withMessages([
                'from' => ['ช่วงวันที่ต้องไม่เกิน 365 วัน'],
            ]);
        }
        [$from, $to] = HealthTime::utcRange($localFrom->toDateString(), $localTo->toDateString());
        $days = (int) $localFrom->diffInDays($localTo) + 1;
        $userId = $request->user()->user_id;
        $assessments = Assessment::query()
            ->with(['symptom', 'results'])
            ->where('user_id', $userId)
            ->where('assessment_status', 'C')
            ->where('is_saved', true)
            ->whereBetween('completed_at', [$from, $to])
            ->latest('completed_at')
            ->get();

        $urgent = $assessments->filter(fn ($assessment) => $assessment->results->contains(fn ($result) => in_array($result->urgency_level, ['R', 'P', 'Y']))
        )->count();

        $followUps = FollowUpEntry::query()
            ->with('episodeSymptom.symptom')
            ->whereHas('episodeSymptom.episode', fn ($query) => $query->where('user_id', $userId))
            ->whereBetween('recorded_at', [$from, $to])
            ->oldest('recorded_at')
            ->get();
        $primaryFollowUps = $followUps->filter(fn ($entry) => $entry->episodeSymptom->is_primary)->values();
        $dailyRecords = DailyHealthRecord::query()
            ->with('symptoms')
            ->where('user_id', $userId)
            ->whereDate('recorded_on', '>=', $localFrom->toDateString())
            ->whereDate('recorded_on', '<=', $localTo->toDateString())
            ->oldest('recorded_on')
            ->get();
        $symptomStats = collect();
        $addSymptom = function ($id, $name, $image, string $source) use (&$symptomStats): void {
            $name = trim((string) ($name ?: 'ไม่ระบุอาการ'));
            $key = $id ? 'symptom:'.$id : 'custom:'.mb_strtolower($name);
            $item = $symptomStats->get($key, [
                'symptom_id' => $id,
                'symptom_name' => $name,
                'symptom_image' => $image,
                'count' => 0,
                'assessment_count' => 0,
                'follow_up_count' => 0,
                'daily_record_count' => 0,
            ]);
            $item['count']++;
            $item[$source.'_count']++;
            $symptomStats->put($key, $item);
        };
        foreach ($assessments as $assessment) {
            $addSymptom(
                $assessment->symptom_id,
                $assessment->symptom?->symptom_name,
                $assessment->symptom?->symptom_image,
                'assessment'
            );
        }
        foreach ($followUps as $followUp) {
            $episodeSymptom = $followUp->episodeSymptom;
            $addSymptom(
                $episodeSymptom->symptom_id,
                $episodeSymptom->symptom?->symptom_name ?? $episodeSymptom->custom_symptom_text,
                $episodeSymptom->symptom?->symptom_image,
                'follow_up'
            );
        }
        foreach ($dailyRecords as $record) {
            foreach ($record->symptoms as $symptom) {
                $addSymptom(
                    $symptom->symptom_id,
                    $symptom->symptom_name,
                    $symptom->symptom_image,
                    'daily_record'
                );
            }
        }
        $symptoms = $symptomStats->sortByDesc('count')->values()->take(5);
        $activeEpisodes = HealthEpisode::query()
            ->with(['symptoms.symptom', 'symptoms.entries' => fn ($query) => $query->latest('recorded_at')->limit(1)])
            ->where('user_id', $userId)->where('status', 'A')->latest('started_at')->get();
        $todayCheckInCount = DailyHealthRecord::query()
            ->where('user_id', $userId)->whereDate('recorded_on', HealthTime::today()->toDateString())->count();

        return response()->json([
            'summary' => [
                'assessment_count' => $assessments->count(),
                'urgent_count' => $urgent,
                'bookmark_count' => UserBookmark::where('user_id', $userId)->count(),
                'follow_up_count' => $followUps->count(),
                'active_episode_count' => $activeEpisodes->count(),
                'today_check_in_count' => $todayCheckInCount,
            ],
            'active_episodes' => $activeEpisodes->map(fn ($episode) => [
                'id' => $episode->id,
                'source_assessment_id' => $episode->source_assessment_id,
                'started_at' => $episode->started_at,
                'symptom_names' => $episode->symptoms->map(fn ($item) => $item->symptom?->symptom_name ?? $item->custom_symptom_text)->filter()->values(),
                'latest_severity' => $episode->symptoms->flatMap(fn ($item) => $item->entries)->sortByDesc('recorded_at')->first()?->severity,
                'latest_recorded_at' => $episode->symptoms->flatMap(fn ($item) => $item->entries)->sortByDesc('recorded_at')->first()?->recorded_at,
            ])->values(),
            'top_symptoms' => $symptoms,
            'period_days' => $days,
            'period' => [
                'from' => $localFrom->toDateString(),
                'to' => $localTo->toDateString(),
            ],
            'statistical_analysis' => $statistics->analyze($primaryFollowUps, $dailyRecords, $localFrom, $localTo),
            'assessment_trend' => $assessments->sortBy('completed_at')->map(fn ($item) => [
                'completed_at' => $item->completed_at,
            ])->values(),
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
