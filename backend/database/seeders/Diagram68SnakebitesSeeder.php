<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram68SnakebitesSeeder extends Seeder
{
    private const DIAGRAM_ID = '00068';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.1.1', '2', '3', '3.1', '3.2',
                '4', '5', '6', '6.1', '6.2'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 68 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 68
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'งูกัด (SNAKEBITES)',
                'diagram_name_en' => 'Snakebites',
                'description' => 'ถูกงูกัด หรือสงสัยว่าถูกงูกัด สาเหตุที่พบบ่อย งูเขียวหางไหม้ งูเห่า งูแมวเซา งูกะปะ ถ้าอาการไม่ชัดเจน และไม่มีอาการผิดปกติแสดงให้เห็น ให้รักษาตามอาการและเฝ้าสังเกตอาการเปลี่ยนแปลงอย่างใกล้ชิด',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes)
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'เห็นตัวงูแน่ชัด หรือจับตัวงูได้?'],
                'B1_1'    => ['frame' => '1.1',   'type' => 'S', 'q' => 'เป็นงูพิษ?'],
                'B1_1_1'  => ['frame' => '1.1.1', 'type' => 'S', 'q' => 'มีอาการผิดปกติ (เช่น หนังตาตก อัมพาต เลือดออก)?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'ตรวจพบรอยเขี้ยว? หรือ มีอาการผิดปกติ?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'หนังตาตก? แขนขาเป็นอัมพาต? หรือ หยุดหายใจ?'],
                'B3_1'    => ['frame' => '3.1',   'type' => 'S', 'q' => 'ถูกกัดในทะเล และ ปัสสาวะดำเข้ม?'],
                'B3_2'    => ['frame' => '3.2',   'type' => 'S', 'q' => 'ถูกกัดในป่าลึก?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'มีเลือดออกตามไรฟัน? อาเจียนเป็นเลือด? ถ่ายเป็นเลือด? มีจ้ำเขียวขึ้นตามตัว? หรือ ทดสอบระยะการจับตัวเป็นลิ่มเลือด พบว่านานกว่าปกติ?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'ปวดเมื่อยตามกล้ามเนื้อมาก ปัสสาวะดำเข้มและถูกกัดในทะเล?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => 'แผลบวม และปวดมาก?'],
                'B6_1'    => ['frame' => '6.1',   'type' => 'S', 'q' => 'ถูกกัดในบริเวณชายทะเล หรือในถิ่นที่มีงูกะปะชุมชุม?'],
                'B6_2'    => ['frame' => '6.2',   'type' => 'S', 'q' => 'ถูกกัดในบริเวณบ้าน?'],
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
                'B1_1'   => [[null, 'B1_1_1'], ['non_venomous_snake', null]],
                'B1_1_1' => [['venomous_symptoms_urgent', null], ['venomous_no_symptoms_observe', null]],
                'B2'     => [[null, 'B3'], ['no_fang_marks_no_symptoms', null]],
                'B3'     => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'   => [['sea_snake_neuro_urgent', null], [null, 'B3_2']],
                'B3_2'   => [['king_cobra_cobra_urgent', null], ['cobra_banded_krait_urgent', null]],
                'B4'     => [['hematotoxin_snake_urgent', null], [null, 'B5']],
                'B5'     => [['sea_snake_myotoxin_urgent', null], [null, 'B6']],
                'B6'     => [[null, 'B6_1'], ['local_swelling_observe', null]],
                'B6_1'   => [['malayan_pit_viper_bite', null], [null, 'B6_2']],
                'B6_2'   => [['green_pit_viper_bite', null], ['unidentified_local_swelling_bite', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนด Diagnosis Rules
            $rules = [
                'non_venomous_snake' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไม่ใช่งูพิษกัด
• รักษาตามอาการ
• หากไม่แน่ใจควรสังเกตอาการอย่างใกล้ชิดนาน 12 ชั่วโมง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'venomous_symptoms_urgent' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
• เป่าปากถ้าหยุดหายใจ
• ให้เซรุ่มแก้พิษงู ตามชนิดของงูที่สงสัย
• รักษาตามอาการ
⊕ ด่วน
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'venomous_no_symptoms_observe' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ให้การรักษาตามอาการ
• สังเกตอาการอย่างใกล้ชิดนาน 12 ชั่วโมง
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'no_fang_marks_no_symptoms' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ไม่พบรอยเขี้ยวและไม่มีอาการผิดปกติ
• ให้การรักษาตามอาการ และสังเกตอาการเปลี่ยนแปลง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'sea_snake_neuro_urgent' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
งูทะเล (221)
⊕ ด่วน
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'king_cobra_cobra_urgent' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
งูจงอาง/งูเห่ากัด (221)
• เป่าปากถ้าหยุดหายใจ
• เซรุ่มแก้พิษงู
⊕ ด่วน
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'cobra_banded_krait_urgent' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
งูเห่า/งูสามเหลี่ยมกัด (221)
• เป่าปากถ้าหยุดหายใจ
• เซรุ่มแก้พิษงู
⊕ ด่วน
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'hematotoxin_snake_urgent' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
งูแมวเซา/งูกะปะ/งูเขียวหางไหม้ (221)
• ให้เซรุ่มแก้พิษงู ตามชนิดของงูที่สงสัย
⊕ ด่วน
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'sea_snake_myotoxin_urgent' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
งูทะเลกัด (221)
⊕ ด่วน
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'local_swelling_observe' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ และเฝ้าดูอาการ 12 ชั่วโมง (ถ้าสงสัยงูเห่ากัด ควรเฝ้าระวังอย่างใกล้ชิด)
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'malayan_pit_viper_bite' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
งูกะปะกัด (221)
• พาราเซตามอล (ย1.2) บรรเทาปวด
• ฉีดยาป้องกันบาดทะยัก
• เฝ้าดูอาการ 12 ชั่วโมง
• ให้เซรุ่มแก้พิษงูในกรณีที่มีอาการบวมรุนแรง
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'green_pit_viper_bite' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
งูเขียวหางไหม้กัด (221)
• พาราเซตามอล (ย1.2) บรรเทาปวด
• ฉีดยาป้องกันบาดทะยัก
• เฝ้าดูอาการ 12 ชั่วโมง
• ให้เซรุ่มแก้พิษงูในกรณีที่มีอาการบวมรุนแรง
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'unidentified_local_swelling_bite' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• พาราเซตามอล (ย1.2) บรรเทาปวด
• ฉีดยาป้องกันบาดทะยัก
• เฝ้าดูอาการ 12 ชั่วโมง
• ให้เซรุ่มแก้พิษงูในกรณีที่มีอาการบวมรุนแรง
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
            ];

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
                    'medical_reference' => 'แผนภูมิที่ 68',
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

        $this->command->info('สร้างแผนภูมิที่ 68 (งูกัด) สำเร็จ');
    }
}
