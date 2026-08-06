<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram4FeverRashSeeder extends Seeder
{
    private const DIAGRAM_ID = '00004';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '2', '3', '4', '4.1', '4.2', '4.3', '4.4', '4.5',
                '5', '5.1', '5.1.1', '5.1.2', '5.2', '5.3', '5.4', '5.5', '5.6'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 4 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 4
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ไข้ร่วมกับมีผื่นหรือตุ่มขึ้น',
                'diagram_name_en' => 'Fever with Rash or Skin Lesions',
                'description' => 'มีจุดแดง จ้ำเขียว ผื่นแดง ตุ่มใส หรือตุ่มหนอง ขึ้นพร้อมกับอาการตัวร้อน (อุณหภูมิมากกว่า 37.2°ซ.)',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 4
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'ไม่ค่อยรู้สึกตัว? ปวดศีรษะรุนแรง? หรือ อาเจียนรุนแรง?'],
                'B1_1'    => ['frame' => '1.1',   'type' => 'S', 'q' => 'คอแข็ง?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'หอบหรือหายใจเร็วกว่าปกติ*?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'มีจุดแดง หรือจ้ำเขียวขึ้นตามผิวหนัง?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'ขึ้นเป็นตุ่มนูน?'],
                'B4_1'    => ['frame' => '4.1',   'type' => 'S', 'q' => 'มีตุ่มน้ำ เม็ดพอง ร่วมกับปากเปื่อย ตาแดง ตาแฉะ?'],
                'B4_2'    => ['frame' => '4.2',   'type' => 'S', 'q' => 'ตุ่มฝี ตุ่มหนอง หรือพุพอง?'],
                'B4_3'    => ['frame' => '4.3',   'type' => 'S', 'q' => 'ตุ่มใส?'],
                'B4_4'    => ['frame' => '4.4',   'type' => 'S', 'q' => 'ขึ้นกระจายทั่วตัว?'],
                'B4_5'    => ['frame' => '4.5',   'type' => 'S', 'q' => 'ขึ้นที่ปาก และมือเท้า/ฝ่ามือฝ่าเท้า?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'ขึ้นเป็นผื่นแดงเล็กๆ?'],
                'B5_1'    => ['frame' => '5.1',   'type' => 'S', 'q' => 'ไข้สูงตลอดเวลา?'],
                'B5_1_1'  => ['frame' => '5.1.1', 'type' => 'S', 'q' => 'ทดสอบทูร์นิเกต์ให้ผลบวก? หรือ ตับโต?'],
                'B5_1_2'  => ['frame' => '5.1.2', 'type' => 'S', 'q' => 'ผื่นขึ้นหลังมีไข้ 4 วัน? หรือ พบจุดค็อปลิกในกระพุ้งแก้ม?'],
                'B5_2'    => ['frame' => '5.2',   'type' => 'S', 'q' => 'ต่อมน้ำเหลืองที่หลังหูและท้ายทอยโต? หรือ อยู่ใกล้ชิดกับผู้ป่วยหัดเยอรมัน?'],
                'B5_3'    => ['frame' => '5.3',   'type' => 'S', 'q' => 'พบแผลเหมือนรอยบุหรี่จี้ หรือผื่นขึ้นหลังมีไข้ 5-7 วัน? และ มีประวัติเคยเข้าไปในป่าดงทุ่งหญ้า หรือไร่สวน ภายในระยะ 3 สัปดาห์ที่ผ่านมา'],
                'B5_4'    => ['frame' => '5.4',   'type' => 'S', 'q' => 'ทอนซิลบวมแดงหรือเป็นหนอง? ลิ้นสตรอเบอร์รี่? และ มีผื่นแดงขึ้นทั่วตัวหลังมีไข้ 1-2 วัน?'],
                'B5_5'    => ['frame' => '5.5',   'type' => 'S', 'q' => 'ผื่นขึ้นหลังกินยา?'],
                'B5_6'    => ['frame' => '5.6',   'type' => 'S', 'q' => 'ผื่นขึ้นหลังไข้ลดแล้ว และท่าทางสบาย พบในเด็กต่ำกว่า 3 ปี?'],
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

            // แผนที่การตัดสินใจ (Binary Decision Tree)
            $binary = [
                'B1'      => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'    => [['meningitis_meningococcemia', null], ['encephalitis_others', null]],
                'B2'      => [['pneumonia_urgent', null], [null, 'B3']],
                'B3'      => [['refer_diagram_10', null], [null, 'B4']],
                'B4'      => [[null, 'B4_1'], [null, 'B5']],
                'B4_1'    => [['stevens_johnson', null], [null, 'B4_2']],
                'B4_2'    => [['boil_impetigo', null], [null, 'B4_3']],
                'B4_3'    => [[null, 'B4_4'], [null, 'B5']],
                'B4_4'    => [['chickenpox', null], [null, 'B4_5']],
                'B4_5'    => [['hand_foot_mouth', null], ['herpes_zoster', null]],
                'B5'      => [[null, 'B5_1'], ['rash_symptomatic_care_5', null]],
                'B5_1'    => [[null, 'B5_1_1'], [null, 'B5_2']],
                'B5_1_1'  => [['dengue_fever', null], [null, 'B5_1_2']],
                'B5_1_2'  => [['measles', null], [null, 'B5_2']],
                'B5_2'    => [['rubella', null], [null, 'B5_3']],
                'B5_3'    => [['scrub_typhus', null], [null, 'B5_4']],
                'B5_4'    => [['scarlet_fever', null], [null, 'B5_5']],
                'B5_5'    => [['drug_eruption', null], [null, 'B5_6']],
                'B5_6'    => [['roseola_infantum', null], ['symptomatic_care_5_7', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'meningitis_meningococcemia' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => implode("\n", [
                        'เยื่อหุ้มสมองอักเสบ (66)/',
                        'ไข้กาฬหลังแอ่น (66.1)/',
                        '⊕ ด่วน',
                    ]),
                    'refs'       => ['66', '66.1'],
                    'diagrams'   => [],
                ],
                'encephalitis_others' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => implode("\n", [
                        '⊕ ด่วน อาจเป็น',
                        'สมองอักเสบ (65)/อื่นๆ',
                    ]),
                    'refs'       => ['65'],
                    'diagrams'   => [],
                ],
                'pneumonia_urgent' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => implode("\n", [
                        'ปอดอักเสบ (19)',
                        '⊕ ด่วน',
                    ]),
                    'refs'       => ['19'],
                    'diagrams'   => [],
                ],
                'refer_diagram_10' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => 'ดูแผนภูมิที่ 10 จุดแดง-จ้ำเขียว กรอบที่ (1.1)',
                    'refs'       => [],
                    'diagrams'   => ['00010'],
                ],
                'stevens_johnson' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => implode("\n", [
                        'กลุ่มอาการสตีเวนส์จอห์นสัน (207.1)',
                        '⊕ ภายใน 24 ชั่วโมง',
                    ]),
                    'refs'       => ['207.1'],
                    'diagrams'   => [],
                ],
                'boil_impetigo' => [
                    'urgency'    => 'G',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => implode("\n", [
                        'ฝี (192.1)/พุพอง (192.2)',
                        '• พาราเซตามอล (ย1.2)',
                        '• ไดคล็อกซาซิลลิน (ย4.3) หรืออีริโทรไมซิน (ย4.4) หรือโคไตรม็อกซาโซล (ย4.11.2)',
                        '⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือเป็นเบาหวาน (117) สงสัยเมลิออยโดซิส (229.2)/เป็นหลังกินหอยนางรมดิบหรือหลังเล่นน้ำทะเล',
                    ]),
                    'refs'       => ['192.1', '192.2', '117', '229.2'],
                    'diagrams'   => [],
                ],
                'chickenpox' => [
                    'urgency'    => 'G',
                    'time_frame' => 'สังเกตอาการใกล้ชิด',
                    'note'       => implode("\n", [
                        'อีสุกอีใส (6)',
                        'รักษาตามอาการ',
                        '• พาราเซตามอล (ย1.2)',
                        '• ถ้าคันทายาแก้ผื่นคัน (ย25.5)',
                        '• ถ้าเจ็บปากให้ดื่มน้ำแข็ง/น้ำเย็น/ไอศกรีม',
                        '• ยาปฏิชีวนะ (ย4.1, ย4.3, ย4.4) ถ้าตุ่มเป็นหนอง',
                        '⊕ ด่วน ถ้าหอบ/ชัก/ซึมมาก/อาเจียนมาก',
                        'สำหรับอีสุกอีใส เริม งูสวัด ให้อะไซโคลเวียร์ (ย4.17) ตามข้อบ่งชี้',
                    ]),
                    'refs'       => ['6'],
                    'diagrams'   => [],
                ],
                'hand_foot_mouth' => [
                    'urgency'    => 'G',
                    'time_frame' => 'สังเกตอาการใกล้ชิด',
                    'note'       => 'โรคมือ-เท้า-ปาก (229.1)',
                    'refs'       => ['229.1'],
                    'diagrams'   => [],
                ],
                'herpes_zoster' => [
                    'urgency'    => 'G',
                    'time_frame' => 'สังเกตอาการใกล้ชิด',
                    'note'       => 'อาจเป็นเริม (187)/งูสวัด (188)',
                    'refs'       => ['187', '188'],
                    'diagrams'   => [],
                ],
                'rash_symptomatic_care_5' => [
                    'urgency'    => 'G',
                    'time_frame' => 'ภายใน 4 วัน',
                    'note'       => implode("\n", [
                        'รักษาตามอาการ',
                        '• พาราเซตามอล (ย1.2)',
                        '• ถ้าคันทายาแก้ผื่นคัน (ย25.5)',
                        '⊕ ถ้าไม่ดีขึ้นใน 4 วัน หรือมีอาการเปลี่ยนแปลงที่ไม่ดี',
                    ]),
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'dengue_fever' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => implode("\n", [
                        'ไข้เลือดออก (225)',
                        '• เช็ดตัว',
                        '• ดื่มน้ำมากๆ',
                        '• พาราเซตามอล (ย1.2)',
                        '• ดูอาการใกล้ชิดทุกวัน',
                        '⊕ ด่วน ถ้ามีเลือดออก/ช็อก/ปวดท้องมาก/อาเจียน/กินไม่ได้',
                    ]),
                    'refs'       => ['225'],
                    'diagrams'   => [],
                ],
                'measles' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ด่วน',
                    'note'       => implode("\n", [
                        'หัด (3)',
                        '• พาราเซตามอล (ย1.2)',
                        '• ห้ามให้ยาปฏิชีวนะ',
                        '⊕ ด่วน ถ้าหอบ/ชัก/ไม่ค่อยรู้สึกตัว/ท้องเดินรุนแรง',
                    ]),
                    'refs'       => ['3'],
                    'diagrams'   => [],
                ],
                'rubella' => [
                    'urgency'    => 'G',
                    'time_frame' => 'ภายใน 1-2 วัน',
                    'note'       => implode("\n", [
                        'หัดเยอรมัน (4)',
                        '• พาราเซตามอล (ย1.2)',
                        '• ถ้าพบในหญิงที่ตั้งครรภ์ระยะ 3 เดือนแรก ควรพบแพทย์ใน 1-2 วัน',
                    ]),
                    'refs'       => ['4'],
                    'diagrams'   => [],
                ],
                'scrub_typhus' => [
                    'urgency'    => 'G',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => implode("\n", [
                        'สครับไทฟัส (226)',
                        '• ชันสูตรเพิ่มเติม',
                        '• ยาลดไข้ (ย1)',
                        '• ด็อกซีไซคลิน (ย4.5.1) หรือไรแฟมพิซิน (ย4.14)',
                        '⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือไม่ค่อยรู้สึกตัว/หอบ/ถ่ายอุจจาระดำ',
                    ]),
                    'refs'       => ['226'],
                    'diagrams'   => [],
                ],
                'scarlet_fever' => [
                    'urgency'    => 'G',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => implode("\n", [
                        'อีดำอีแดง (9)',
                        '• ยาลดไข้ (ย1)',
                        '• เพนิซิลลินวี (ย4.1) หรืออะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.4)',
                        '⊕ ถ้าไม่ดีขึ้นใน 3 วัน ถ้าดีขึ้นกินยาปฏิชีวนะจนครบ 10 วัน',
                    ]),
                    'refs'       => ['9'],
                    'diagrams'   => [],
                ],
                'drug_eruption' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'แนะนำให้พบแพทย์เดิม',
                    'note'       => implode("\n", [
                        'ผื่นจากยา',
                        '• หยุดยา',
                        '• ถ้าคันกินยาแก้แพ้ (ย7)',
                        '• แนะนำให้กลับไปพบแพทย์ที่รักษาอยู่เดิมเพื่อปรับเปลี่ยนยา',
                    ]),
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'roseola_infantum' => [
                    'urgency'    => 'G',
                    'time_frame' => 'สังเกตอาการใกล้ชิด',
                    'note'       => implode("\n", [
                        'ไข้ผื่นกุหลาบในทารก (5)',
                        'ไม่ต้องให้ยาอะไรถ้าเด็กสบายดี',
                    ]),
                    'refs'       => ['5'],
                    'diagrams'   => [],
                ],
                'symptomatic_care_5_7' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 7 วัน',
                    'note'       => implode("\n", [
                        'รักษาตามอาการ',
                        '• พาราเซตามอล (ย1.2)',
                        '• ถ้าคันทายาแก้ผื่นคัน (ย25.5)',
                        '⊕ ถ้าไม่ดีขึ้นใน 7 วัน หรือมีอาการปวดบวมตามข้อ/น้ำหนักลดฮวบ/หอบ/ชัก/ซึมมาก/ปวดท้องมาก/อาเจียน/กินไม่ได้',
                    ]),
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
                    'medical_reference' => 'แผนภูมิที่ 4',
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

        $this->command->info('สร้างแผนภูมิที่ 4 (ไข้ร่วมกับมีผื่นหรือตุ่มขึ้น) กรอบ 1-5.7 สำเร็จ');
    }
}
