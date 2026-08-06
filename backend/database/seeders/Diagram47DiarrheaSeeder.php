<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram47DiarrheaSeeder extends Seeder
{
    private const DIAGRAM_ID = '00047';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '4.1', '4.2', '4.3', '4.4',
                '5', '6', '7', '8', '8.1', '8.2', '9', '10'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 47 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 47
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ท้องเดิน (DIARRHEA)',
                'diagram_name_en' => 'Diarrhea',
                'description' => 'มีอาการถ่ายเป็นน้ำ หรือถ่ายเหลวบ่อยครั้ง สาเหตุที่พบบ่อย อาหารเป็นพิษ (34) บิดชิเกลลา (36.1) โรคติดเชื้อไวรัส ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 10 ถ้ารร่ายเป็นเลือด/ถ่ายดำ ดูแผนภูมิที่ 50',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 47
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'เป็นนานเกิน 3 สัปดาห์ หรือเป็น ๆ หาย ๆ บ่อย?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => "มีภาวะขาดน้ำรุนแรง(1)? หรือ ช็อก(2)?"],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => "หลังกินปลาปักเป้า/แมงดาทะเล/คางคก/เห็ดป่า/เนื้อหมูดิบ/อาหารที่บรรจุภาชนะที่ปิดมิดชิด/ยากำจัดแมลง? มีอาการหนังตาตก? เห็นภาพซ้อน? เจ็บปวดตามกล้ามเนื้อมาก? กล้ามเนื้ออ่อนแรง? น้ำลายฟูมปาก? พูดจาอ้อแอ้? หรือ ชาปากและลิ้น?"],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'มีไข้?'],
                'B4_1'    => ['frame' => '4.1',   'type' => 'S', 'q' => 'มีไข้เกิน 7 วัน?'],
                'B4_2'    => ['frame' => '4.2',   'type' => 'S', 'q' => 'กดเจ็บตรงท้องน้อยข้างขวา?'],
                'B4_3'    => ['frame' => '4.3',   'type' => 'S', 'q' => "เจ็บปวดตามกล้ามเนื้อมาก หลังกินแหนมหรือลาบหมู?"],
                'B4_4'    => ['frame' => '4.4',   'type' => 'S', 'q' => "ปวดบิดท้องเป็นพัก ๆ? หรือ อาเจียน?"],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'ถ่ายเป็นมูก หรือมูกปนเลือด?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => "ถ่ายเป็นน้ำหรือถ่ายเหลว มีกลิ่นเหม็นจัด?"],
                'B7'      => ['frame' => '7',     'type' => 'S', 'q' => "มีลมพิษ ผื่นคัน หรือผิวหนังบวมคันร่วมด้วย? หรือ มีอาการกำเริบเมื่อกินอาหารที่แพ้ (เช่น นมวัว ไข่ กุ้ง หอย ปู ปลา ถั่วลิสง)?"],
                'B8'      => ['frame' => '8',     'type' => 'S', 'q' => "ปวดบิดท้องเป็นพักๆ? อาเจียน? ถ่ายรุนแรง? หรือ เป็นพร้อมกันหลายคน?"],
                'B8_1'    => ['frame' => '8.1',   'type' => 'S', 'q' => "มีประวัติสัมผัสผู้ป่วยอหิวาต์? หรือ อยู่ในถิ่นที่มีการระบาดของโรคนี้?"],
                'B8_2'    => ['frame' => '8.2',   'type' => 'S', 'q' => "ปากและลิ้นชา หรือรู้สึกเสียวแปลกๆ เกิดอาการหลังกินปลาทะเล/หอยทะเล?"],
                'B9'      => ['frame' => '9',     'type' => 'S', 'q' => "เป็นหลังกินยาถ่าย ยาต้านกรด ยาปฏิชีวนะ หรือยาอื่น ๆ?"],
                'B10'     => ['frame' => '10',    'type' => 'T', 'q' => "• กินอาหารย่อยง่าย รสไม่จัด ไม่มันจัด\n• ทารกให้ดื่มนมมารดาตามปกติ ถ้าดื่มนมผง ในระยะ 2-4 ชั่วโมงแรกให้ผสมนมเจือจางลงเท่าตัว\n• ให้กินสารละลายน้ำตาลเกลือแร่\n• ถ้าอาเจียนมากให้น้ำเกลือทางหลอดเลือดดำ\n• ถ้ามีไข้ให้พาราเซตามอล (ย1.2)\n• ถ้าต่อมามีอาการถ่ายเป็นมูก/มูกปนเลือด ให้การรักษาแบบบิดชิเกลลา (36.1)\n⊕ ถ้าไม่ดีขึ้นใน 48 ชั่วโมง หรือมีภาวะขาดน้ำรุนแรง/มีไข้เกิน 7 วัน/กล้ามเนื้อแขนขาอ่อนแรง/ปวดกล้ามเนื้อมาก/น้ำหนักลดฮวบ/มีประวัติสัมผัสผู้ป่วยอหิวาต์"],
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
                'B1'      => [[null, 'B2'], ['refer_diagram_48_frame_1', null]],
                'B2'      => [['severe_dehydration_shock', null], [null, 'B3']],
                'B3'      => [['botulism_pesticide_poisoning_etc', null], [null, 'B4']],
                'B4'      => [[null, 'B4_1'], [null, 'B5']],
                'B4_1'    => [['fever_over_7_days', null], [null, 'B4_2']],
                'B4_2'    => [['appendicitis', null], [null, 'B4_3']],
                'B4_3'    => [['trichinosis', null], [null, 'B4_4']],
                'B4_4'    => [['food_poisoning_shigellosis_early_rotavirus', null], [null, 'B5']],
                'B5'      => [[null, 'B6'], ['refer_diagram_49_frame_1', null]],
                'B6'      => [['giardiasis', null], [null, 'B7']],
                'B7'      => [['food_allergy', null], [null, 'B8']],
                'B8'      => [[null, 'B8_1'], [null, 'B9']],
                'B8_1'    => [['cholera', null], [null, 'B8_2']],
                'B8_2'    => [['ciguatera_shellfish_poisoning', null], ['infectious_food_poisoning', null]],
                'B9'      => [['drug_induced_diarrhea', null], [null, 'B10']],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // กรอบที่ 10 (Terminal Text Box)
            $addChoice('B10', 'การดูแลรักษาตามกรอบที่ 10', null, 'unspecified_diarrhea_care', 1);

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'refer_diagram_48_frame_1' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 48 ท้องเดินเรื้อรัง กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00048'],
                ],
                'severe_dehydration_shock' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ท้องเดินชนิดรุนแรง (32) อาจเกิดจากอาหารเป็นพิษ (34)/อหิวาต์ (35)/บิดชิเกลลา (36.1) เป็นต้น
