<?php

namespace Database\Seeders;

use App\Models\BodyAreaGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BodyAreaGroupSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['name' => 'ศีรษะและลำคอ', 'name_en' => 'Head and neck', 'description' => 'ศีรษะ ตา หู จมูก ปาก และลำคอ', 'image' => 'head-neck.png', 'categories' => ['000002', '000003', '000004', '000005', '000006']],
            ['name' => 'ช่วงอกและลำตัว', 'name_en' => 'Chest and torso', 'description' => 'หน้าอก ช่องท้อง หลัง และเอว', 'image' => 'torso.png', 'categories' => ['000007', '000008']],
            ['name' => 'แขน ขา และช่วงล่าง', 'name_en' => 'Limbs and lower body', 'description' => 'แขน มือ สะโพก ขา เท้า และระบบขับถ่าย', 'image' => 'lower-body.png', 'categories' => ['000010', '000011', '000012', '000013']],
            ['name' => 'อาการทั่วไป', 'name_en' => 'General symptoms', 'description' => 'ไข้ อ่อนเพลีย ผิวหนัง และอาการที่ระบุตำแหน่งไม่ได้', 'image' => 'general.png', 'categories' => ['000001', '000009', '000014']],
        ];

        foreach ($definitions as $order => $definition) {
            $source = database_path('seeders/assets/body-area-groups/'.$definition['image']);
            $path = 'body_area_groups/'.$definition['image'];
            if (is_file($source)) {
                Storage::disk('public')->put($path, file_get_contents($source));
            }

            $group = BodyAreaGroup::updateOrCreate(
                ['name' => $definition['name']],
                [
                    'name_en' => $definition['name_en'],
                    'description' => $definition['description'],
                    'image_path' => $path,
                    'display_order' => $order + 1,
                    'status' => '1',
                ],
            );

            $symptomIds = DB::table('main_symptoms')
                ->whereIn('symptom_category_id', $definition['categories'])
                ->orderBy('symptom_name')
                ->pluck('symptom_id');
            $group->symptoms()->sync($symptomIds->mapWithKeys(
                fn ($id, $index) => [$id => ['display_order' => $index]],
            )->all());
        }
    }
}
