<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LINKS = [
        'ช่วงอกและลำตัว' => [
            'หลัง' => ['ปวดหลัง'],
            'เอวและสีข้าง' => ['ปวดหลัง'],
        ],
        'แขน ขา และช่วงล่าง' => [
            'ไหล่และแขน' => ['ปวดข้อ', 'ข้ออักเสบ', 'ปวดเอ็น'],
            'เข่า' => ['ปวดข้อ', 'ข้ออักเสบ', 'ปวดเอ็น'],
        ],
    ];

    public function up(): void
    {
        foreach (self::LINKS as $groupName => $subgroups) {
            $groupId = DB::table('body_area_groups')->where('name', $groupName)->value('id');
            if (! $groupId) {
                continue;
            }

            foreach ($subgroups as $subgroupName => $symptomNames) {
                $subgroupId = DB::table('body_area_subgroups')
                    ->where('body_area_group_id', $groupId)
                    ->where('name', $subgroupName)
                    ->value('id');
                if (! $subgroupId) {
                    continue;
                }

                $symptomIds = DB::table('main_symptoms')
                    ->whereIn('symptom_name', $symptomNames)
                    ->pluck('symptom_id');

                foreach ($symptomIds as $order => $symptomId) {
                    DB::table('body_area_group_symptoms')->insertOrIgnore([
                        'body_area_group_id' => $groupId,
                        'symptom_id' => $symptomId,
                        'display_order' => 999 + $order,
                    ]);
                    DB::table('body_area_subgroup_symptoms')->insertOrIgnore([
                        'body_area_subgroup_id' => $subgroupId,
                        'symptom_id' => $symptomId,
                        'display_order' => $order,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Preserve links because administrators may review or edit them later.
    }
};
