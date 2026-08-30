<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const RULES = [
        'ศีรษะและลำคอ' => [
            'ดวงตา' => ['ตา', 'หนังตา', 'เห็นภาพ', 'เห็นเงา', 'เห็นแสง'],
            'หู' => ['หู', 'ได้ยิน'],
            'จมูกและไซนัส' => ['จมูก', 'น้ำมูก', 'กำเดา'],
            'ปากและฟัน' => ['ปาก', 'ฟัน', 'เหงือก', 'ลิ้น', 'ริมฝีปาก', 'คาง', 'ใบหน้า'],
            'ลำคอ' => ['คอ', 'ไทรอยด์', 'กลืน', 'เสียงแหบ', 'ไม่มีเสียง'],
            'ศีรษะ' => ['ศีรษะ', 'หัว', 'เวียน', 'บ้านหมุน', 'ชัก', 'สลบ', 'หมดสติ', 'เป็นลม', 'หน้ามืด', 'อัมพาต'],
        ],
        'ช่วงอกและลำตัว' => [
            'หน้าอก' => ['หน้าอก', 'ทรวงอก', 'หอบ', 'ไอ', 'ใจสั่น'],
            'หลัง' => ['หลัง'],
            'เอวและสีข้าง' => ['เอว', 'สีข้าง'],
            'ช่องท้อง' => ['ท้อง', 'ลิ้นปี่', 'คลื่นไส้', 'อาเจียน', 'ถ่าย', 'อุจจาระ', 'พยาธิ', 'บิด', 'ทวารหนัก'],
        ],
        'แขน ขา และช่วงล่าง' => [
            'มือและข้อมือ' => ['มือ', 'ข้อมือ', 'นิ้วมือ', 'ฝ่ามือ'],
            'ไหล่และแขน' => ['ไหล่', 'แขน', 'ข้อศอก'],
            'สะโพกและต้นขา' => ['สะโพก', 'ขาหนีบ', 'ต้นขา'],
            'เข่า' => ['เข่า'],
            'ขา ข้อเท้า และเท้า' => ['ขา', 'เท้า', 'ข้อเท้า', 'ตะคริว'],
            'อุ้งเชิงกรานและระบบขับถ่าย' => [
                'ปัสสาวะ', 'ช่องคลอด', 'ประจำเดือน', 'อวัยวะเพศ', 'อัณฑะ',
                'ไข่ดัน', 'ตกขาว', 'ตั้งครรภ์', 'ท้องน้อย', 'ท่อปัสสาวะ', 'กามโรค',
            ],
        ],
        'อาการทั่วไป' => [
            'ผิวหนัง' => ['คัน', 'ผื่น', 'ตุ่ม', 'ผิวหนัง', 'ลมพิษ', 'วงด่าง', 'ผมร่วง', 'แมลง', 'งูกัด', 'จุดแดง', 'จ้ำเขียว'],
            'การนอนหลับและพลังงาน' => ['นอน', 'อ่อนเพลีย', 'เหนื่อยง่าย'],
            'อาการทั่วร่างกาย' => ['ไข้', 'ตัวร้อน', 'ซีด', 'ดีซ่าน', 'ตาเหลือง', 'น้ำหนัก', 'บวมทั่วไป', 'อ้วน', 'โลหิตจาง', 'เหงื่อ'],
            'อาการอื่น ๆ' => ['ช็อก'],
        ],
    ];

    public function up(): void
    {
        Schema::table('body_area_subgroup_symptoms', function (Blueprint $table) {
            $table->dropForeign(['symptom_id']);
        });
        Schema::table('body_area_subgroup_symptoms', function (Blueprint $table) {
            $table->char('symptom_id', 10)->change();
        });
        Schema::table('body_area_subgroup_symptoms', function (Blueprint $table) {
            $table->foreign('symptom_id')->references('symptom_id')->on('main_symptoms')->cascadeOnDelete();
        });

        foreach (self::RULES as $groupName => $subgroupRules) {
            $groupId = DB::table('body_area_groups')->where('name', $groupName)->value('id');
            if (! $groupId) {
                continue;
            }

            $symptoms = DB::table('body_area_group_symptoms')
                ->join('main_symptoms', 'main_symptoms.symptom_id', '=', 'body_area_group_symptoms.symptom_id')
                ->where('body_area_group_symptoms.body_area_group_id', $groupId)
                ->select('main_symptoms.symptom_id', 'main_symptoms.symptom_name')
                ->get();

            foreach ($subgroupRules as $subgroupName => $keywords) {
                $subgroupId = DB::table('body_area_subgroups')
                    ->where('body_area_group_id', $groupId)
                    ->where('name', $subgroupName)
                    ->value('id');
                if (! $subgroupId) {
                    continue;
                }

                $order = 0;
                foreach ($symptoms as $symptom) {
                    if (! $this->containsAny($symptom->symptom_name, $keywords)) {
                        continue;
                    }

                    DB::table('body_area_subgroup_symptoms')->updateOrInsert(
                        [
                            'body_area_subgroup_id' => $subgroupId,
                            'symptom_id' => $symptom->symptom_id,
                        ],
                        ['display_order' => $order++],
                    );
                }
            }
        }
    }

    public function down(): void
    {
        $names = collect(self::RULES)->flatMap(fn ($rules) => array_keys($rules))->unique();
        $ids = DB::table('body_area_subgroups')->whereIn('name', $names)->pluck('id');
        DB::table('body_area_subgroup_symptoms')->whereIn('body_area_subgroup_id', $ids)->delete();
    }

    private function containsAny(string $value, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (mb_stripos($value, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
};
