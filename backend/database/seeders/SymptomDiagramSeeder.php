<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * SymptomDiagramSeeder
 * Map ความสัมพันธ์ระหว่าง main_symptoms ↔ diagrams (many-to-many)
 *
 * อ่าน diagram number จาก main_symptoms.description ("แผนภูมิที่ X")
 * แล้ว map กับ diagrams.diagram_id (str_pad(X, 5, '0'))
 * → ไม่ต้อง hardcode map ใดๆ — sync กับ DiagnosisBookSeeder อัตโนมัติ
 */
class SymptomDiagramSeeder extends Seeder
{
    public function run(): void
    {
        // Lookup diagrams ทั้งหมด: [1 => '00001', 2 => '00002', ...]
        $allDiagrams = DB::table('diagrams')
            ->pluck('diagram_id', 'diagram_id')
            ->mapWithKeys(fn($id) => [(int)$id => $id]);

        // ดึง symptoms ทุกตัวที่มี description = "แผนภูมิที่ X"
        $symptoms = DB::table('main_symptoms')
            ->whereNotNull('description')
            ->where('description', 'like', 'แผนภูมิที่ %')
            ->get(['symptom_id', 'description']);

        $rows    = [];
        $missing = [];

        foreach ($symptoms as $symptom) {
            // parse เลขแผนภูมิจาก "แผนภูมิที่ 1", "แผนภูมิที่ 47" ฯลฯ
            if (!preg_match('/แผนภูมิที่\s+([\d,\s]+)/', $symptom->description, $m)) {
                $missing[] = "parse failed: [{$symptom->symptom_id}] {$symptom->description}";
                continue;
            }

            $nums = array_map('intval', explode(',', $m[1]));

            foreach ($nums as $num) {
                $diagramId = $allDiagrams[$num] ?? null;

                if (!$diagramId) {
                    $missing[] = "diagram {$num} not found (symptom_id: {$symptom->symptom_id})";
                    continue;
                }

                $key = "{$symptom->symptom_id}|{$diagramId}";
                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'symptom_id' => $symptom->symptom_id,
                        'diagram_id' => $diagramId,
                    ];
                }
            }

            if (!$diagramId) {
                $missing[] = "diagram {$num} not found (symptom_id: {$symptom->symptom_id})";
                continue;
            }

            $key = "{$symptom->symptom_id}|{$diagramId}";
            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'symptom_id' => $symptom->symptom_id,
                    'diagram_id' => $diagramId,
                ];
            }
        }

        if (!empty($rows)) {
            foreach (array_chunk(array_values($rows), 100) as $chunk) {
                DB::table('symptom_diagrams')->insertOrIgnore($chunk);
            }
        }

        $this->command->info('✅ SymptomDiagramSeeder สำเร็จ');
        $this->command->info('   🔗 rows inserted : ' . count($rows));

        if (!empty($missing)) {
            $this->command->warn('⚠️  ข้ามรายการต่อไปนี้:');
            foreach ($missing as $m) {
                $this->command->warn("   - {$m}");
            }
        }
    }
}
