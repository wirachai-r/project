<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('main_symptoms')
            ->select(['symptom_id', 'symptom_name', 'symptom_image'])
            ->orderBy('symptom_id')
            ->get()
            ->each(function (object $symptom): void {
                if (str_starts_with((string) $symptom->symptom_image, 'health:')) {
                    return;
                }

                DB::table('main_symptoms')
                    ->where('symptom_id', $symptom->symptom_id)
                    ->update(['symptom_image' => $this->iconFor($symptom->symptom_name)]);
            });
    }

    public function down(): void
    {
        // Intentionally left unchanged so rolling back never removes icons
        // subsequently selected by an administrator.
    }

    private function iconFor(string $name): string
    {
        $rules = [
            'health:Fever' => ['ไข้', 'ตัวร้อน'],
            'health:Vomiting' => ['อาเจียน'],
            'health:Nauseous' => ['คลื่นไส้'],
            'health:Diarrhea' => ['ท้องเดิน', 'ท้องเสีย', 'ท้องร่วง', 'อุจจาระร่วง', 'บิด'],
            'health:Coughing' => ['ไอ'],
            'health:Lungs' => ['หอบ', 'เหนื่อยง่าย'],
            'health:Headache' => ['ปวดศีรษะ'],
            'health:Skull' => ['บาดเจ็บที่ศีรษะ', 'ศีรษะได้รับบาดเจ็บ'],
            'health:Dizzy' => ['เวียนศีรษะ', 'วิงเวียน', 'บ้านหมุน', 'หน้ามืด'],
            'health:Deaf' => ['หูตึง', 'หูหนวก'],
            'health:Ear' => ['หู'],
            'health:LowVision' => ['ตาฟ้าฟาง', 'ตามัว', 'เห็นภาพซ้อน', 'เห็นเงาหยากไย่', 'เห็นแสงแวบ'],
            'health:Eye' => ['ตา', 'หนังตา'],
            'health:Tooth' => ['ฟัน', 'เหงือก'],
            'health:Mouth' => ['ปาก', 'ริมฝีปาก', 'ลิ้น', 'เจ็บคอ', 'คันคอ', 'เสียงแหบ', 'ไม่มีเสียง', 'กลืนลำบาก'],
            'health:BackPain' => ['ปวดหลัง', 'ปวดต้นคอ', 'ปวดท้ายทอย'],
            'health:IntestinalPain' => ['ปวดท้อง', 'ลิ้นปี่', 'ท้องผูก', 'ท้องบวม', 'พยาธิ', 'ทวารหนัก', 'ถ่ายดำ', 'ถ่ายเป็น'],
            'health:Joints' => ['ข้ออักเสบ', 'ปวดข้อ', 'ปวดเอ็น'],
            'health:Bladder' => ['ปัสสาวะ', 'ขัดเบา', 'ท่อปัสสาวะ'],
            'health:FemaleReproductiveSystem' => ['ช่องคลอด', 'ประจำเดือน', 'ตกขาว'],
            'health:Pregnant' => ['ตั้งครรภ์'],
            'health:PenisAlt' => ['อวัยวะเพศในผู้ชาย', 'กามโรคในผู้ชาย'],
            'health:Body' => ['อวัยวะเพศในผู้หญิง', 'กามโรคในผู้หญิง', 'เต้านม'],
            'health:BloodDrop' => ['ตกเลือด', 'เลือดออก', 'เลือดกำเดา', 'เป็นเลือด'],
            'health:BloodCells' => ['ซีด', 'โลหิตจาง', 'จ้ำเขียว'],
            'health:Liver' => ['ดีซ่าน', 'ตาเหลือง'],
            'health:Neurology' => ['ชัก', 'ชา', 'อัมพาต', 'อ่อนแรง', 'เคลื่อนไหวผิดปกติ', 'เกร็ง', 'ปากเบี้ยว', 'คอเอียง'],
            'health:Tissue' => ['บวม', 'ก้อน', 'ผื่น', 'ตุ่ม', 'คัน', 'ผิวหนัง', 'วงด่าง', 'ผมร่วง', 'ผมบาง', 'ลมพิษ'],
            'health:Foot' => ['เท้า'],
            'health:Overweight' => ['น้ำหนักมาก', 'น้ำหนักขึ้น', 'อ้วน'],
            'health:Underweight' => ['น้ำหนักลด'],
            'health:Sweating' => ['เหงื่อออก'],
            'health:Virus' => ['หวัด'],
            'health:Poison' => ['งูกัด', 'แมลงต่อย'],
            'health:Symptom' => ['ช็อก', 'เป็นลม', 'สลบ', 'หมดสติ', 'อ่อนเพลีย'],
        ];

        foreach ($rules as $icon => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($name, $keyword)) {
                    return $icon;
                }
            }
        }

        return 'health:Symptom';
    }
};
