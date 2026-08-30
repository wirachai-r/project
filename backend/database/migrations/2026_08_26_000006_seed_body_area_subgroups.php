<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SUBGROUPS = [
        'ศีรษะและลำคอ' => [
            ['ศีรษะ', 'บริเวณศีรษะและหนังศีรษะ'],
            ['ดวงตา', 'ดวงตา เปลือกตา และบริเวณรอบดวงตา'],
            ['หู', 'ใบหู ช่องหู และบริเวณรอบหู'],
            ['จมูกและไซนัส', 'จมูก โพรงจมูก และไซนัส'],
            ['ปากและฟัน', 'ริมฝีปาก ช่องปาก เหงือก ลิ้น และฟัน'],
            ['ลำคอ', 'ลำคอ คอหอย และบริเวณรอบคอ'],
        ],
        'ช่วงอกและลำตัว' => [
            ['หน้าอก', 'บริเวณหน้าอกและทรวงอก'],
            ['ช่องท้อง', 'บริเวณหน้าท้องและช่องท้อง'],
            ['หลัง', 'บริเวณแผ่นหลังส่วนบนและส่วนกลาง'],
            ['เอวและสีข้าง', 'บริเวณเอวและด้านข้างของลำตัว'],
        ],
        'แขน ขา และช่วงล่าง' => [
            ['ไหล่และแขน', 'หัวไหล่ ต้นแขน และข้อศอก'],
            ['มือและข้อมือ', 'ข้อมือ ฝ่ามือ นิ้วมือ และเล็บ'],
            ['สะโพกและต้นขา', 'สะโพก ขาหนีบ และต้นขา'],
            ['เข่า', 'หัวเข่าและบริเวณรอบข้อเข่า'],
            ['ขา ข้อเท้า และเท้า', 'ขาส่วนล่าง ข้อเท้า ฝ่าเท้า และนิ้วเท้า'],
            ['อุ้งเชิงกรานและระบบขับถ่าย', 'บริเวณอุ้งเชิงกรานและอวัยวะที่เกี่ยวข้องกับการขับถ่าย'],
        ],
        'อาการทั่วไป' => [
            ['อาการทั่วร่างกาย', 'อาการที่เกิดขึ้นทั่วร่างกายหรือระบุตำแหน่งไม่ได้'],
            ['ผิวหนัง', 'อาการที่ผิวหนังหลายบริเวณหรือทั่วร่างกาย'],
            ['การนอนหลับและพลังงาน', 'อาการที่เกี่ยวข้องกับการนอนหลับและระดับพลังงาน'],
            ['อาการอื่น ๆ', 'อาการทั่วไปที่ไม่อยู่ในบริเวณย่อยอื่น'],
        ],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::SUBGROUPS as $groupName => $subgroups) {
            $groupId = DB::table('body_area_groups')->where('name', $groupName)->value('id');
            if (! $groupId) {
                continue;
            }

            foreach ($subgroups as $order => [$name, $description]) {
                DB::table('body_area_subgroups')->updateOrInsert(
                    ['body_area_group_id' => $groupId, 'name' => $name],
                    [
                        'description' => $description,
                        'display_order' => $order,
                        'status' => '1',
                        'updated_at' => $now,
                        'created_at' => $now,
                    ],
                );
            }
        }
    }

    public function down(): void
    {
        foreach (self::SUBGROUPS as $groupName => $subgroups) {
            $groupId = DB::table('body_area_groups')->where('name', $groupName)->value('id');
            if (! $groupId) {
                continue;
            }

            DB::table('body_area_subgroups')
                ->where('body_area_group_id', $groupId)
                ->whereIn('name', array_column($subgroups, 0))
                ->delete();
        }
    }
};
