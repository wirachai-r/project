<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram44AbdominalPainWithFeverSeeder extends Seeder
{
    private const DIAGRAM_ID = '00044';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.2', '1.2.1',
                '2', '2.1', '2.1.1', '2.2', '2.3',
                '3', '4', '5', '6', '7'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 44 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 44
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ปวดท้องร่วมกับมีไข้',
                'diagram_name_en' => 'Abdominal Pain with Fever',
                'description' => 'มีอาการปวดเจ็บในท้องร่วมกับตัวร้อน อุณหภูมิร่างกายสูงกว่า 37.2°ซ. โดยการวัดทางปาก สาเหตุที่พบบ่อย ท้องเดิน (32) ไทฟอยด์ (37) ไข้เลือดออก (225) ไส้ติ่งอักเสบ (46) กรวยไตอักเสบ (137) ปีกมดลูกอักเสบ (147) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 7 ถ้ามีอาการปวดท้องน้อยที่พบในผู้หญิงวัยเจริญพันธุ์ ดูแผนภูมิที่ 46',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 44
            $boxes = [
                'B1'     => ['frame' => '1',     'type' => 'S', 'q' => "มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้\n☐ ปวดรุนแรง?\n☐ หน้าท้องเกร็งแข็ง?\n☐ ปวดติดต่อกันนานเกิน 6 ชั่วโมง?\n☐ กระเทือนหรือถูกกดเจ็บ?"],
                'B1_1'   => ['frame' => '1.1',   'type' => 'S', 'q' => 'กดเจ็บตรงท้องน้อยข้างขวา?'],
                'B1_2'   => ['frame' => '1.2',   'type' => 'S', 'q' => 'กดเจ็บตรงใต้ลิ้นปี่ หรือ ทั่วทั้งท้อง?'],
                'B1_2_1' => ['frame' => '1.2.1', 'type' => 'S', 'q' => "มีประวัติเป็นโรคกระเพาะ? หรือ กินยาแก้ปวดหรือยาชุดมานาน?"],
                'B2'     => ['frame' => '2',     'type' => 'S', 'q' => 'หนาวสั่นมาก?'],
                'B2_1'   => ['frame' => '2.1',   'type' => 'S', 'q' => 'กดเจ็บตรงชายโครงข้างขวา?'],
                'B2_1_1' => ['frame' => '2.1.1', 'type' => 'S', 'q' => "กดเจ็บเพียงจุดเล็ก ๆ? หรือ มีประวัติถ่ายเป็นมูกเลือด?"],
                'B2_2'   => ['frame' => '2.2',   'type' => 'S', 'q' => "เคาะเจ็บที่สีข้าง? และ ปัสสาวะขุ่น?"],
                'B2_3'   => ['frame' => '2.3',   'type' => 'S', 'q' => "ปวดตรงท้องน้อยในผู้หญิง และ ตกขาวมีกลิ่นเหม็นหรือขัดเบา?"],
                'B3'     => ['frame' => '3',     'type' => 'S', 'q' => 'ท้องเดิน? หรือ ดีซ่าน?'],
                'B4'     => ['frame' => '4',     'type' => 'S', 'q' => 'มีไข้เกิน 7 วัน?'],
                'B5'     => ['frame' => '5',     'type' => 'S', 'q' => "ไข้สูงตลอดเวลา? และ ทดสอบทูร์นิเกต์ให้ผลบวก?"],
                'B6'     => ['frame' => '6',     'type' => 'S', 'q' => "ในเด็กที่เป็นไข้หวัด เจ็บคอ หรือตัวร้อน?"],
                'B7'     => ['frame' => '7',     'type' => 'T', 'q' => "• ยาแก้ปวดลดไข้ (ย1)\n• ยาแก้ท้องอืดท้องเฟ้อ (ย13)/ยาลดกรด (ย14.1) ถ้าจุกแน่นท้อง\n⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือปวดท้องติดต่อกันเกิน 6 ชั่วโมง/กดเจ็บ/อาเจียน/ซีด/ดีซ่าน"],
            ];

            // สร้าง box_id ถัดไปอัตโนมัติ
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

            // อัปเดตจุดเริ่มต้นของแผนภูมิ (Entry Box)
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'entry_box_id' => $boxes['B1']['id'],
            ]);

            // 3. กำหนดตัวเลือก (Choices) และเส้นทางเดิน (Flow)
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

            // แผนที่การตัดสินใจ (Decision Tree Flow)
            $binary = [
                'B1'     => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'   => [['appendicitis_acute', null], [null, 'B1_2']],
                'B1_2'   => [[null, 'B1_2_1'], [null, 'B2_1']],
                'B1_2_1' => [['stomach_perforation', null], ['peritonitis_pancreatitis', null]],
                'B2'     => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'   => [[null, 'B2_1_1'], [null, 'B2_2']],
                'B2_1_1' => [['amebic_liver_abscess', null], ['cholecystitis_cholangitis', null]],
                'B2_2'   => [['acute_pyelonephritis', null], [null, 'B2_3']],
                'B2_3'   => [['salpingitis_endometritis', null], ['other_severe_causes', null]],
                'B3'     => [['refer_diagram_47_11', null], [null, 'B4']],
                'B4'     => [['malaria_typhoid_other', null], [null, 'B5']],
                'B5'     => [['dengue_hemorrhagic_fever', null], [null, 'B6']],
                'B6'     => [['pediatric_cold_abdominal_pain', null], [null, 'B7']],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // กรอบที่ 7 (Terminal Text Box)
            $addChoice('B7', 'การดูแลรักษาตามกรอบที่ 7', null, 'unspecified_fever_abdominal_pain_care', 1);

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'appendicitis_acute' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ไส้ติ่งอักเสบ (46)
• งดน้ำและอาหาร
• ให้น้ำเกลือ
⊕ ด่วน
NOTE,
                    'refs'       => ['46'],
                    'diagrams'   => [],
                ],
                'stomach_perforation' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
