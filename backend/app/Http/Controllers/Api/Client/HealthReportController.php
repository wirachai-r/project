<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\HealthReportRequest;
use App\Models\Assessment;
use App\Models\DailyHealthRecord;
use App\Models\SymptomFollowUp;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\Response;

class HealthReportController extends Controller
{
    public function download(HealthReportRequest $request): Response
    {
        $validated = $request->validated();
        $user = $request->user();
        $from = CarbonImmutable::parse($validated['from'])->startOfDay();
        $to = CarbonImmutable::parse($validated['to'])->endOfDay();

        $assessments = collect();
        if ($validated['include_assessments']) {
            $assessments = Assessment::query()
                ->with(['symptom', 'results.diseases'])
                ->where('user_id', $user->user_id)
                ->whereBetween('created_at', [$from, $to])
                ->latest('created_at')
                ->get();
        }

        $followUps = collect();
        if ($validated['include_follow_ups']) {
            $followUps = SymptomFollowUp::query()
                ->with('assessment.symptom')
                ->where('user_id', $user->user_id)
                ->whereBetween('recorded_at', [$from, $to])
                ->latest('recorded_at')
                ->get();
        }

        $dailyRecords = collect();
        if ($validated['include_daily_records']) {
            $dailyRecords = DailyHealthRecord::query()
                ->where('user_id', $user->user_id)
                ->whereBetween('recorded_on', [$from->toDateString(), $to->toDateString()])
                ->latest('recorded_on')
                ->get();
        }

        $pdf = Pdf::loadView('pdf.health-report', compact(
            'user', 'from', 'to', 'assessments', 'followUps', 'dailyRecords'
        ))->setPaper('a4');
        $pdf->setOption([
            'defaultFont' => 'Sarabun',
            'isFontSubsettingEnabled' => true,
            'chroot' => base_path(),
        ]);

        return $pdf->download("health-report-{$from->format('Y-m-d')}-{$to->format('Y-m-d')}.pdf");
    }
}
