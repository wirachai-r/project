<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram49DysenterySeeder extends Seeder
{
    private const DIAGRAM_ID = '00049';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            // รายการกรอบทั้งหมดในแผนภูมิที่ 49
            $frameNumbers = ['1', '1.1', '2', '3', '4', '5', '6'];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 49 ถูก seed แล้ว กรุณารัน Rollback หรือลบข้อมูลเดิมก่อน'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 49
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'บิด (DYSENTERY)',
                'diagram_name_en' => 'Dysentery',
                'description' => 'มีอาการถ่ายเป็นมูก หรือมูกปนเลือดกะปริบกะปรอยบ่อยครั้ง สาเหตุที่พบบ่อย บิดชิเกลลา (36.1) บิดอะมีบา (36.2) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 6',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 49
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => 'ปวดท้องรุนแรง? กดเจ็บท้องมาก? หน้าท้องเกร็งแข็ง? อาเจียนเป็นพักๆ หรือ มีภาวะขาดน้ำรุนแรง?'],
                'B1_1' => ['frame' => '1.1', 'type' => 'S', 'q' => 'พบในเด็กเล็ก?'],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => 'น้ำหนักลดฮวบ? ซีด? ดีซ่าน? ถ่ายนานกว่า 2 สัปดาห์? เป็นๆ หายๆ เรื้อรัง? หรือ คลำได้ก้อนในท้อง?'],
                'B3'   => ['frame' => '3',   'type' => 'S', 'q' => 'ทวารหนักโผล่ในเด็ก?'],
                'B4'   => ['frame' => '4',   'type' => 'S', 'q' => 'อุจจาระเหม็นเหมือนหัวกุ้งเน่า? กินยารักษาบิดชิเกลลาแล้วไม่ดีขึ้น? หรือ ตับโตกดเจ็บ?'],
                'B5'   => ['frame' => '5',   'type' => 'S', 'q' => 'มีไข้? และ ถ่ายเป็นน้ำมาก่อน?'],
                'B6'   => ['frame' => '6',   'type' => 'S', 'q' => 'การดูแลรักษาบิดชิเกลลา / กรณีอาการไม่ชัดเจน'],
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
                'B1'   => [[null, 'B1_1'], [null, 'B2']],
                'B1_1' => [['intussusception_54', null], ['severe_shigella_amoebiasis_complication', null]],
                'B2'   => [['severe_cause_colon_cancer_chronic_amoebiasis_237_13_36_2', null], [null, 'B3']],
                'B3'   => [['whipworm_shigella_234_36_1', null], [null, 'B4']],
                'B4'   => [['amoebiasis_36_2', null], [null, 'B5']],
                'B5'   => [['shigella_36_1', null], ['shigella_36_1', null]],
                'B6'   => [['shigella_36_1', null], ['shigella_36_1', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กฎการวินิจฉัย การรักษา และคำแนะนำ
            $rules = [
                'intussusception_54' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็น ลำไส้กลืนกันเอง (54)
NOTE,
                    'refs'       => ['54'],
                    'diagrams'   => [],
                ],
                'severe_shigella_amoebiasis_complication' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นบิดชิเกลลา (36.1)/บิดอะมีบา (36.2) ที่มีภาวะแทรกซ้อน/สาเหตุร้ายแรงอื่น ๆ
NOTE,
                    'refs'       => ['36.1', '36.2'],
                    'diagrams'   => [],
                ],
                'severe_cause_colon_cancer_chronic_amoebiasis_237_13_36_2' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน อาจมีสาเหตุร้ายแรง เช่น มะเร็งลำไส้ใหญ่ (237.13)/บิดอะมีบาเรื้อรัง (36.2)
NOTE,
                    'refs'       => ['237.13', '36.2'],
                    'diagrams'   => [],
                ],
                'whipworm_shigella_234_36_1' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคพยาธิแส้ม้า (234)/บิดชิเกลลา (36.1)
• ให้การรักษาตามสาเหตุที่ตรวจพบ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['234', '36.1'],
                    'diagrams'   => [],
                ],
                'amoebiasis_36_2' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
บิดอะมีบา (36.2)
• ชันสูตรเพิ่มเติม
• เมโทรนิดาโซล (ยา4.8)
⊕ ถ้าไม่ดีขึ้นใน 5 วัน
NOTE,
                    'refs'       => ['36.2'],
                    'diagrams'   => [],
                ],
                'shigella_36_1' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
บิดชิเกลลา (36.1)
• โคไตรม็อกซาโซล (ยา4.7) หรือนอร์ฟล็อกซาซิน (ยา4.11)
• ยาลดไข้ (ยา1) ถ้ามีไข้
• น้ำเกลือ ถ้ามีภาวะขาดน้ำ
⊕ ถ้าไม่ดีขึ้นใน 5 วัน หรือปวดท้องรุนแรง
NOTE,
                    'refs'       => ['36.1'],
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
                    'medical_reference' => 'แผนภูมิที่ 49',
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

        $this->command->info('สร้างแผนภูมิที่ 49 (บิด) กรอบ 1-6 สำเร็จ');
    }
}
