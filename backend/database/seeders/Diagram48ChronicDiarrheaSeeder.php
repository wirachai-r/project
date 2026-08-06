<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram48ChronicDiarrheaSeeder extends Seeder
{
    private const DIAGRAM_ID = '00048';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            // รายการกรอบทั้งหมดในแผนภูมิที่ 48
            $frameNumbers = [
                '1', '1.1', '1.2', '1.3',
                '2', '2.1', '2.2',
                '3', '4', '4.1', '5', '6', '7',
                '8', '8.1', '8.1.1', '8.2', '9'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 48 ถูก seed แล้ว กรุณารัน Rollback หรือลบข้อมูลเดิมก่อน'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 48
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ท้องเดินเรื้อรัง (CHRONIC DIARRHEA)',
                'diagram_name_en' => 'Chronic Diarrhea',
                'description' => 'มีอาการถ่ายเป็นน้ำหรือถ่ายเหลวบ่อยครั้งนานเกิน 3 สัปดาห์ หรือเป็นๆ หายๆ เรื้อรัง สาเหตุที่พบบ่อย โรคลำไส้แปรปรวน (33) ภาวะพร่องแล็กเทส (33.1) เอดส์ (238) ไทฟอยด์ (37) ถ้าอาการไม่ชัดเจน ให้การดูแลแบบโรคลำไส้แปรปรวน ถ้าถ่ายเป็นมูกหรือปนเลือด ดูแผนภูมิที่ 49',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 48
            $boxes = [
                'B1'     => ['frame' => '1',      'type' => 'S', 'q' => 'น้ำหนักลดฮวบ?'],
                'B1_1'   => ['frame' => '1.1',    'type' => 'M', 'q' => "มีอาการอย่างน้อย 2 อย่างดังต่อไปนี้\n- เหนื่อยง่าย?\n- ขี้ร้อน?\n- มือสั่น?\n- คอพอก?\n- ตาโปน?\n- ชีพจรมากกว่า 120 ครั้ง/นาที?"],
                'B1_2'   => ['frame' => '1.2',    'type' => 'S', 'q' => 'กระหายน้ำบ่อย ปัสสาวะบ่อยและมาก? หรือ ตรวจพบน้ำตาลในปัสสาวะ?'],
                'B1_3'   => ['frame' => '1.3',    'type' => 'S', 'q' => 'ถ่ายเป็นน้ำหรือถ่ายเหลว มีกลิ่นเหม็นจัด?'],
                'B2'     => ['frame' => '2',      'type' => 'S', 'q' => 'มีไข้?'],
                'B2_1'   => ['frame' => '2.1',    'type' => 'S', 'q' => 'เคยเข้าไปในดงมาลาเรียในระยะหลายเดือนที่ผ่านมา?'],
                'B2_2'   => ['frame' => '2.2',    'type' => 'S', 'q' => 'ไข้สูงตลอดเวลา? ม้ามโต? หรือ อยู่ในละแวกที่มีการระบาดของไทฟอยด์?'],
                'B3'     => ['frame' => '3',      'type' => 'S', 'q' => 'กินยาถ่าย ยาลดกรด คอลชิซีน (รักษาโรคเกาต์) หรือมะขามแขกเป็นประจำ?'],
                'B4'     => ['frame' => '4',      'type' => 'S', 'q' => 'ท้องผูกสลับท้องเดิน?'],
                'B4_1'   => ['frame' => '4.1',    'type' => 'S', 'q' => 'เคยเป็นโรคลำไส้แปรปรวน? หรือ เป็นๆ หายๆ มาเป็นแรมปี และสุขภาพแข็งแรงดี?'],
                'B5'     => ['frame' => '5',      'type' => 'S', 'q' => 'ทวารหนักโผล่ในเด็ก?'],
                'B6'     => ['frame' => '6',      'type' => 'S', 'q' => 'มีอาการเฉพาะหลังดื่มนม งดนมแล้วดีขึ้น?'],
                'B7'     => ['frame' => '7',      'type' => 'S', 'q' => 'มีลมพิษ ผื่นคัน หรือผิวหนังบวมคันร่วมด้วย? หรือ มีอาการกำเริบเมื่อกินอาหารที่แพ้ (เช่น นมวัว ไข่ กุ้ง หอย ปู ปลา ถั่วลิสง)?'],
                'B8'     => ['frame' => '8',      'type' => 'S', 'q' => 'สุขภาพทั่วไปแข็งแรงดี?'],
                'B8_1'   => ['frame' => '8.1',    'type' => 'S', 'q' => 'มีอาการมากกว่า 12 สัปดาห์ ในช่วงเวลา 12 เดือนที่ผ่านมา? หรือ มีความเครียด วิตกกังวล ซึมเศร้า หรือนอนไม่หลับ?'],
                'B8_1_1' => ['frame' => '8.1.1',  'type' => 'S', 'q' => 'ข้อแนะนำการดูแลรักษาโรคลำไส้แปรปรวน'],
                'B8_2'   => ['frame' => '8.2',    'type' => 'S', 'q' => 'อาการเกิดขึ้นประมาณ 30 นาที หลังกินอาหาร/เครื่องดื่ม (เช่น อาหารรสเผ็ด/รสจัด เหล้า ชา กาแฟ น้ำผลไม้ เป็นต้น)?'],
                'B9'     => ['frame' => '9',      'type' => 'S', 'q' => 'มีประวัติเป็นเบาหวานมาก่อน?'],
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
                    'min_required' => $box['type'] === 'M' ? 2 : null,
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
                'B1'     => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'   => [['hyperthyroidism_toxic_goiter_121', null], [null, 'B1_2']],
                'B1_2'   => [['diabetes_117', null], [null, 'B1_3']],
                'B1_3'   => [['giardiasis_malabsorption_32_2', null], ['chronic_diarrhea_other_causes_238_237_13_36_2', null]],
                'B2'     => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'   => [['malaria_224', null], [null, 'B2_2']],
                'B2_2'   => [['typhoid_37', null], [null, 'B3']],
                'B3'     => [['drug_induced_diarrhea', null], [null, 'B4']],
                'B4'     => [[null, 'B4_1'], [null, 'B5']],
                'B4_1'   => [['ibs_8_1_1', null], [null, 'B5']],
                'B5'     => [['whipworm_234', null], [null, 'B6']],
                'B6'     => [['lactase_deficiency_33_1', null], [null, 'B7']],
                'B7'     => [['food_allergy_32_198', null], [null, 'B8']],
                'B8'     => [[null, 'B8_1'], [null, 'B9']],
                'B8_1'   => [['ibs_generalized_anxiety_depression_33_88_88_2', null], [null, 'B8_2']],
                'B8_1_1' => [['ibs_care_management', null], ['ibs_care_management', null]],
                'B8_2'   => [['postprandial_stimulation_indigestion', null], [null, 'B9']],
                'B9'     => [['diabetes_autonomic_neuropathy_ibs_117', null], ['chronic_diarrhea_check_3days', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กฎการวินิจฉัย การรักษา และคำแนะนำ
            $rules = [
                'hyperthyroidism_toxic_goiter_121' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ภาวะต่อมไทรอยด์ทำงานเกิน/คอพอกเป็นพิษ (121)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['121'],
                    'diagrams'   => [],
                ],
                'diabetes_117' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เบาหวาน (117)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['117'],
                    'diagrams'   => [],
                ],
                'giardiasis_malabsorption_32_2' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นท้องเดินจากเชื้อการ์เดีย (32.2)/การดูดซึมผิดปกติ
NOTE,
                    'refs'       => ['32.2'],
                    'diagrams'   => [],
                ],
                'chronic_diarrhea_other_causes_238_237_13_36_2' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจมีสาเหตุอื่น ๆ เช่น เอดส์ (238)/มะเร็งลำไส้ใหญ่ (237.13)/วัณโรคลำไส้/บิดอะมีบาเรื้อรัง (36.2)/การดูดซึมผิดปกติ
NOTE,
                    'refs'       => ['238', '237.13', '36.2'],
                    'diagrams'   => [],
                ],
                'malaria_224' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
มาลาเรีย (224)
• ชันสูตรเพิ่มเติม
• ยาลดไข้ (ยา1)
• ยารักษามาลาเรีย (ยา5)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือไม่ค่อยรู้สึกตัว/ชัก/หอบ/ซีด/ดีซ่าน
NOTE,
                    'refs'       => ['224'],
                    'diagrams'   => [],
                ],
                'typhoid_37' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไทฟอยด์ (37)
• ชันสูตรเพิ่มเติม
• ยาลดไข้ (ยา1)
• โคไตรม็อกซาโซล (ยา4.7) หรือคลอแรมเฟนิคอล (ยา4.6)
⊕ ถ้าไม่ดีขึ้นใน 4 วัน หรือหอบ/คอแข็ง/ปวดท้องรุนแรง/ซีด/ดีซ่าน
NOTE,
                    'refs'       => ['37'],
                    'diagrams'   => [],
                ],
                'drug_induced_diarrhea' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากยา
