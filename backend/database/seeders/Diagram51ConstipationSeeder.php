<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram51ConstipationSeeder extends Seeder
{
    private const DIAGRAM_ID = '00051';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            // รายการกรอบทั้งหมดในแผนภูมิที่ 51
            $frameNumbers = ['1', '2', '3', '4', '5', '6', '7'];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 51 ถูก seed แล้ว กรุณารัน Rollback หรือลบข้อมูลเดิมก่อน'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 51
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ท้องผูก (CONSTIPATION)',
                'diagram_name_en' => 'Constipation',
                'description' => 'ถ่ายอุจจาระแข็งหรือไม่ถ่ายอุจจาระนานหลายวัน (เว้นระยะนานกว่าที่เคยเป็นอยู่อย่างเป็นปกตินิสัย) สาเหตุที่พบบ่อย ขาดการออกกำลังกาย กินผักผลไม้น้อย ดื่มน้ำน้อย โรควิตกกังวล/โรควิตกกังวลทั่วไป (88) ริดสีดวงทวาร (58) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 7',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 51
            $boxes = [
                'B1' => ['frame' => '1', 'type' => 'S', 'q' => 'ปวดท้องรุนแรง? หรือ อาเจียนรุนแรง?'],
                'B2' => ['frame' => '2', 'type' => 'S', 'q' => 'น้ำหนักลดฮวบ? หรือ มีอาการท้องผูกสลับท้องเดิน เป็นๆ หายๆ นานกว่า 1 เดือน?'],
                'B3' => ['frame' => '3', 'type' => 'S', 'q' => 'ถ่ายเป็นเลือดสด?'],
                'B4' => ['frame' => '4', 'type' => 'S', 'q' => 'มีประวัติกินยา* หรือดื่มชา กาแฟ?'],
                'B5' => ['frame' => '5', 'type' => 'S', 'q' => 'พบในเด็กเล็กที่ถูกบังคับให้นั่งกระโถน?'],
                'B6' => ['frame' => '6', 'type' => 'S', 'q' => 'มีอาการมากกว่า 12 สัปดาห์ ในช่วงเวลา 12 เดือนที่ผ่านมา? หรือ มีความเครียด วิตกกังวล ซึมเศร้า หรือนอนไม่หลับ?'],
                'B7' => ['frame' => '7', 'type' => 'S', 'q' => 'ข้อแนะนำการดูแลรักษาอาการท้องผูกทั่วไป'],
            ];

            // รันสร้าง box_id ต่อเนื่อง
            $nextBoxId = ((int) DB::table('question_boxes')->max('box_id')) + 1;
            foreach ($boxes as &$box) {
                $box['id'] = str_pad((string) $nextBoxId++, 10, '0', STR_PAD_LEFT);
            }
            unset($box);

            // บันทึกคำถามลง DB
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

            // Entry Point ของแผนภูมิ
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'entry_box_id' => $boxes['B1']['id'],
            ]);

            // 3. ตัวเลือกตอบ (Choices) และเส้นทางเชื่อมต่อ
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

            // โครงสร้าง Decision Tree
            $binary = [
                'B1' => [['stomach_bowel_obstruction_54', null], [null, 'B2']],
                'B2' => [['colon_cancer_237_13', null], [null, 'B3']],
                'B3' => [
                    [null, null], // เชื่อมไปยังแผนภูมิที่ 50 กรอบที่ 5.1
                    [null, 'B4']
                ],
                'B4' => [['drug_diet_induced_constipation', null], [null, 'B5']],
                'B5' => [['child_potty_training_reaction', null], [null, 'B6']],
                'B6' => [['ibs_anxiety_depression_33_88_88_2', null], [null, 'B7']],
                'B7' => [['general_constipation_care', null], ['general_constipation_care', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                if ($boxKey === 'B3' && $yes[1] === null) {
                    // กรณีตอบ ใช่ ให้ไปที่แผนภูมิที่ 50 (ถ่ายเป็นเลือด)
                    $choiceId = str_pad((string) $choiceNumber++, 10, '0', STR_PAD_LEFT);
                    $rectalBleedingDiagramId = DB::table('diagrams')->where('diagram_id', '00050')->value('diagram_id');
                    DB::table('answer_choices')->insert([
                        'choice_id' => $choiceId,
                        'choice_text' => 'ใช่',
                        'order' => 1,
                        'status' => '1',
                        'box_id' => $boxes['B3']['id'],
                        'next_box_id' => null,
                        'next_diagram_id' => $rectalBleedingDiagramId,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]);
                } else {
                    $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                }
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กฎการวินิจฉัย การรักษา และคำแนะนำ
            $rules = [
                'stomach_bowel_obstruction_54' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นกระเพาะหรือลำไส้อุดตัน (54)
NOTE,
                    'refs'       => ['54'],
                    'diagrams'   => [],
                ],
                'colon_cancer_237_13' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งลำไส้ใหญ่ (237.13)/อื่น ๆ
NOTE,
                    'refs'       => ['237.13'],
                    'diagrams'   => [],
                ],
                'drug_diet_induced_constipation' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สิ่งเหล่านี้ทำให้ท้องผูกได้ ถ้าหยุดกินหรือปรับเปลี่ยนยาให้เหมาะสม
* ยาที่ทำให้มีอาการท้องผูก เช่น ยาแก้ท้องเดินที่มีอนุพันธ์ฝิ่น, ยาต้านกรดที่มีอลูมิเนียมไฮดรอกไซด์ แคลเซียมคาร์บอเนต (ยา24.2.1), ฟีโนไทอาซีน, ยาแก้ซึมเศร้า (tricyclic antidepressant), ยาลดความดัน-เวราพาลิล (verapamil), ยาลดไขมัน-คอเลสไทรามีน (cholestyramine)
NOTE,
                    'refs'       => ['24.2.1'],
                    'diagrams'   => [],
                ],
                'child_potty_training_reaction' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เด็กมีปฏิกิริยาต่อการถูกบังคับ
• ให้คำแนะนำ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'ibs_anxiety_depression_33_88_88_2' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคลำไส้แปรปรวน (33)/โรควิตกกังวล/โรควิตกกังวลทั่วไป (88)/โรคอารมณ์แปรปรวน/โรคซึมเศร้า (88.2)
• งดอาหารที่เป็นสาเหตุกระตุ้น
• กินอาหารที่มีกากใบมาก
• ออกกำลัง/ฝึกโยคะ/รำมวยจีน
• ถ้าเครียด/กังวล/ซึมเศร้า ให้ยาทางจิตประสาท (ยา17)
⊕ ถ้ามีอาการรุนแรงหรือน้ำหนักลด/ถ่ายมีเลือดสด/ถ่ายดำ/ซีด/มีไข้/มีประวัติของมะเร็งลำไส้ใหญ่ในครอบครัว/เริ่มเป็นเมื่ออายุมากกว่า 50 ปี
NOTE,
                    'refs'       => ['33', '88', '88.2'],
                    'diagrams'   => [],
                ],
                'general_constipation_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
