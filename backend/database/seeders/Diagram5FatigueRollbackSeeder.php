<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram5FatigueRollbackSeeder extends Seeder
{
    private const DIAGRAM_ID = '00005';

    public function run(): void
    {
        DB::transaction(function () {
            $boxIds = DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', ['1', '2', '3', '4', '5', '5.1', '6', '7', '8', '9', '10', '11', '12'])
                ->pluck('box_id');

            if ($boxIds->isEmpty()) {
                $this->command?->warn('ไม่พบข้อมูลแผนภูมิที่ 5 ที่สร้างโดย Diagram5FatigueSeeder');

                return;
            }

            $ruleIds = DB::table('rule_conditions')
                ->whereIn('box_id', $boxIds)
                ->pluck('rule_id')
                ->unique()
                ->values();

            $hasAssessmentAnswers = DB::table('assessment_answers')
                ->whereIn('box_id', $boxIds)
                ->exists();
            $hasAssessmentResults = $ruleIds->isNotEmpty()
                && DB::table('assessment_results')->whereIn('rule_id', $ruleIds)->exists();

            if ($hasAssessmentAnswers || $hasAssessmentResults) {
                throw new RuntimeException(
                    'ไม่สามารถ rollback ได้ เพราะมีประวัติการประเมินที่อ้างอิงข้อมูลแผนภูมิที่ 5'
                );
            }

            DB::table('diagrams')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('entry_box_id', $boxIds)
                ->update(['entry_box_id' => null]);

            if ($ruleIds->isNotEmpty()) {
                DB::table('rule_next_diagrams')->whereIn('rule_id', $ruleIds)->delete();
                DB::table('rule_diseases')->whereIn('rule_id', $ruleIds)->delete();
                DB::table('rule_conditions')->whereIn('rule_id', $ruleIds)->delete();
                DB::table('diagnosis_rules')->whereIn('rule_id', $ruleIds)->delete();
            }

            DB::table('answer_choices')
                ->whereIn('box_id', $boxIds)
                ->delete();

            DB::table('question_boxes')
                ->whereIn('box_id', $boxIds)
                ->delete();
        });

        $this->command?->info('ย้อนข้อมูลที่สร้างโดย Diagram5FatigueSeeder สำเร็จ');
    }
}
