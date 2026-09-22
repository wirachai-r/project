<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActivateReviewedAdaptiveRoutes extends Command
{
    protected $signature = 'adaptive:activate-reviewed-routes';

    protected $description = 'Activate question templates that have at least one externally reviewed route';

    public function handle(): int
    {
        $questionIds = DB::table('adaptive_question_rules')
            ->where('status', '1')
            ->whereIn('evidence_status', ['reviewed', 'verified'])
            ->pluck('adaptive_question_id')
            ->unique();

        $updated = DB::table('adaptive_questions')
            ->whereIn('id', $questionIds)
            ->whereIn('status', ['draft', 'reviewed'])
            ->update([
                'status' => 'approved',
                'evidence_source' => 'ใช้แหล่งอ้างอิงระดับเส้นทางใน adaptive_question_rules',
                'approved_by' => null,
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

        $routeCount = DB::table('adaptive_question_rules')
            ->whereIn('adaptive_question_id', $questionIds)
            ->where('status', '1')
            ->whereIn('evidence_status', ['reviewed', 'verified'])
            ->count();

        $this->info("เปิดใช้ {$updated} รูปแบบคำถาม ใน {$routeCount} เส้นทางที่ตรวจแหล่งแล้ว");

        return self::SUCCESS;
    }
}