• หยุดยาหรือเปลี่ยนยา
• รักษาภาวะที่ต้องการแก้ไข (เช่น ท้องผูก น้ำหนักเกิน โรคกระเพาะ) ด้วยวิธีที่เหมาะสม
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'ibs_8_1_1' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคลำไส้แปรปรวน (33)
• ให้การรักษาตามกรอบที่ 8.1.1
NOTE,
                    'refs'       => ['33'],
                    'diagrams'   => [],
                ],
                'whipworm_234' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคพยาธิแส้ม้า (234)
• ยาถ่ายพยาธิ-มีเบนดาโซล (ยา6.4)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['234'],
                    'diagrams'   => [],
                ],
                'lactase_deficiency_33_1' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ภาวะพร่องแล็กเทส (33.1)
• งดนม/ลดปริมาณนมที่ดื่ม
• กินนมถั่วเหลือง/น้ำเต้าหู้
NOTE,
                    'refs'       => ['33.1'],
                    'diagrams'   => [],
                ],
                'food_allergy_32_198' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
แพ้อาหาร (ดู "โรคที่ 32 และ 198")
• งดอาหารที่แพ้
• ยาแก้แพ้ (ยา7)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['32', '198'],
                    'diagrams'   => [],
                ],
                'ibs_generalized_anxiety_depression_33_88_88_2' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคลำไส้แปรปรวน (33)/ โรควิตกกังวล/โรควิตกกังวลทั่วไป (88)/ โรคอารมณ์แปรปรวน/ โรคซึมเศร้า (88.2)