การดูแลรักษาอาการท้องผูกทั่วไป:
1. อย่ารีบร้อนในการนั่งส้วม และฝึกถ่ายให้เป็นนิสัย
2. ออกกำลังกายให้มากขึ้น
3. ดื่มน้ำมากๆ วันละ 10-15 แก้ว และกินผัก ผลไม้ให้มากขึ้น
4. งดชา กาแฟ
5. ทารกให้ดื่มน้ำผึ้ง น้ำมะขาม หรือน้ำลูกพรุน
6. ถ้าไม่ได้ผล ให้ยาระบาย (ยา16) สำหรับทารกให้สวนด้วยแท่งกลีเซอรีน (อย่าให้ยาระบาย หรือยาสวนเป็นประจำ อาจทำให้ท้องผูกเป็นนิสัย)
⊕ ถ้าไม่หายท้องผูกใน 2 สัปดาห์/มีอาการปวดท้องมาก/น้ำหนักลด
NOTE,
                    'refs'       => ['16'],
                    'diagrams'   => [],
                ],
            ];

            // 5. บันทึก Diagnosis Rules และ Relations
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
                    'medical_reference' => 'แผนภูมิที่ 51',
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

                // Rule Conditions
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

                // Rule Diseases (เชื่อมรหัสโรค)
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

                // Rule Next Diagrams (เชื่อมแผนภูมิถัดไป)
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

        $this->command->info('สร้างแผนภูมิที่ 51 (ท้องผูก) กรอบ 1-7 สำเร็จ');
    }
}