⊕ ด่วน พร้อมให้น้ำเกลือ

(1) มีอาการตาโบ๋ หนังเหี่ยว (หยิบตั้งอยู่นาน) ปากแห้ง กระหายน้ำ ปัสสาวะออกน้อย ในทารกมีอาการกระหม่อมบุ๋มร่วมด้วย
(2) มีอาการกระสับกระส่าย เหงื่อออก ตัวซีด ตัวเย็น ลุกนัั่งหน้ามืด ความดันต่ำ ชีพจรเบาและเร็ว
NOTE,
                    'refs'       => ['32', '34', '35', '36.1'],
                    'diagrams'   => [],
                ],
                'botulism_pesticide_poisoning_etc' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นโบทูลิซึม (57.1)/พิษฆ่าแมลง (219)/พิษสัตว์ (219.1-219.4)/พิษเห็ด (219.5)/โรคพยาธิทริคิโนซิส (235)
NOTE,
                    'refs'       => ['57.1', '219', '219.1', '219.2', '219.3', '219.4', '219.5', '235'],
                    'diagrams'   => [],
                ],
                'fever_over_7_days' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง เพื่อตรวจหาสาเหตุ ดูแผนภูมิที่ 48 กรอบที่ 2.1 เพิ่มเติม
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00048'],
                ],
                'appendicitis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ไส้ติ่งอักเสบ (46)
⊕ ด่วน
NOTE,
                    'refs'       => ['46'],
                    'diagrams'   => [],
                ],
                'trichinosis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นโรคพยาธิทริคิโนซิส (235)
