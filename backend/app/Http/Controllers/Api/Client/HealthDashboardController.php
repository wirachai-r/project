<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\SymptomFollowUp;
use App\Models\UserBookmark;
use Illuminate\Http\Request;

class HealthDashboardController extends Controller
{
    public function show(Request $request)
    {
        $userId = $request->user()->user_id;
        $assessments = Assessment::query()
            ->with(['symptom', 'results'])
            ->where('user_id', $userId)
            ->where('assessment_status', 'C')
            ->latest('completed_at')
            ->get();

        $urgent = $assessments->filter(fn ($assessment) =>
            $assessment->results->contains(fn ($result) => in_array($result->urgency_level, ['R', 'P', 'Y']))
        )->count();

        $symptoms = $assessments->groupBy('symptom_id')->map(function ($items) {
            return [
                'symptom_id' => $items->first()->symptom_id,
                'symptom_name' => $items->first()->symptom?->symptom_name ?? 'ไม่ระบุอาการ',
                'count' => $items->count(),
            ];
        })->sortByDesc('count')->values()->take(5);

        $followUps = SymptomFollowUp::where('user_id', $userId)
            ->latest('recorded_at')->limit(14)->get()->reverse()->values();

        return response()->json([
            'summary' => [
                'assessment_count' => $assessments->count(),
                'urgent_count' => $urgent,
                'bookmark_count' => UserBookmark::where('user_id', $userId)->count(),
                'follow_up_count' => SymptomFollowUp::where('user_id', $userId)->count(),
            ],
            'top_symptoms' => $symptoms,
            'severity_trend' => $followUps->map(fn ($item) => [
                'severity' => $item->severity,
                'recorded_at' => $item->recorded_at,
            ]),
            'latest_assessment' => $assessments->first() ? [
                'id' => $assessments->first()->id,
                'symptom_name' => $assessments->first()->symptom?->symptom_name,
                'completed_at' => $assessments->first()->completed_at,
            ] : null,
        ]);
    }
}
