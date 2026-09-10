<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\HealthReportRequest;
use App\Models\Assessment;
use App\Models\DailyHealthRecord;
use App\Models\FollowUpEntry;
use App\Models\HealthEpisode;
use App\Support\HealthTime;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class HealthReportController extends Controller
{
    public function download(HealthReportRequest $request): Response
    {
        $validated = $request->validated();
        $user = $request->user();
        $from = HealthTime::localDate($validated['from']);
        $to = HealthTime::localDate($validated['to']);
        [$fromUtc, $toUtc] = HealthTime::utcRange($validated['from'], $validated['to']);

        $assessments = collect();
        if ($validated['include_assessments']) {
            $assessments = Assessment::query()
                ->with(['symptom', 'results.diseases'])
                ->where('user_id', $user->user_id)
                ->whereBetween('created_at', [$fromUtc, $toUtc])
                ->latest('created_at')
                ->get();
        }

        $followUps = collect();
        if ($validated['include_follow_ups']) {
            $followUps = FollowUpEntry::query()
                ->with('episodeSymptom.symptom')
                ->whereHas('episodeSymptom.episode', fn ($query) => $query->where('user_id', $user->user_id))
                ->whereBetween('recorded_at', [$fromUtc, $toUtc])
                ->latest('recorded_at')
                ->get();
        }

        $dailyRecords = collect();
        if ($validated['include_daily_records']) {
            $dailyRecords = DailyHealthRecord::query()
                ->with(['symptoms', 'healthEpisodes'])
                ->where('user_id', $user->user_id)
                ->whereBetween('recorded_on', [$from->toDateString(), $to->toDateString()])
                ->latest('recorded_on')
                ->latest('created_at')
                ->get();
        }

        $episodes = HealthEpisode::query()
            ->with(['assessments.symptom', 'symptoms.symptom', 'symptoms.entries'])
            ->where('user_id', $user->user_id)
            ->where(function ($query) use ($fromUtc, $toUtc) {
                $query->whereBetween('started_at', [$fromUtc, $toUtc])
                    ->orWhereHas('symptoms.entries', fn ($entries) => $entries->whereBetween('recorded_at', [$fromUtc, $toUtc]));
            })->latest('started_at')->get();

        $pdf = Pdf::loadView('pdf.health-report', compact(
            'user', 'from', 'to', 'assessments', 'followUps', 'dailyRecords', 'episodes'
        ))->setPaper('a4');
        $pdf->setOption([
            'defaultFont' => 'Sarabun',
            'isFontSubsettingEnabled' => true,
            'chroot' => base_path(),
        ]);

        return $pdf->download("health-report-{$from->format('Y-m-d')}-{$to->format('Y-m-d')}.pdf");
    }
}