NOTE,
                    'refs'       => ['235'],
                    'diagrams'   => [],
                ],
                'food_poisoning_shigellosis_early_rotavirus' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อาหารเป็นพิษจากเชื้อโรค (34.1)/บิดชิเกลลา (36.1) ระยะแรก/ท้องเดินจากเชื้อไวรัสโรตา (32.1) เป็นต้น
• ให้การรักษาตามกรอบที่ 10
NOTE,
                    'refs'       => ['34.1', '36.1', '32.1'],
                    'diagrams'   => [],
                ],
                'refer_diagram_49_frame_1' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 49 บิด กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00049'],
                ],
                'giardiasis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ท้องเดินจากเชื้อเกอาร์เดีย (32.2)
• ชันสูตรเพิ่มเติม
• เมโทรนิดาโซล (ย4.8)
⊕ ถ้าไม่ดีขึ้นใน 5 วัน
NOTE,
                    'refs'       => ['32.2'],
                    'diagrams'   => [],
                ],
                'food_allergy' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
แพ้อาหาร (ดู "โรคที่ 32 และ 198")
• งดอาหารที่แพ้
• ยาแก้แพ้ (ย7)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['32', '198'],
                    'diagrams'   => [],
                ],
                'cholera' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อหิวาต์ (35)
• ตรวจอุจจาระ
• เตตราไซคลีน (ย4.5)
• ให้การรักษาตามกรอบที่ 10
NOTE,
                    'refs'       => ['35'],
                    'diagrams'   => [],
                ],
                'ciguatera_shellfish_poisoning' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
พิษปลาทะเล (219.2)/พิษหอยทะเล (219.3)
⊕ ด่วน
NOTE,
                    'refs'       => ['219.2', '219.3'],
                    'diagrams'   => [],
                ],
                'infectious_food_poisoning' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อาหารเป็นพิษจากเชื้อโรค (34.1)
• ให้การรักษาตามกรอบที่ 10
NOTE,
                    'refs'       => ['34.1'],
                    'diagrams'   => [],
                ],
                'drug_induced_diarrhea' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากยา*
• ให้การรักษาตามอาการดังกรอบที่ 10
• พิจารณาหยุดยา หรือเปลี่ยนยาตามความจำเป็น

* ยาที่ทำให้มีอาการท้องเดิน เช่น ยาถ่าย (ย16) ยาต้านกรดที่มีแมกนีเซียมไฮดรอกไซด์ อะม็อกซีซิลลิน (ย4.2) อิริโทรไมซิน (ย4.4) เตตราไซคลีน (ย4.5) มีเบนดาโซล (ย6.4) คอลชิซีน (colchicine) ดิจิทาลิส (digitalis) เมทิลโดพา (methyldopa) เป็นต้น
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'unspecified_diarrhea_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• กินอาหารย่อยง่าย รสไม่จัด ไม่มันจัด
• ทารกให้ดื่มนมมารดาตามปกติ ถ้าดื่มนมผง ในระยะ 2-4 ชั่วโมงแรกให้ผสมนมเจือจางลงเท่าตัว
• ให้กินสารละลายน้ำตาลเกลือแร่
• ถ้าอาเจียนมากให้น้ำเกลือทางหลอดเลือดดำ
• ถ้ามีไข้ให้พาราเซตามอล (ย1.2)
• ถ้าต่อมามีอาการถ่ายเป็นมูก/มูกปนเลือด ให้การรักษาแบบบิดชิเกลลา (36.1)
⊕ ถ้าไม่ดีขึ้นใน 48 ชั่วโมง หรือมีภาวะขาดน้ำรุนแรง/มีไข้เกิน 7 วัน/กล้ามเนื้อแขนขาอ่อนแรง/ปวดกล้ามเนื้อมาก/น้ำหนักลดฮวบ/มีประวัติสัมผัสผู้ป่วยอหิวาต์
NOTE,
                    'refs'       => ['36.1'],
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
                    'medical_reference' => 'แผนภูมิที่ 47',
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

        $this->command->info('สร้างแผนภูมิที่ 47 (ท้องเดิน DIARRHEA) กรอบ 1-10 สำเร็จ');
    }
}
