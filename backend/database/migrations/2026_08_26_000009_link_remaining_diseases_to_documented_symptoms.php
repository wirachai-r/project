<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LINKS = [
        'กระดูกพรุน' => ['ปวดข้อ', 'ปวดหลัง'],
        'กลุ่มอาการถุงน้ำรังไข่หลายใบ/พีซีโอเอส' => ['ประจำเดือนขาด/ไม่มา', 'ประจำเดือนออกมาก'],
        'ช่องคลอดอักเสบ' => ['คันในช่องคลอด', 'ตกขาว'],
        'ตาเข' => ['เห็นภาพซ้อน', 'ปวดศีรษะ', 'คลื่นไส้', 'อาเจียน', 'ท้องเดิน (ท้องเสีย ท้องร่วง)'],
        'บิด' => ['บิด', 'ถ่ายเป็นมูก/มูกปนเลือด'],
        'ผิวหนังอักเสบชนิดเกล็ดรังแค' => ['คัน', 'คันศีรษะ', 'ผื่นขึ้นตามตัว', 'โรคผิวหนัง'],
        'ฝีรอบทวารหนัก/ฝีคัณฑสูตร' => ['คันก้น', 'ก้อนบวม (มีก้อน)', 'ไข้'],
        'ภาวะเลือดเซาะผนังหลอดเลือดแดงใหญ่' => ['เจ็บหน้าอก', 'ปวดหลัง', 'แขนขาอ่อนแรง', 'เป็นลม'],
        'มะเร็งทอนซิล' => ['เจ็บคอ', 'ปวดหู', 'กลืนลำบาก', 'คอบวม'],
        'หมดสติ' => ['หมดสติ', 'หอบ', 'อัมพาต', 'ปากเบี้ยว', 'ชัก', 'ไข้'],
        'อาเจียนในเด็ก' => ['อาเจียน'],
        'แผลเปื่อยที่ปาก' => ['ปากเปื่อย', 'แผลในปาก'],
        'แผลเปื่อยในปากที่เกิดจากการบาดเจ็บ' => ['ปากเปื่อย', 'แผลในปาก', 'เจ็บปาก'],
        'โรคติดเชื้อแบคทีเรียของผิวหนัง' => ['โรคผิวหนัง'],
        'โรคพยาธิใบไม้ตับ' => [
            'ปวดท้อง', 'คลื่นไส้', 'อาเจียน', 'ท้องผูก', 'ท้องเดิน (ท้องเสีย ท้องร่วง)',
            'ไข้', 'ดีซ่าน', 'อ่อนเพลีย', 'น้ำหนักลด', 'คัน', 'เท้าบวม',
        ],
        'ไขมันในเลือดสูง/ไขมันในเลือดผิดปกติ' => ['เจ็บหน้าอก', 'อัมพาต', 'ปวดเอ็น'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::LINKS as $diseaseName => $symptomNames) {
            $diseaseId = DB::table('diseases')->where('disease_name', $diseaseName)->value('disease_id');
            if (! $diseaseId) {
                continue;
            }

            $symptomIds = DB::table('main_symptoms')
                ->whereIn('symptom_name', $symptomNames)
                ->pluck('symptom_id');

            foreach ($symptomIds as $symptomId) {
                DB::table('disease_symptoms')->insertOrIgnore([
                    'disease_id' => $diseaseId,
                    'symptom_id' => $symptomId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Preserve medical-content links that may have been reviewed or edited.
    }
};
