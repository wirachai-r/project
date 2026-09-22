<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReviewFeverAdaptiveRules extends Command
{
    protected $signature = 'adaptive:review-fever-rules';

    protected $description = 'Attach externally reviewed sources to fever Adaptive routes without approving them';

    public function handle(): int
    {
        $initialSymptomId = DB::table('main_symptoms')->where('symptom_name', 'ไข้')->value('symptom_id');
        if (! $initialSymptomId) {
            $this->error('ไม่พบอาการเริ่มต้น ไข้');

            return self::FAILURE;
        }

        $sources = [
            'ตัวร้อน' => 'NHS, High temperature (fever) in adults: https://www.nhs.uk/symptoms/fever-in-adults/',
            'ไข้ + ซีด' => 'NHS, Sepsis (high/low temperature with pale or blotchy skin): https://www.nhs.uk/conditions/sepsis/',
            'ซีด + มีไข้' => 'NHS, Sepsis (high/low temperature with pale or blotchy skin): https://www.nhs.uk/conditions/sepsis/',
            'ไข้ + ผื่น/ตุ่มขึ้น' => 'CDC, Definitions of signs and symptoms of ill travelers (fever plus rash): https://www.cdc.gov/port-health/php/definitions-symptoms-reportable-illness/index.html',
            'ตุ่มขึ้น + มีไข้' => 'CDC, Definitions of signs and symptoms of ill travelers (fever plus rash): https://www.cdc.gov/port-health/php/definitions-symptoms-reportable-illness/index.html',
            'ผื่นขึ้น + มีไข้' => 'CDC, Definitions of signs and symptoms of ill travelers (fever plus rash): https://www.cdc.gov/port-health/php/definitions-symptoms-reportable-illness/index.html',
            'ไข้ + คางบวม/คอบวม' => 'NHS, Swollen glands (neck or under-chin swelling with high temperature): https://www.nhs.uk/symptoms/swollen-glands/',
            'ไข้ + จุดแดง-จ้ำเขียว' => 'NHS, Meningitis (fever with a non-blanching spotty rash): https://www.nhs.uk/conditions/meningitis/',
            'น้ำหนักลด' => 'NHS, Vasculitis (conditions that can include high temperature and weight loss): https://www.nhs.uk/conditions/vasculitis/',
            'ลมพิษ' => 'NHS, Hives (hives with high temperature and feeling unwell): https://www.nhs.uk/conditions/hives/',
            'ดีซ่าน' => 'CDC, Clinical features of yellow fever (fever and jaundice): https://www.cdc.gov/yellow-fever/hcp/clinical-diagnosis/index.html',
        ];

        $updated = 0;
        DB::transaction(function () use ($initialSymptomId, $sources, &$updated): void {
            foreach ($sources as $targetName => $source) {
                $targetId = DB::table('main_symptoms')->where('symptom_name', $targetName)->value('symptom_id');
                if (! $targetId) {
                    continue;
                }

                $questionIds = DB::table('adaptive_question_symptoms')
                    ->where('symptom_id', $targetId)
                    ->pluck('adaptive_question_id');
                $updated += DB::table('adaptive_question_rules')
                    ->where('initial_symptom_id', $initialSymptomId)
                    ->whereIn('adaptive_question_id', $questionIds)
                    ->update([
                        'evidence_source' => $source,
                        'evidence_status' => 'reviewed',
                        'reviewed_by' => null,
                        'reviewed_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        });

        $this->info("เพิ่มแหล่งภายนอกให้เส้นทางคำถามหมวดไข้ {$updated} รายการแล้ว");
        $this->warn('รายการยังไม่ถูก approved และรอผู้มีอำนาจอนุมัติ');

        return self::SUCCESS;
    }
}
