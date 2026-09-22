<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReviewSensoryEmergencyAdaptiveRules extends Command
{
    protected $signature = 'adaptive:review-sensory-emergency-rules';

    protected $description = 'Review supported eye, ear, bleeding, head injury, swelling and jaundice adaptive routes';

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

        $this->info("Reviewed {$updated} sensory and emergency-sign adaptive routes.");
        if ($missing !== []) {
            $this->warn('Not found: '.implode(', ', $missing));
        }

        return self::SUCCESS;
    }

    /** @return array<string, array<string, string>> */
    private function reviewedRoutes(): array
    {
        $eye = 'NHS, Conjunctivitis (red, gritty, itchy, watery eyes; urgent signs include eye pain or vision changes): https://www.nhs.uk/conditions/conjunctivitis/';
        $floaters = 'NHS, Floaters and flashes (floaters/flashes with blurred vision or eye pain): https://www.nhs.uk/symptoms/floaters-and-flashes-in-the-eyes/';
        $ear = 'NHS, Ear infections (ear pain, fever, itching, discharge, hearing or balance changes): https://www.nhs.uk/conditions/ear-infections/';
        $eardrum = 'NHS, Perforated eardrum (ear pain/itching, blood or pus discharge, tinnitus, dizziness and fever): https://www.nhs.uk/conditions/perforated-eardrum/';
        $earache = 'NHS, Earache (ear pain with fever, discharge, hearing change or an object in the ear): https://www.nhs.uk/symptoms/earache/';
        $vomitingBlood = 'NHS, Vomiting blood (vomiting blood with tummy pain, dizziness/faintness or black stool): https://www.nhs.uk/symptoms/vomiting-blood/';
        $headInjury = 'NHS, Head injury and concussion (head injury with loss of consciousness, seizure, balance problems, weakness or ear bleeding): https://www.nhs.uk/conditions/head-injury-and-concussion/';
        $jaundice = 'NHS, Hepatitis (jaundice with fever, upper tummy pain, nausea/vomiting and fatigue): https://www.nhs.uk/conditions/hepatitis/';
        $liverDisease = 'NHS, Alcohol-related liver disease (jaundice with fever, weight loss, tiredness, tummy/leg swelling or bleeding): https://www.nhs.uk/conditions/alcohol-related-liver-disease-arld/';
        $angioedema = 'NHS, Angioedema (sudden swelling commonly affecting the face, lips and eyelids): https://www.nhs.uk/conditions/angioedema/';

        return [
            'เคืองตา' => [
                'คันตา' => $eye, 'ตาแฉะ' => $eye, 'ตาแดง' => $eye,
                'เจ็บตา' => $eye, 'ตาเจ็บ' => $eye, 'ปวดตา' => $eye,
                'ตาฟ้าฟาง' => $eye, 'ตามัว' => $eye,
                'เห็นเงาหยากไย่' => $floaters, 'เห็นแสงแวบคล้ายฟ้าแลบ' => $floaters,
            ],
            'ตาแฉะ' => [
                'คันตา' => $eye, 'เคืองตา' => $eye, 'ตาแดง' => $eye,
                'เจ็บตา' => $eye, 'ตาเจ็บ' => $eye, 'ปวดตา' => $eye,
                'ตาฟ้าฟาง' => $eye, 'ตามัว' => $eye,
                'เห็นเงาหยากไย่' => $floaters, 'เห็นแสงแวบคล้ายฟ้าแลบ' => $floaters,
            ],
            'เจ็บตา' => [
                'ตาเจ็บ' => $eye, 'ปวดตา' => $eye, 'ตาฟ้าฟาง' => $floaters,
                'ตามัว' => $floaters, 'เห็นเงาหยากไย่' => $floaters,
                'เห็นแสงแวบคล้ายฟ้าแลบ' => $floaters, 'คันตา' => $eye,
                'เคืองตา' => $eye, 'ตาแฉะ' => $eye, 'ตาแดง' => $eye,
            ],
            'คันหู' => [
                'เจ็บหู' => $ear, 'หูมีอาการปวด' => $ear, 'ปวดหู' => $ear,
                'ไข้ + ปวดหู' => $ear, 'เลือดออกจากรูหู' => $eardrum,
                'หูมีเลือดไหล' => $eardrum, 'หูหนองไหล/หูนํ้าหนวก' => $ear,
                'มีเสียงในหู' => $eardrum, 'หูมีเสียงดัง' => $eardrum,
            ],
            'หูมีอาการปวด' => [
                'คันหู' => $ear, 'เจ็บหู' => $ear, 'ปวดหู' => $ear,
                'ไข้ + ปวดหู' => $ear, 'เลือดออกจากรูหู' => $eardrum,
                'หูมีเลือดไหล' => $eardrum, 'หูหนองไหล/หูนํ้าหนวก' => $ear,
                'มีเสียงในหู' => $eardrum, 'หูมีเสียงดัง' => $eardrum,
                'สิ่งแปลกปลอมเข้าหู' => $earache, 'หูมีสิ่งแปลกปลอมเข้า' => $earache,
            ],
            'หูมีเลือดไหล' => [
                'เลือดออกจากรูหู' => $eardrum, 'หูหนองไหล/หูนํ้าหนวก' => $eardrum,
                'คันหู' => $eardrum, 'เจ็บหู' => $eardrum, 'หูมีอาการปวด' => $eardrum,
                'ปวดหู' => $eardrum, 'ไข้ + ปวดหู' => $eardrum,
                'บ้านหมุน' => $eardrum, 'เวียนศีรษะ/วิงเวียน' => $eardrum,
            ],
            'อาเจียนเป็นเลือด' => [
                'อาเจียน' => $vomitingBlood, 'ถ่ายดำ' => $vomitingBlood,
                'ปวดตรงลิ้นปี่/ยอดอก' => $vomitingBlood, 'ปวดท้อง' => $vomitingBlood,
            ],
            'บาดเจ็บที่ศีรษะ' => [
                'ศีรษะได้รับบาดเจ็บ' => $headInjury, 'เป็นลม' => $headInjury,
                'บ้านหมุน' => $headInjury, 'เวียนศีรษะ/วิงเวียน' => $headInjury,
                'หน้ามืด' => $headInjury, 'ชัก' => $headInjury,
            ],
            'ดีซ่าน' => [
                'ไข้ + ดีซ่าน (ตาเหลือง)' => $jaundice, 'ตาเหลือง' => $jaundice,
                'ไข้ + ปวดท้อง' => $jaundice, 'ไข้' => $jaundice, 'ตัวร้อน' => $jaundice,
                'บวมทั่วไป' => $liverDisease, 'น้ำหนักลด' => $liverDisease,
            ],
            'ริมฝีปากบวม' => [
                'หนังตาบวม' => $angioedema, 'บวมเฉพาะที่' => $angioedema,
            ],
            'หน้าบวม' => [
                'บวมทั่วไป' => $angioedema, 'เท้าบวม' => $angioedema,
            ],
        ];
    }
}
