<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\DailyHealthRecord;
use App\Models\Disease;
use App\Models\FirstAid;
use App\Models\FollowUpEntry;
use App\Models\MainSymptom;
use App\Models\User;
use App\Support\HealthTime;
use Illuminate\Support\Facades\DB;

/**
 * @tags Admin DashboardController
 */
class DashboardController extends Controller
{
    public function stats()
    {
        return response()->json([
            'overview' => $this->overview(),
            'assessment_today' => $this->assessmentToday(),
            'urgency_summary' => $this->urgencySummary(),
            'top_diseases' => $this->topDiseases(),
            'top_symptoms' => $this->topSymptoms(),
            'recent_assessments' => $this->recentAssessments(),
            'new_users_trend' => $this->newUsersTrend(),
            'assessment_trend' => $this->assessmentTrend(),
        ]);
    }

    // จำนวนรวมทั้งหมด
    private function overview(): array
    {
        return [
            'total_users' => User::where('role', 'User')->count(),
            'total_assessments' => Assessment::count(),
            'total_diseases' => Disease::where('status', '1')->count(),
            'total_symptoms' => MainSymptom::where('status', '1')->count(),
            'total_articles' => Article::where('status', '1')->count(),
            'total_first_aids' => FirstAid::where('status', '1')->count(),
        ];
    }

    // assessment วันนี้
    private function assessmentToday(): array
    {
        [$from, $to] = HealthTime::utcRange(
            HealthTime::today()->toDateString(),
            HealthTime::today()->toDateString(),
        );

        return [
            'total' => Assessment::whereBetween('created_at', [$from, $to])->count(),
            'completed' => Assessment::whereBetween('created_at', [$from, $to])
                ->where('assessment_status', 'C')->count(),
            'ongoing' => Assessment::whereBetween('created_at', [$from, $to])
                ->where('assessment_status', 'P')->count(),
            'abandoned' => Assessment::whereBetween('created_at', [$from, $to])
                ->where('assessment_status', 'A')->count(),
        ];
    }

    // สรุปตาม urgency level
    private function urgencySummary(): array
    {
        $results = AssessmentResult::select('urgency_level', DB::raw('count(*) as total'))
            ->groupBy('urgency_level')
            ->get()
            ->keyBy('urgency_level');

        return [
            'R' => $results->get('R')?->total ?? 0, // Red
            'P' => $results->get('P')?->total ?? 0, // Pink
            'Y' => $results->get('Y')?->total ?? 0, // Yellow
            'G' => $results->get('G')?->total ?? 0, // Green
            'W' => $results->get('W')?->total ?? 0, // White
        ];
    }

    // โรคที่พบบ่อย 10 อันดับ
    private function topDiseases(): object
    {
        $assessmentCounts = DB::table('assessment_result_diseases')
            ->select('disease_id', DB::raw('count(*) as assessment_count'))
            ->groupBy('disease_id');
        $bookmarkCounts = DB::table('user_bookmarks')
            ->select('bookmarkable_id', DB::raw('count(*) as bookmark_count'))
            ->where('bookmarkable_type', Disease::class)
            ->groupBy('bookmarkable_id');

        return DB::table('diseases')
            ->leftJoinSub($assessmentCounts, 'assessment_counts', fn ($join) => $join
                ->on('assessment_counts.disease_id', '=', 'diseases.disease_id'))
            ->leftJoinSub($bookmarkCounts, 'bookmark_counts', fn ($join) => $join
                ->on('bookmark_counts.bookmarkable_id', '=', 'diseases.disease_id'))
            ->where('diseases.status', '1')
            ->select(
                'diseases.disease_id',
                'diseases.disease_name',
                'diseases.view_count',
                DB::raw('COALESCE(assessment_counts.assessment_count, 0) as assessment_count'),
                DB::raw('COALESCE(bookmark_counts.bookmark_count, 0) as bookmark_count')
            )
            ->get()
            ->map(fn ($r) => [
                'disease_id' => $r->disease_id,
                'disease_name' => $r->disease_name,
                'assessment_count' => (int) $r->assessment_count,
                'view_count' => (int) $r->view_count,
                'bookmark_count' => (int) $r->bookmark_count,
            ]);
    }

    // อาการที่ถูกเลือกบ่อย 10 อันดับ
    private function topSymptoms(): object
    {
        $stats = collect();
        $add = function ($id, $name, string $source) use (&$stats): void {
            $name = trim((string) ($name ?: 'ไม่ระบุอาการ'));
            $key = $id ? 'symptom:'.$id : 'custom:'.mb_strtolower($name);
            $item = $stats->get($key, [
                'symptom_id' => $id,
                'symptom_name' => $name,
                'total' => 0,
                'assessment_count' => 0,
                'daily_record_count' => 0,
                'follow_up_count' => 0,
            ]);
            $item['total']++;
            $item[$source.'_count']++;
            $stats->put($key, $item);
        };

        Assessment::with('symptom:symptom_id,symptom_name')
            ->get()
            ->each(fn ($assessment) => $add(
                $assessment->symptom_id,
                $assessment->symptom?->symptom_name,
                'assessment'
            ));
        DailyHealthRecord::with('symptoms:symptom_id,symptom_name')
            ->get()
            ->each(function ($record) use ($add): void {
                foreach ($record->symptoms as $symptom) {
                    $add($symptom->symptom_id, $symptom->symptom_name, 'daily_record');
                }
            });
        FollowUpEntry::with('episodeSymptom.symptom:symptom_id,symptom_name')
            ->get()
            ->each(function ($entry) use ($add): void {
                $episodeSymptom = $entry->episodeSymptom;
                $add(
                    $episodeSymptom->symptom_id,
                    $episodeSymptom->symptom?->symptom_name ?? $episodeSymptom->custom_symptom_text,
                    'follow_up'
                );
            });

        return $stats->sortByDesc('total')->values()->take(10);
    }

    // assessment ล่าสุด 10 รายการ
    private function recentAssessments(): object
    {
        return Assessment::with(['symptom:symptom_id,symptom_name', 'results:assessment_id,urgency_level'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($a) => [
                'assessment_id' => $a->id,
                'symptom_name' => $a->symptom?->symptom_name,
                'assessment_status' => $a->assessment_status,
                'urgency_level' => $a->results->first()?->urgency_level,
                'created_at' => $a->created_at,
            ]);
    }

    // user ใหม่ 30 วันย้อนหลัง
    private function newUsersTrend(): object
    {
        return User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as total')
        )
            ->where('role', 'User')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    // การประเมินรายวัน 30 วันย้อนหลัง
    private function assessmentTrend(): object
    {
        return Assessment::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as total')
        )
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