NOTE,
                    'refs'       => ['33', '88', '88.2'],
                    'diagrams'   => [],
                ],
                'postprandial_stimulation_indigestion' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เกิดจากการกระตุ้นหรือไม่ย่อยของอาหาร
• หลีกเลี่ยงการกินอาหาร/เครื่องดื่มเหล่านี้ โดยเฉพาะขณะท้องว่างหรือกินปริมาณเล็กน้อยพร้อมอาหารอื่น
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'diabetes_autonomic_neuropathy_ibs_117' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เบาหวาน (117) ที่มีภาวะลำไส้ทำงานแปรปรวนจากประสาทเสื่อม
• ดูแลรักษาโรคเบาหวาน
• ให้ยาบรรเทาตามอาการ
NOTE,
                    'refs'       => ['117'],
                    'diagrams'   => [],
                ],
                'chronic_diarrhea_check_3days' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน อาจเป็น มะเร็งลำไส้ใหญ่ (237.13)/ ท้องเดินจากเชื้อการ์เดีย (32.2)/ บิดอะมีบา (36.2)/ เอดส์ (238)/ การดูดซึมผิดปกติ
NOTE,
                    'refs'       => ['237.13', '32.2', '36.2', '238'],
                    'diagrams'   => [],
                ],
                'ibs_care_management' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ข้อแนะนำการดูแลรักษาโรคลำไส้แปรปรวน:
• งดอาหารที่เป็นสาเหตุกระตุ้น
• ถ้าปวดท้องแบบบิดเกร็ง ให้แอนติสปาสโมดิก (ยา20)
• ถ้าท้องเดินมากให้โลเพอราไมด์ (ยา15.2)
• ถ้าท้องผูกให้อาหารที่มีกากมาก/ยาระบาย (ยา16)
• ออกกำลังกาย/โยคะ/รำมวยจีน
• ถ้าเครียด/กังวล/ซึมเศร้า ให้ยาทางจิตประสาท (ยา17)
⊕ ถ้ามีอาการรุนแรง หรือน้ำหนักลด/ลุกขึ้นถ่ายตอนดึก/ท้องเดินทุกวันนานเกิน 1 สัปดาห์/ถ่ายมีเลือดสดหรือถ่ายดำ/ซีด/มีไข้/มีประวัติของมะเร็งลำไส้ใหญ่ในครอบครัว/เริ่มเป็นเมื่ออายุมากกว่า 50 ปี
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
                    'medical_reference' => 'แผนภูมิที่ 48',
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

        $this->command->info('สร้างแผนภูมิที่ 48 (ท้องเดินเรื้อรัง) กรอบ 1-9 สำเร็จ');
    }
}
