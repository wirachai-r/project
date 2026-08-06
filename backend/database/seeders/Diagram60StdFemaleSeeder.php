<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram60StdFemaleSeeder extends Seeder
{
    private const DIAGRAM_ID = '00060';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '2', '2.1', '2.1.1', '2.2',
                '3', '3.1', '3.2', '4', '4.1', '5'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 60 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 60
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'กามโรคในผู้หญิง (STD IN FEMALE)',
                'diagram_name_en' => 'Sexually Transmitted Diseases in Female',
                'description' => 'มีอาการขัดเบา ปวดท้องน้อย ตกขาว เป็นแผลหรือตุ่มที่อวัยวะเพศ ไข่ดันบวม หรือมีความผิดปกติที่ชวนสงสัยว่าเป็นกามโรค สาเหตุที่พบบ่อย หนองใน (208) แผลริมอ่อน (210) เริม (187) หิด (195) ฝีมะม่วง (212) ซิฟิลิส (211) ถ้าอาการไม่ชัดเจน และมีประวัติเพศสัมพันธ์กับผู้ป่วยกามโรค ควรแนะนำให้แพทย์ตรวจภายในช่องคลอด',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes)
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'ปวดท้องน้อย และขัดเบา? มีหนองไหลออกจากท่อปัสสาวะ? หรือ ตกขาวมีลักษณะเป็นสีเหลืองหรือสีเขียว และมีกลิ่นเหม็น?'],
                'B1_1'    => ['frame' => '1.1',   'type' => 'S', 'q' => 'มีไข้?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'เป็นแผลที่อวัยวะเพศ?'],
                'B2_1'    => ['frame' => '2.1',   'type' => 'S', 'q' => 'แผลเดียวหรือ 2 แผลชนกัน?'],
                'B2_1_1'  => ['frame' => '2.1.1', 'type' => 'S', 'q' => 'แผลขอบแข็ง ไม่เจ็บ และพบหลังติดเชื้อ 10-90 วัน?'],
                'B2_2'    => ['frame' => '2.2',   'type' => 'S', 'q' => 'แผลริมอ่อน ขอบไม่ชัด เลือดออกง่ายและเจ็บ มีหลายแผล และพบหลังติดเชื้อ 2-7 วัน?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'ขึ้นเป็นตุ่มเล็กๆ?'],
                'B3_1'    => ['frame' => '3.1',   'type' => 'S', 'q' => 'ตุ่มคันมากและลามได้? หรือ พบตุ่มคันที่ง่ามมือ ง่ามเท้าร่วมด้วย?'],
                'B3_2'    => ['frame' => '3.2',   'type' => 'S', 'q' => 'ตุ่มใสเล็กๆ อยู่เป็นกลุ่ม เกิดหลังติดเชื้อ 4-7 วัน หรือเป็นๆ หายๆ เรื้อรัง?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'ต่อมน้ำเหลืองที่ขาหนีบโต?'],
                'B4_1'    => ['frame' => '4.1',   'type' => 'S', 'q' => 'มีประวัติเพศสัมพันธ์กับผู้ป่วยกามโรค หรือมีเพศสัมพันธ์เสรี?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'มีประวัติเพศสัมพันธ์กับผู้ป่วยกามโรค หรือมีเพศสัมพันธ์เสรี?'],
            ];

            // สร้าง box_id ถัดไปอัตโนมัติ
            $nextBoxId = ((int) DB::table('question_boxes')->max('box_id')) + 1;
            foreach ($boxes as &$box) {
                $box['id'] = str_pad((string) $nextBoxId++, 10, '0', STR_PAD_LEFT);
            }
            unset($box);

            // Insert คำถามลง DB
            foreach ($boxes as $box) {
                DB::table('question_boxes')->updateOrInsert(['box_id' => $box['id']], [
                    'frame_number' => $box['frame'],
                    'question_text' => $box['q'],
                    'question_text_en' => null,
                    'question_type' => $box['type'],
                    'min_required' => null,
                    'detail' => null,
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);
            }

            // ตั้งค่า Entry Box
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'entry_box_id' => $boxes['B1']['id'],
            ]);

            // 3. กำหนด Choice และ Flow
            $choiceNumber = ((int) DB::table('answer_choices')->max('choice_id')) + 1;
            $terminalChoices = [];
            $addChoice = function (string $boxKey, string $text, ?string $nextBoxKey, ?string $ruleKey, int $order) use (&$choiceNumber, &$terminalChoices, $boxes, $now) {
                $choiceId = str_pad((string) $choiceNumber++, 10, '0', STR_PAD_LEFT);
                DB::table('answer_choices')->updateOrInsert(['choice_id' => $choiceId], [
                    'choice_text' => $text,
                    'choice_text_en' => null,
                    'order' => $order,
                    'status' => '1',
                    'box_id' => $boxes[$boxKey]['id'],
                    'next_box_id' => $nextBoxKey ? $boxes[$nextBoxKey]['id'] : null,
                    'next_diagram_id' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);
                if ($ruleKey) {
                    $terminalChoices[$ruleKey][] = ['box_id' => $boxes[$boxKey]['id'], 'choice_id' => $choiceId];
                }
            };

            // โครงสร้างการตัดสินใจแบบ Binary
            $binary = [
                'B1'     => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'   => [['pelvic_endometritis_severe_24h', null], ['gonorrhea_or_others_3d', null]],
                'B2'     => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'   => [[null, 'B2_1_1'], [null, 'B2_2']],
                'B2_1_1' => [['syphilis_female', null], [null, 'B2_2']],
                'B2_2'   => [['chancroid_female', null], ['ulcer_eval_1w_female', null]],
                'B3'     => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'   => [['scabies_female', null], [null, 'B3_2']],
                'B3_2'   => [['herpes_simplex_female', null], ['symptomatic_treatment_papule_female', null]],
                'B4'     => [[null, 'B4_1'], [null, 'B5']],
                'B4_1'   => [['lymphogranuloma_venereum_female', null], ['groin_mass_diagram14_female', null]],
                'B5'     => [['recommend_pelvic_exam', null], ['observation_and_reassess_female', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนด Diagnosis Rules
            $rules = [
                'pelvic_endometritis_severe_24h' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจเป็นปีกมดลูกอักเสบ/เยื่อบุมดลูกอักเสบ (147) ระยะรุนแรง
NOTE,
                    'refs'       => ['147'],
                    'diagrams'   => [],
                ],
                'gonorrhea_or_others_3d' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน อาจเป็นหนองใน (208)/อื่น ๆ
NOTE,
                    'refs'       => ['208'],
                    'diagrams'   => [],
                ],
                'syphilis_female' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ซิฟิลิส (211)
• ชันสูตรเพิ่มเติม
• ยาปฏิชีวนะรักษาซิฟิลิส
NOTE,
                    'refs'       => ['211'],
                    'diagrams'   => [],
                ],
                'chancroid_female' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
แผลริมอ่อน (210)
• ชันสูตรเพิ่มเติม
• ยาปฏิชีวนะรักษาแผลริมอ่อน
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['210'],
                    'diagrams'   => [],
                ],
                'ulcer_eval_1w_female' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'scabies_female' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
หิด (195)
• ทาเบนซิลเบนโซเอต (ย25.4)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['195'],
                    'diagrams'   => [],
                ],
                'herpes_simplex_female' => [
                    'urgency'    => 'P',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เริมที่อวัยวะเพศ (187)
• ชะด้วยทิงเจอร์ใส่แผลสดหรือโพวิโดนไอโอดีน
• อะไซโคลเวียร์ (ย4.17)
⊕ ถ้าเป็นรุนแรง หรือพบในหญิงตั้งครรภ์
NOTE,
                    'refs'       => ['187'],
                    'diagrams'   => [],
                ],
                'symptomatic_treatment_papule_female' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
• รักษาตามอาการ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'lymphogranuloma_venereum_female' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ฝีมะม่วง (212)
• ชันสูตรเพิ่มเติม
• ยาปฏิชีวนะรักษาฝีมะม่วง
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['212'],
                    'diagrams'   => [],
                ],
                'groin_mass_diagram14_female' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 14 บวมเฉพาะที่/มีก้อน กรอบที่ 10.2
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00014'],
                ],
                'recommend_pelvic_exam' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
