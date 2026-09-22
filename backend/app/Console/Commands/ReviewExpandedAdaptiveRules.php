<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReviewExpandedAdaptiveRules extends Command
{
    protected $signature = 'adaptive:review-expanded-rules';

    protected $description = 'Attach authoritative sources to additional supported adaptive symptom routes';

    public function handle(): int
    {
        $updated = 0;
        $missing = [];

        DB::transaction(function () use (&$updated, &$missing): void {
            foreach ($this->reviewedRoutes() as $initialName => $routes) {
                $initialId = DB::table('main_symptoms')->where('symptom_name', $initialName)->value('symptom_id');
                if (! $initialId) {
                    $missing[] = "initial: {$initialName}";

                    continue;
                }

                foreach ($routes as $targetName => $source) {
                    $targetId = DB::table('main_symptoms')->where('symptom_name', $targetName)->value('symptom_id');
                    if (! $targetId) {
                        $missing[] = "target: {$targetName}";

                        continue;
                    }

                    $questionIds = DB::table('adaptive_question_symptoms')
                        ->where('symptom_id', $targetId)
                        ->pluck('adaptive_question_id');

                    $count = DB::table('adaptive_question_rules')
                        ->where('initial_symptom_id', $initialId)
                        ->whereIn('adaptive_question_id', $questionIds)
                        ->update([
                            'evidence_source' => $source,
                            'evidence_status' => 'reviewed',
                            'reviewed_by' => null,
                            'reviewed_at' => now(),
                            'updated_at' => now(),
                        ]);

                    if ($count === 0) {
                        $missing[] = "route: {$initialName} -> {$targetName}";
                    }
                    $updated += $count;
                }
            }
        });

        $this->info("Reviewed {$updated} additional adaptive routes.");
        if ($missing !== []) {
            $this->warn('Not found: '.implode(', ', $missing));
        }

        return self::SUCCESS;
    }

    /** @return array<string, array<string, string>> */
    private function reviewedRoutes(): array
    {
        $stroke = 'NHS, Symptoms of a stroke (face weakness, arm weakness/numbness, dizziness, severe headache): https://www.nhs.uk/conditions/stroke/symptoms/';
        $strokeSwallowing = 'North West Ambulance Service NHS, Stroke (face/limb weakness, numbness, dizziness, headache, swallowing difficulty, loss of consciousness): https://www.nwas.nhs.uk/services/emergency-advice/stroke/';
        $palpitations = 'NHS, Heart palpitations (palpitations with chest pain, shortness of breath, feeling faint or fainting): https://www.nhs.uk/symptoms/heart-palpitations/';
        $pots = 'NHS, Postural tachycardia syndrome (palpitations with dizziness, sweating, chest pain, breathlessness, fainting, nausea or tummy pain): https://www.nhs.uk/conditions/postural-tachycardia-syndrome/';
        $dizziness = 'NHS, Dizziness (dizziness/vertigo with headache, vision change, numbness, pulse change, fainting or collapse): https://www.nhs.uk/symptoms/dizziness/';
        $uti = 'NHS, Urinary tract infections (frequent or painful urination, cloudy/dark/bloody urine, lower tummy or back pain, fever): https://www.nhs.uk/conditions/urinary-tract-infections-utis/';
        $urinaryDifficulty = 'Royal United Hospitals NHS, Urinary tract infection (frequent or difficult urination, cloudy/bloody urine and back pain): https://www.ruh.nhs.uk/patients/services/clinical_depts/older_people/documents/urinary_tract_infection.pdf';

        return [
            'ปากเบี้ยว' => [
                'อัมพาต' => $stroke,
                'แขนขาอ่อนแรง' => $stroke,
                'ชา' => $stroke,
                'ปวดศีรษะ' => $stroke,
                'บ้านหมุน' => $stroke,
                'เวียนศีรษะ/วิงเวียน' => $stroke,
                'หน้ามืด' => $stroke,
            ],
            'แขนขาอ่อนแรง' => [
                'ปากเบี้ยว' => $stroke,
                'อัมพาต' => $stroke,
                'ชา' => $stroke,
                'ปวดศีรษะ' => $stroke,
                'บ้านหมุน' => $stroke,
                'เวียนศีรษะ/วิงเวียน' => $stroke,
                'หน้ามืด' => $stroke,
            ],
            'กลืนลำบาก' => [
                'ชา' => $strokeSwallowing,
                'ปวดศีรษะ' => $strokeSwallowing,
                'แขนขาอ่อนแรง' => $strokeSwallowing,
                'ปากเบี้ยว' => $strokeSwallowing,
            ],
            'ใจสั่น' => [
                'เหงื่อออกตามมือตามเท้า' => $pots,
                'บ้านหมุน' => $pots,
                'เวียนศีรษะ/วิงเวียน' => $pots,
                'หน้ามืด' => $palpitations,
                'เป็นลม' => $palpitations,
                'เจ็บหน้าอก' => $palpitations,
                'ปวดท้อง' => $pots,
                'หอบ' => $palpitations,
            ],
            'บ้านหมุน' => [
                'เวียนศีรษะ/วิงเวียน' => $dizziness,
                'หน้ามืด' => $dizziness,
                'เป็นลม' => $dizziness,
                'ใจสั่น' => $dizziness,
                'ปวดศีรษะ' => $dizziness,
                'สลบ' => $dizziness,
                'หมดสติ' => $dizziness,
            ],
            'ปัสสาวะบ่อย' => [
                'ปัสสาวะลำบาก' => $urinaryDifficulty,
                'ขัดเบา' => $uti,
                'ปัสสาวะขุ่น' => $uti,
                'ปัสสาวะดำ' => $uti,
                'ปัสสาวะแดง/เป็นเลือด' => $uti,
                'ปัสสาวะสีผิดปกติ' => $uti,
                'ปวดท้องน้อยในผู้หญิงวัยเจริญพันธุ์' => $uti,
            ],
            'ปัสสาวะลำบาก' => [
                'ปัสสาวะบ่อย' => $urinaryDifficulty,
                'ขัดเบา' => $urinaryDifficulty,
                'ปัสสาวะขุ่น' => $urinaryDifficulty,
                'ปัสสาวะดำ' => $uti,
                'ปัสสาวะแดง/เป็นเลือด' => $urinaryDifficulty,
                'ปัสสาวะสีผิดปกติ' => $uti,
                'ปวดท้องน้อยในผู้หญิงวัยเจริญพันธุ์' => $uti,
            ],
            'ปวดหลัง' => [
                'ไข้ + ปวดหลัง' => $uti,
                'ปวดท้อง' => $uti,
                'ปัสสาวะขุ่น' => $uti,
                'ปัสสาวะดำ' => $uti,
                'ปัสสาวะแดง/เป็นเลือด' => $uti,
                'ปัสสาวะสีผิดปกติ' => $uti,
            ],
        ];
    }
}
