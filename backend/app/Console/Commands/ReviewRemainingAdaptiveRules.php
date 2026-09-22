<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReviewRemainingAdaptiveRules extends Command
{
    protected $signature = 'adaptive:review-remaining-rules';

    protected $description = 'Review remaining supported adaptive routes in evidence-based symptom clusters';

    public function handle(): int
    {
        $updated = 0;

        DB::transaction(function () use (&$updated): void {
            foreach ($this->groups() as $group) {
                $initialIds = DB::table('main_symptoms')->whereIn('symptom_name', $group['initials'])->pluck('symptom_id');
                $targetIds = DB::table('main_symptoms')->whereIn('symptom_name', $group['targets'])->pluck('symptom_id');
                $questionIds = DB::table('adaptive_question_symptoms')->whereIn('symptom_id', $targetIds)->pluck('adaptive_question_id');

                $updated += DB::table('adaptive_question_rules')
                    ->whereIn('initial_symptom_id', $initialIds)
                    ->whereIn('adaptive_question_id', $questionIds)
                    ->update([
                        'evidence_source' => $group['source'],
                        'evidence_status' => 'reviewed',
                        'reviewed_by' => null,
                        'reviewed_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        });

        $this->info("Reviewed {$updated} remaining supported adaptive routes.");

        return self::SUCCESS;
    }

    /** @return array<int, array{initials: array<int, string>, targets: array<int, string>, source: string}> */
    private function groups(): array
    {
        return [
            [
                'initials' => ['หวัด', 'คัดจมูก', 'แน่นจมูก', 'น้ำมูกไหล', 'คันจมูก', 'คันคอ', 'เจ็บคอ', 'ไอ', 'ไข้ + น้ำมูกไหล (ไข้หวัด)'],
                'targets' => ['หวัด', 'คัดจมูก', 'แน่นจมูก', 'น้ำมูกไหล', 'คันจมูก', 'คันคอ', 'เจ็บคอ', 'ไอ', 'ไข้ + ไอ', 'ไข้ + น้ำมูกไหล (ไข้หวัด)', 'อ่อนเพลีย'],
                'source' => 'NHS, Common cold (blocked/runny nose, sore throat, cough, high temperature and tiredness): https://www.nhs.uk/conditions/common-cold/',
            ],
            [
                'initials' => ['คัน', 'ตุ่มขึ้นตามตัว', 'ตุ่มคันตามตัว', 'ผื่นขึ้นตามตัว', 'ผื่นคันตามตัว', 'โรคผิวหนัง', 'ลมพิษ'],
                'targets' => ['คัน', 'ตุ่มขึ้นตามตัว', 'ตุ่มคันตามตัว', 'ผื่นขึ้นตามตัว', 'ผื่นคันตามตัว', 'โรคผิวหนัง', 'ลมพิษ', 'ริมฝีปากบวม', 'หน้าบวม'],
                'source' => 'NHS, Hives (itchy raised bumps or patches that may spread across the body, sometimes with swelling): https://www.nhs.uk/conditions/hives/',
            ],
            [
                'initials' => ['กามโรคในผู้ชาย', 'กามโรคในผู้หญิง', 'คันในช่องคลอด', 'ตกขาว', 'แผลที่อวัยวะเพศในผู้ชาย', 'แผลที่อวัยวะเพศในผู้หญิง', 'หนองไหลจากท่อปัสสาวะ', 'เลือดออกจากช่องคลอด', 'ตกเลือดทางช่องคลอด'],
                'targets' => ['กามโรคในผู้ชาย', 'กามโรคในผู้หญิง', 'คันในช่องคลอด', 'ตกขาว', 'แผลที่อวัยวะเพศในผู้ชาย', 'แผลที่อวัยวะเพศในผู้หญิง', 'หนองไหลจากท่อปัสสาวะ', 'เลือดออกจากช่องคลอด', 'ตกเลือดทางช่องคลอด', 'ขัดเบา', 'ปัสสาวะลำบาก'],
                'source' => 'NHS, Sexually transmitted infections (unusual genital discharge or bleeding, itching, sores, lumps and pain when urinating): https://www.nhs.uk/conditions/sexually-transmitted-infections-stis/',
            ],
            [
                'initials' => ['ปวดอัณฑะ', 'อัณฑะบวม', 'กามโรคในผู้ชาย', 'หนองไหลจากท่อปัสสาวะ'],
                'targets' => ['ปวดอัณฑะ', 'อัณฑะบวม', 'กามโรคในผู้ชาย', 'หนองไหลจากท่อปัสสาวะ', 'ขัดเบา'],
                'source' => 'NHS, Chlamydia (penile discharge, burning urination, and pain or swelling in the testicles): https://www.nhs.uk/conditions/chlamydia/',
            ],
            [
                'initials' => ['ก้อนบวม (มีก้อน)', 'ก้อนที่ขาหนีบ', 'ไข่ดันบวม', 'มีก้อนบวมเฉพาะที่', 'ต่อมน้ำเหลืองโต', 'คางบวม', 'คอพอก (คอโต)', 'ต่อมไทรอยด์โต', 'เต้านมเป็นก้อน', 'อัณฑะบวม'],
                'targets' => ['ก้อนบวม (มีก้อน)', 'ก้อนที่ขาหนีบ', 'ไข่ดันบวม', 'มีก้อนบวมเฉพาะที่', 'ต่อมน้ำเหลืองโต', 'คางบวม', 'คอพอก (คอโต)', 'ต่อมไทรอยด์โต', 'เต้านมเป็นก้อน', 'อัณฑะบวม'],
                'source' => 'NHS, Lumps (lumps and swellings including neck, armpit, groin, breast, thyroid and testicle locations): https://www.nhs.uk/symptoms/lumps/',
            ],
            [
                'initials' => ['ขาบวมข้างเดียว', 'เท้าบวม', 'บวมเฉพาะที่'],
                'targets' => ['ขาบวมข้างเดียว', 'เท้าบวม', 'บวมเฉพาะที่', 'เจ็บหน้าอก', 'หอบ'],
                'source' => 'NHS, Deep vein thrombosis (one-leg pain/swelling; emergency signs include chest pain or breathlessness): https://www.nhs.uk/conditions/deep-vein-thrombosis-dvt/',
            ],
            [
                'initials' => ['ข้ออักเสบ', 'ปวดข้อ', 'ไข้ + ปวดข้อ', 'ปวดเอ็น'],
                'targets' => ['ข้ออักเสบ', 'ปวดข้อ', 'ไข้ + ปวดข้อ', 'ปวดเอ็น', 'อ่อนเพลีย', 'น้ำหนักลด'],
                'source' => 'NHS, Rheumatoid arthritis symptoms (joint pain, swelling/stiffness, tiredness, fever and weight loss): https://www.nhs.uk/conditions/rheumatoid-arthritis/symptoms/',
            ],
            [
                'initials' => ['ท้องเดิน (ท้องเสีย ท้องร่วง)', 'อุจจาระร่วง', 'ไข้ + ท้องเดิน', 'ท้องเดินเรื้อรัง', 'บิด', 'ถ่ายเป็นมูก/มูกปนเลือด', 'ถ่ายเป็นเลือด'],
                'targets' => ['ท้องเดิน (ท้องเสีย ท้องร่วง)', 'อุจจาระร่วง', 'ไข้ + ท้องเดิน', 'ท้องเดินเรื้อรัง', 'บิด', 'ถ่ายเป็นมูก/มูกปนเลือด', 'ถ่ายเป็นเลือด', 'ปวดท้อง', 'คลื่นไส้', 'อาเจียน'],
                'source' => 'NHS, Diarrhoea and vomiting (diarrhoea with vomiting, tummy pain, fever or blood): https://www.nhs.uk/symptoms/diarrhoea-and-vomiting/',
            ],
            [
                'initials' => ['ท้องผูก', 'ท้องบวม', 'ปวดท้อง'],
                'targets' => ['ท้องผูก', 'ท้องบวม', 'ปวดท้อง', 'อ่อนเพลีย', 'ถ่ายเป็นเลือด'],
                'source' => 'NHS, Constipation (constipation with stomach ache, bloating, nausea, tiredness or blood in stool): https://www.nhs.uk/conditions/constipation/',
            ],
            [
                'initials' => ['เจ็บปาก', 'ปากเจ็บ', 'ปากเปื่อย', 'แผลในปาก', 'เหงือกบวม', 'ฟันมีเลือดออก', 'เลือดออกจากฟัน', 'โรคฟัน', 'ปวดฟัน'],
                'targets' => ['เจ็บปาก', 'ปากเจ็บ', 'ปากเปื่อย', 'แผลในปาก', 'เหงือกบวม', 'ฟันมีเลือดออก', 'เลือดออกจากฟัน', 'โรคฟัน', 'ปวดฟัน'],
                'source' => 'NHS, Gum disease and mouth ulcers (swollen, sore or bleeding gums and painful mouth ulcers): https://www.nhs.uk/conditions/gum-disease/ ; https://www.nhs.uk/conditions/mouth-ulcers/',
            ],
            [
                'initials' => ['แขนขาเคลื่อนไหวผิดปกติ', 'แขนขาอ่อนแรง', 'ปากเบี้ยว', 'ชา', 'ตาปรือ', 'หนังตาตก'],
                'targets' => ['แขนขาเคลื่อนไหวผิดปกติ', 'แขนขาอ่อนแรง', 'ปากเบี้ยว', 'ชา', 'ตาปรือ', 'หนังตาตก', 'อัมพาต'],
                'source' => 'NHS, Stroke symptoms (face weakness, arm weakness or numbness, and weakness down one side): https://www.nhs.uk/conditions/stroke/symptoms/',
            ],
            [
                'initials' => ['ชัก', 'ไข้ + ชัก', 'ชัก + มีไข้', 'สลบ', 'หมดสติ'],
                'targets' => ['ชัก', 'ไข้ + ชัก', 'ชัก + มีไข้', 'สลบ', 'หมดสติ', 'แขนขาเคลื่อนไหวผิดปกติ'],
                'source' => 'NHS, Epilepsy (seizures may include jerking, falling, loss of awareness or unconsciousness): https://www.nhs.uk/conditions/epilepsy/',
            ],
            [
                'initials' => ['หูตึง/หูหนวก', 'หูอื้อ', 'มีเสียงในหู', 'หูมีเสียงดัง', 'ปวดหู', 'หูมีอาการปวด'],
                'targets' => ['หูตึง/หูหนวก', 'หูอื้อ', 'มีเสียงในหู', 'หูมีเสียงดัง', 'ปวดหู', 'หูมีอาการปวด'],
                'source' => 'NHS, Hearing loss (hearing difficulty with earache, discharge, dizziness, vertigo or tinnitus): https://www.nhs.uk/conditions/hearing-loss/',
            ],
            [
                'initials' => ['แมลงเข้าหู', 'สิ่งแปลกปลอมเข้าหู', 'หูมีสิ่งแปลกปลอมเข้า', 'ปวดหู', 'หูมีอาการปวด'],
                'targets' => ['แมลงเข้าหู', 'สิ่งแปลกปลอมเข้าหู', 'หูมีสิ่งแปลกปลอมเข้า', 'ปวดหู', 'หูมีอาการปวด'],
                'source' => 'NHS, Earache (ear pain with an object stuck in the ear): https://www.nhs.uk/symptoms/earache/',
            ],
            [
                'initials' => ['คันก้น', 'ถ่ายเป็นพยาธิ', 'พยาธิ', 'โรคหนอนพยาธิ'],
                'targets' => ['คันก้น', 'ถ่ายเป็นพยาธิ', 'พยาธิ', 'โรคหนอนพยาธิ'],
                'source' => 'NHS, Threadworms (small white worms in stool and intense itching around the anus): https://www.nhs.uk/conditions/threadworms/',
            ],
            [
                'initials' => ['วงด่าง', 'ผื่นขึ้นตามตัว', 'ผื่นคันตามตัว', 'คัน', 'คันศีรษะ'],
                'targets' => ['วงด่าง', 'ผื่นขึ้นตามตัว', 'ผื่นคันตามตัว', 'คัน', 'คันศีรษะ', 'ผมร่วง/ผมบาง'],
                'source' => 'NHS, Ringworm (ring-shaped, scaly, itchy rash; scalp ringworm may cause patchy hair loss): https://www.nhs.uk/conditions/ringworm/',
            ],
            [
                'initials' => ['เต้านมเป็นฝี', 'เต้านมเป็นก้อน', 'ไข้ + บวม', 'ก้อนบวม (มีก้อน)'],
                'targets' => ['เต้านมเป็นฝี', 'เต้านมเป็นก้อน', 'ไข้ + บวม', 'ก้อนบวม (มีก้อน)'],
                'source' => 'NHS, Breast abscess (breast lump or swelling with pain, warmth, redness or high temperature): https://www.nhs.uk/conditions/breast-abscess/',
            ],
            [
                'initials' => ['ตกเลือดระหว่างตั้งครรภ์', 'เลือดออกจากช่องคลอด', 'ตกเลือดทางช่องคลอด', 'ประจำเดือนขาด/ไม่มา'],
                'targets' => ['ตกเลือดระหว่างตั้งครรภ์', 'เลือดออกจากช่องคลอด', 'ตกเลือดทางช่องคลอด', 'ประจำเดือนขาด/ไม่มา', 'ปวดท้อง'],
                'source' => 'NHS, Vaginal bleeding in pregnancy and ectopic pregnancy symptoms (missed period, vaginal bleeding, tummy pain, dizziness or fainting): https://www.nhs.uk/pregnancy/common-symptoms/vaginal-bleeding/ ; https://www.nhs.uk/conditions/ectopic-pregnancy/symptoms/',
            ],
            [
                'initials' => ['ประจำเดือนออกมาก', 'ตกเลือดทางช่องคลอด', 'เลือดออกจากช่องคลอด', 'ซีด', 'โลหิตจาง'],
                'targets' => ['ประจำเดือนออกมาก', 'ตกเลือดทางช่องคลอด', 'เลือดออกจากช่องคลอด', 'ซีด', 'โลหิตจาง', 'อ่อนเพลีย'],
                'source' => 'NHS, Heavy periods (heavy bleeding with tiredness or breathlessness; tests may identify iron-deficiency anaemia): https://www.nhs.uk/conditions/heavy-periods/',
            ],
            [
                'initials' => ['ประจำเดือนขาด/ไม่มา', 'น้ำหนักมาก (น้ำหนักขึ้น)', 'อ้วน', 'อ่อนเพลีย'],
                'targets' => ['ประจำเดือนขาด/ไม่มา', 'น้ำหนักมาก (น้ำหนักขึ้น)', 'อ้วน', 'อ่อนเพลีย'],
                'source' => 'NHS, Missed or late periods (associated features can include pregnancy, overweight, weight change and tiredness): https://www.nhs.uk/symptoms/missed-or-late-periods/',
            ],
            [
                'initials' => ['ตามีสิ่งแปลกปลอมเข้า', 'สิ่งแปลกปลอมเข้าตา', 'เคืองตา', 'เจ็บตา', 'ตาแดง', 'ตาแฉะ'],
                'targets' => ['ตามีสิ่งแปลกปลอมเข้า', 'สิ่งแปลกปลอมเข้าตา', 'เคืองตา', 'เจ็บตา', 'ตาแดง', 'ตาแฉะ', 'ตามัว'],
                'source' => 'NHS, Eye injuries (foreign material in the eye can cause pain, irritation and vision changes): https://www.nhs.uk/conditions/eye-injuries/',
            ],
            [
                'initials' => ['ลิ้นเป็นฝ้าขาว', 'เจ็บปาก', 'ปากเจ็บ', 'แผลในปาก'],
                'targets' => ['ลิ้นเป็นฝ้าขาว', 'เจ็บปาก', 'ปากเจ็บ', 'แผลในปาก'],
                'source' => 'NHS, Oral thrush (white tongue or mouth patches with mouth, tongue or gum pain): https://www.nhs.uk/conditions/oral-thrush-mouth-thrush/',
            ],
            [
                'initials' => ['สิ่งแปลกปลอมเข้าจมูก', 'เลือดกำเดาไหล'],
                'targets' => ['สิ่งแปลกปลอมเข้าจมูก', 'เลือดกำเดาไหล'],
                'source' => 'Royal United Hospitals NHS, Nosebleeds (nasal trauma or a foreign body can cause nosebleeding): https://www.ruh.nhs.uk/For_Clinicians/departments_ruh/ENT/documents/referrals/ENT016_Nosebleeds.pdf',
            ],
            [
                'initials' => ['บาดเจ็บที่ศีรษะ', 'ศีรษะได้รับบาดเจ็บ', 'เป็นลม', 'บ้านหมุน', 'เวียนศีรษะ/วิงเวียน', 'หน้ามืด', 'ชัก'],
                'targets' => ['บาดเจ็บที่ศีรษะ', 'ศีรษะได้รับบาดเจ็บ', 'เป็นลม', 'บ้านหมุน', 'เวียนศีรษะ/วิงเวียน', 'หน้ามืด', 'ชัก'],
                'source' => 'NHS, Head injury and concussion (head injury with loss of consciousness, seizure, weakness or balance problems): https://www.nhs.uk/conditions/head-injury-and-concussion/',
            ],
            [
                'initials' => ['ตัวร้อน', 'ไข้ + ไอ', 'ไข้ + เจ็บคอ'],
                'targets' => ['ไข้ + น้ำมูกไหล (ไข้หวัด)'],
                'source' => 'NHS, Common cold (runny or blocked nose with cough, sore throat and sometimes a high temperature): https://www.nhs.uk/conditions/common-cold/',
            ],
            [
                'initials' => ['บวมทั่วไป', 'หน้าบวม', 'เท้าบวม', 'ท้องบวม'],
                'targets' => ['ไข้ + บวม', 'หน้าบวม', 'เท้าบวม', 'ท้องบวม', 'บวมทั่วไป'],
                'source' => 'NHS, Oedema (swelling may affect feet, legs, face or tummy; fever with swelling needs urgent assessment): https://www.nhs.uk/conditions/oedema/',
            ],
            [
                'initials' => ['ปวดศีรษะ', 'ปวดฟัน', 'คัดจมูก', 'แน่นจมูก', 'น้ำมูกไหล'],
                'targets' => ['ปวดใบหน้าข้างเดียว', 'ปวดศีรษะ', 'ปวดฟัน', 'คัดจมูก', 'แน่นจมูก', 'น้ำมูกไหล'],
                'source' => 'NHS, Sinusitis (one-sided facial pain or pressure with headache, toothache and blocked or runny nose): https://www.nhs.uk/conditions/sinusitis-sinus-infection/',
            ],
            [
                'initials' => ['ปัสสาวะกะปริดกะปรอย', 'ปัสสาวะบ่อย', 'ปัสสาวะลำบาก', 'ปัสสาวะไม่ออก/ออกน้อย', 'ขัดเบา'],
                'targets' => ['ปัสสาวะกะปริดกะปรอย', 'ปัสสาวะบ่อย', 'ปัสสาวะลำบาก', 'ปัสสาวะไม่ออก/ออกน้อย', 'ขัดเบา'],
                'source' => 'NHS, Enlarged prostate (difficulty starting, weak or stop-start flow, incomplete emptying, dribbling and frequent urination): https://www.nhs.uk/conditions/enlarged-prostate/',
            ],
            [
                'initials' => ['ปัสสาวะมาก', 'ปัสสาวะบ่อย', 'อ่อนเพลีย', 'น้ำหนักลด'],
                'targets' => ['ปัสสาวะมาก', 'ปัสสาวะบ่อย', 'อ่อนเพลีย', 'น้ำหนักลด'],
                'source' => 'NHS, Diabetes insipidus symptoms (passing large amounts of urine frequently with thirst and tiredness): https://www.nhs.uk/conditions/diabetes-insipidus/symptoms/',
            ],
            [
                'initials' => ['อาเจียนเป็นเลือด', 'ถ่ายดำ', 'ปวดท้อง', 'ปวดตรงลิ้นปี่/ยอดอก'],
                'targets' => ['ถ่ายเป็นเลือด', 'ถ่ายดำ', 'อาเจียนเป็นเลือด', 'ปวดท้อง', 'ปวดตรงลิ้นปี่/ยอดอก'],
                'source' => 'NHS, Stomach ache and vomiting blood (vomiting blood, black or bloody stool, and abdominal pain are emergency combinations): https://www.nhs.uk/symptoms/stomach-ache/ ; https://www.nhs.uk/symptoms/vomiting-blood/',
            ],
        ];
    }
}
