<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Disease;
use App\Models\FirstAid;
use App\Models\MainSymptom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @tags Admin DashboardController
 */

class DashboardController extends Controller
{
    public function stats()
    {
        return response()->json([
            'overview'          => $this->overview(),
            'assessment_today'  => $this->assessmentToday(),
            'urgency_summary'   => $this->urgencySummary(),
            'top_diseases'      => $this->topDiseases(),
            'top_symptoms'      => $this->topSymptoms(),
            'recent_assessments'=> $this->recentAssessments(),
            'new_users_trend'   => $this->newUsersTrend(),
        ]);
    }

    // จำนวนรวมทั้งหมด
    private function overview(): array
    {
        return [
            'total_users'       => User::where('role', 'User')->count(),
            'total_assessments' => Assessment::count(),
            'total_diseases'    => Disease::where('status', '1')->count(),
            'total_symptoms'    => MainSymptom::where('status', '1')->count(),
            'total_articles'    => Article::where('status', '1')->count(),
            'total_first_aids'  => FirstAid::where('status', '1')->count(),
        ];
    }

    // assessment วันนี้
    private function assessmentToday(): array
    {
        return [
            'total'     => Assessment::whereDate('created_at', today())->count(),
            'completed' => Assessment::whereDate('created_at', today())
                ->where('assessment_status', 'C')->count(),
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
        return AssessmentResult::select('disease_id', DB::raw('count(*) as total'))
            ->with('disease:disease_id,disease_name')
            ->groupBy('disease_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'disease_id'   => $r->disease_id,
                'disease_name' => $r->disease?->disease_name,
                'total'        => $r->total,
            ]);
    }

    // อาการที่ถูกเลือกบ่อย 10 อันดับ
    private function topSymptoms(): object
    {
        return Assessment::select('symptom_id', DB::raw('count(*) as total'))
            ->with('symptom:symptom_id,symptom_name')
            ->groupBy('symptom_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'symptom_id'   => $r->symptom_id,
                'symptom_name' => $r->symptom?->symptom_name,
                'total'        => $r->total,
            ]);
    }

    // assessment ล่าสุด 10 รายการ
    private function recentAssessments(): object
    {
        return Assessment::with(['symptom:symptom_id,symptom_name', 'results:assessment_id,urgency_level'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($a) => [
                'assessment_id'     => $a->id,
                'symptom_name'      => $a->symptom?->symptom_name,
                'assessment_status' => $a->assessment_status,
                'urgency_level'     => $a->results->first()?->urgency_level,
                'created_at'        => $a->created_at,
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
}
