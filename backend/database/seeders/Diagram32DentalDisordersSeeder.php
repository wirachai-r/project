<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram32DentalDisordersSeeder extends Seeder
{
    private const DIAGRAM_ID = '00032';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            // รายการกรอบทั้งหมดในแผนภูมิที่ 32
            $frameNumbers = [
                '1', '1.1', '1.2', '1.3', '1.4', '1.5',
                '2', '3', '4', '4.1', '4.2'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 32 ถูก seed แล้ว กรุณารัน Rollback หรือลบข้อมูลเดิมก่อน'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 32
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'โรคฟัน (DENTAL DISORDERS)',
                'diagram_name_en' => 'Dental Disorders',
                'description' => 'มีความผิดปกติเกี่ยวกับเหงือกหรือฟัน เช่น ปวดฟัน เลือดออกจากไรฟัน หรือฟันเหลือง ดำ เป็นต้น สาเหตุที่พบบ่อย 1. เลือดออกจากไรฟัน : เหงือกอักเสบ (61) รอยแผลถอนฟัน 2. ปวดฟัน/เหงือกบวม : ฟันผุ/ฟันคุด (60) เหงือกอักเสบ (61) 3. ฟันเหลือง ดำ : จากยาเตตราไซคลีน/คราบบุหรี่ (62) ถ้าอาการไม่ชัดเจน ควรปรึกษาแพทย์',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 32
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => 'เลือดออกจากฟัน?'],
                'B1_1' => ['frame' => '1.1', 'type' => 'S', 'q' => 'ภายหลังถอนฟัน?'],
                'B1_2' => ['frame' => '1.2', 'type' => 'S', 'q' => 'มีจุดแดงจ้ำเขียวตามตัว? มีเลือดออกที่อื่น ๆ? หรือ ตับ/ม้ามโต?'],
                'B1_3' => ['frame' => '1.3', 'type' => 'S', 'q' => 'หลังถูกงูกัด?'],
                'B1_4' => ['frame' => '1.4', 'type' => 'S', 'q' => 'เหงือกบวม เป็นหนอง?'],
                'B1_5' => ['frame' => '1.5', 'type' => 'S', 'q' => 'เป็นอยู่ประจำ?'],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => 'เหงือกบวม เป็นหนอง?'],
                'B3'   => ['frame' => '3',   'type' => 'S', 'q' => 'ปวดฟัน? ฟันผุ? หรือ ฟันคุด?'],
                'B4'   => ['frame' => '4',   'type' => 'S', 'q' => 'ฟันเหลืองดำ?'],
                'B4_1' => ['frame' => '4.1', 'type' => 'S', 'q' => 'เป็นหลังสูบบุหรี่?'],
                'B4_2' => ['frame' => '4.2', 'type' => 'S', 'q' => 'ประวัติกินยาเตตราไซคลีน/ดอกซีไซคลีน เมื่อมีอายุต่ำกว่า 8 ปี หรือขณะมารดาตั้งครรภ์?'],
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

            // โครงสร้าง Decision Tree แบบ Binary
            $binary = [
                'B1'   => [[null, 'B1_1'], [null, 'B2']],
                'B1_1' => [['post_tooth_extraction_bleeding', null], [null, 'B1_2']],
                'B1_2' => [['blood_disorders_dengue_septicemia', null], [null, 'B1_3']],
                'B1_3' => [['snake_bite_221', null], [null, 'B1_4']],
                'B1_4' => [['gingivitis_1_4_61', null], [null, 'B1_5']],
                'B1_5' => [['scurvy_133', null], ['bleeding_gums_refer_24h', null]],
                'B2'   => [['gingivitis_2_61', null], [null, 'B3']],
                'B3'   => [['tooth_decay_impacted_60', null], [null, 'B4']],
                'B4'   => [[null, 'B4_1'], ['other_abnormalities_check', null]],
                'B4_1' => [['tobacco_stain_62', null], [null, 'B4_2']],
                'B4_2' => [['tetracycline_stain_62', null], ['other_causes_fluorosis_stain', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กฎการวินิจฉัย การรักษา และคำแนะนำ
            $rules = [
                'post_tooth_extraction_bleeding' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รอยแผลถอนฟัน
• ใช้ผ้าก็อซพับเป็นก้อนกลมวางลงบนร่องฟันที่ถอน กัดให้แน่น
⊕ ถ้าไม่หยุดใน 1 ชั่วโมง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'blood_disorders_dengue_septicemia' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจเป็นโรคเลือด (103, 104, 106)/ไข้เลือดออก (225)/โลหิตเป็นพิษ (228)
NOTE,
                    'refs'       => ['103', '104', '106', '225', '228'],
                    'diagrams'   => [],
                ],
                'snake_bite_221' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
งูแมวเซา/งูกะปะ/งูเขียวหางไหม้กัด (221)
• ฉีดเซรุ่มแก้พิษงู
⊕ ด่วน
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'gingivitis_1_4_61' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เหงือกอักเสบ (61)
• เพนิซิลลินวี (ย4.1) หรือ อิริโทรไมซิน (ย4.4) หรือ ดอกซีไซคลีน (ย4.5.1)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['61'],
                    'diagrams'   => [],
                ],
                'scurvy_133' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ลักปิดลักเปิด (133)
• วิตามินซี
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['133'],
                    'diagrams'   => [],
                ],
                'bleeding_gums_refer_24h' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมงถ้าซีด/มีไข้เกิน 7 วัน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'gingivitis_2_61' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เหงือกอักเสบ (61)
• เพนิซิลลินวี (ย4.1) หรืออิริโทรไมซิน (ย4.4) หรือดอกซีไซคลีน (ย4.5.1)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['61'],
                    'diagrams'   => [],
                ],
                'tooth_decay_impacted_60' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปวดฟัน/ฟันผุ/ฟันคุด (60)
• ยาแก้ปวด (ยา)
• แนะนำไปพบทันตแพทย์ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['60'],
                    'diagrams'   => [],
                ],
                'other_abnormalities_check' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ถ้ามีความผิดปกติอื่นๆ ตรวจดูอาการเพิ่มเติม
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'tobacco_stain_62' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
คราบบุหรี่ (62)
• แนะนำไปพบทันตแพทย์เพื่อขูดออก
NOTE,
                    'refs'       => ['62'],
                    'diagrams'   => [],
                ],
                'tetracycline_stain_62' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ฟันเหลืองจากเตตราไซคลีน/ดอกซีไซคลีน (62)
• ควรหาทางป้องกันโดยหลีกเลี่ยงการใช้ยานี้ในหญิงตั้งครรภ์และเด็กอยู่อายุต่ำกว่า 8 ปี
NOTE,
                    'refs'       => ['62'],
                    'diagrams'   => [],
                ],
                'other_causes_fluorosis_stain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุอื่นๆ เช่น การใช้ฟลูออไรด์มากเกินไป คราบของชา/กาแฟ หินปูนเกาะ เป็นต้น
• ควรปรึกษาทันตแพทย์
NOTE,
                    'refs'       => [],
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
                    'medical_reference' => 'แผนภูมิที่ 32',
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

        $this->command->info('สร้างแผนภูมิที่ 32 (โรคฟัน) กรอบ 1-4.2 สำเร็จ');
    }
}