แนะนำให้แพทย์ตรวจภายใน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'observation_and_reassess_female' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• สังเกตอาการ
• ถ้ามีอาการผิดปกติ ควรตรวจดูอาการเพิ่มเติม
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
            ];

            // Fix syntax array key error in rule definition
            $rules['pelvic_endometritis_severe_24h']['time_frame'] = 'ภายใน 24 ชั่วโมง';

            // 5. Insert Rules, Conditions, Diseases, Next Diagrams
            $ruleNumber = ((int) DB::table('diagnosis_rules')->max('rule_id')) + 1;
            $conditionNumber = ((int) DB::table('rule_conditions')->max('condition_id')) + 1;

            foreach ($rules as $key => $rule) {
                $ruleId = str_pad((string) $ruleNumber, 10, '0', STR_PAD_LEFT);
                DB::table('diagnosis_rules')->updateOrInsert(['rule_id' => $ruleId], [
                    'urgency_level' => $rule['urgency'],
                    'time_frame' => $rule['time_frame'],
                    'time_frame_en' => null,
                    'note' => $rule['note'],
                    'note_en' => null,
                    'medical_reference' => 'แผนภูมิที่ 60',
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

                // Insert Conditions
                DB::table('rule_conditions')->where('rule_id', $ruleId)->delete();
                foreach ($terminalChoices[$key] ?? [] as $conditionIndex => $condition) {
                    DB::table('rule_conditions')->insert([
                        'condition_id' => str_pad((string) $conditionNumber++, 10, '0', STR_PAD_LEFT),
                        'rule_id' => $ruleId,
                        'box_id' => $condition['box_id'],
                        'choice_id' => $condition['choice_id'],
                        'logic_operator' => $conditionIndex === 0 ? 'AND' : 'OR',
                        'status' => '1',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                // Insert Rule Diseases
                DB::table('rule_diseases')->where('rule_id', $ruleId)->delete();
                foreach ($rule['refs'] as $order => $reference) {
                    $diseaseId = DB::table('diseases')->where('reference', $reference)->value('disease_id');
                    if ($diseaseId) {
                        DB::table('rule_diseases')->insert([
                            'rule_id' => $ruleId,
                            'disease_id' => $diseaseId,
                            'display_order' => $order,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }

                // Insert Rule Next Diagrams
                DB::table('rule_next_diagrams')->where('rule_id', $ruleId)->delete();
                foreach ($rule['diagrams'] as $order => $diagramId) {
                    DB::table('rule_next_diagrams')->insert([
                        'rule_id' => $ruleId,
                        'diagram_id' => $diagramId,
                        'display_order' => $order,
                        'prompt_text' => 'ต้องการประเมินอาการนี้ต่อหรือไม่',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $ruleNumber++;
            }
        });

        $this->command->info('สร้างแผนภูมิที่ 60 (กามโรคในผู้หญิง) สำเร็จ');
    }
}
