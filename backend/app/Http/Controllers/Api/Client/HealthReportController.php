<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\HealthReportRequest;
use App\Models\Assessment;
use App\Models\DailyHealthRecord;
use App\Models\FollowUpEntry;
use App\Models\HealthEpisode;
use App\Support\HealthTime;
use App\Support\PdfImage;
use Illuminate\Support\Facades\File;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
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
                ->where('is_saved', true)
                ->where('assessment_status', 'C')
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

        $episodes = collect();
        if ($validated['include_follow_ups']) {
            $episodes = HealthEpisode::query()
                ->with(['assessments.symptom', 'symptoms.symptom', 'symptoms.entries'])
                ->where('user_id', $user->user_id)
                ->where(function ($query) use ($fromUtc, $toUtc) {
                    $query->whereBetween('started_at', [$fromUtc, $toUtc])
                        ->orWhereHas('symptoms.entries', fn ($entries) => $entries->whereBetween('recorded_at', [$fromUtc, $toUtc]));
                })->latest('started_at')->get();
        }

        $profileImage = PdfImage::fromImageDisk($user->profile_image ?: $user->avatar);
        $diseaseImages = $assessments
            ->flatMap(fn (Assessment $assessment) => $assessment->results->flatMap->diseases)
            ->unique('disease_id')
            ->mapWithKeys(fn ($disease) => [
                $disease->disease_id => PdfImage::fromImageDisk($disease->disease_image),
            ])
            ->filter();

        $html = view('pdf.health-report', compact(
            'user', 'from', 'to', 'assessments', 'followUps', 'dailyRecords', 'episodes',
            'profileImage', 'diseaseImages'
        ))->render();

        $tempDirectory = storage_path('framework/cache/mpdf');
        File::ensureDirectoryExists($tempDirectory);

        $fontDirectories = (new ConfigVariables)->getDefaults()['fontDir'];
        $fontData = (new FontVariables)->getDefaults()['fontdata'];

        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDirectory,
            'fontDir' => array_merge($fontDirectories, [resource_path('fonts')]),
            'fontdata' => array_merge($fontData, [
                'prompt' => [
                    'R' => 'Prompt-Regular.ttf',
                    'B' => 'Prompt-Bold.ttf',
                    'useOTL' => 0xFF,
                ],
            ]),
            'default_font' => 'prompt',
        ]);
        $pdf->useKerning = true;
        $pdf->WriteHTML($html);

        $filename = "health-report-{$from->format('Y-m-d')}-{$to->format('Y-m-d')}.pdf";
        $contents = $pdf->Output($filename, Destination::STRING_RETURN);

        return response($contents, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($contents),
        ]);
    }
}