กระเพาะอาหารทะลุ (52)
• งดน้ำและอาหาร
• ให้น้ำเกลือ
⊕ ด่วน
NOTE,
                    'refs'       => ['52'],
                    'diagrams'   => [],
                ],
                'peritonitis_pancreatitis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
เยื่อบุช่องท้องอักเสบ (47)/ตับอ่อนอักเสบ (48)
• งดน้ำและอาหาร
• ให้น้ำเกลือ
⊕ ด่วน
NOTE,
                    'refs'       => ['47', '48'],
                    'diagrams'   => [],
                ],
                'amebic_liver_abscess' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ฝีตับอะมีบา (39)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['39'],
                    'diagrams'   => [],
                ],
                'cholecystitis_cholangitis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ถุงน้ำดีอักเสบ (40)/ท่อน้ำดีอักเสบ (41)
• งดน้ำและอาหาร
• ให้น้ำเกลือ
⊕ ด่วน
NOTE,
                    'refs'       => ['40', '41'],
                    'diagrams'   => [],
                ],
                'acute_pyelonephritis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กรวยไตอักเสบเฉียบพลัน (137)
• ชันสูตรเพิ่มเติม
• ยาลดไข้ (ย1)
• อะม็อกซีซิลลิน (ย4.2) หรือ โคไตรม็อกซาโซล (ย4.7) หรือไซโพรฟล็อกซาซิน (ย4.11.2)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือซึม/ดีซ่าน/มีจุดแดงจ้ำเขียว
NOTE,
                    'refs'       => ['137'],
                    'diagrams'   => [],
                ],
                'salpingitis_endometritis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ปีกมดลูกอักเสบ/เยื่อบุมดลูกอักเสบ (147)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['147'],
                    'diagrams'   => [],
                ],
                'other_severe_causes' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุที่ร้ายแรงอื่น ๆ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'refer_diagram_47_11' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 47 ท้องเดิน กรอบที่ 4.1
ดูแผนภูมิที่ 11 ดีซ่าน กรอบที่ 3.1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00047', '00011'],
                ],
                'malaria_typhoid_other' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจเป็นมาลาเรีย (224)/ไทฟอยด์ (37)/อื่น ๆ
NOTE,
                    'refs'       => ['224', '37'],
                    'diagrams'   => [],
                ],
                'dengue_hemorrhagic_fever' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไข้เลือดออก (225)
• เช็ดตัว ดื่มน้ำมากๆ
• พาราเซตามอล (ย1.2)
• นัดดูอาการทุกวัน
⊕ ด่วน ถ้ามีเลือดออก/ช็อก/อาเจียนมาก
NOTE,
                    'refs'       => ['225'],
                    'diagrams'   => [],
                ],
                'pediatric_cold_abdominal_pain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เด็กมักมีอาการปวดท้องเล็กน้อยร่วมกับโรคเหล่านี้
ให้ยาแก้ปวด (ย1) และรักษาโรคที่เป็นสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'unspecified_fever_abdominal_pain_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ปวดลดไข้ (ย1)
• ยาแก้ท้องอืดท้องเฟ้อ (ย13)/ยาลดกรด (ย14.1) ถ้าจุกแน่นท้อง
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือปวดท้องติดต่อกันเกิน 6 ชั่วโมง/กดเจ็บ/อาเจียน/ซีด/ดีซ่าน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
            ];

            // 5. บันทึก Rules, Conditions, Disease references, Next Diagram references ลง DB
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
                    'medical_reference' => 'แผนภูมิที่ 44',
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

                // สร้างเงื่อนไข (Rule Conditions)
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

                // ผูกรหัสโรค (Rule Diseases)
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

                // ผูกแผนภูมิถัดไป (Rule Next Diagrams)
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

        $this->command->info('สร้างแผนภูมิที่ 44 (ปวดท้องร่วมกับมีไข้) กรอบ 1-7 สำเร็จ');
    }
}
