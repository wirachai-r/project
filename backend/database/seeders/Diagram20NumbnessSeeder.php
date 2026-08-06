<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram20NumbnessSeeder extends Seeder
{
    private const DIAGRAM_ID = '00020';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '5', '5.1', '6', '7', '8',
                '9', '9.1', '9.1.1', '9.2', '9.3', '9.4', '9.5',
                '10', '10.1', '11', '12', '13'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 20 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 20
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ชา (NUMBNESS)',
                'diagram_name_en' => 'Numbness',
                'description' => 'มีความรู้สึกชาปลายมือปลายเท้า ชาปาก หรือชาเฉพาะที่ ถ้าใช้เข็มแทงรู้สึกเจ็บน้อยลง แสดงว่ามีความผิดปกติของระบบประสาท แต่ถ้ารู้สึกเจ็บเชนปกติก็ไม่เกี่ยวกับระบบประสาท สาเหตุที่พบบ่อย เหน็บกิน โรคกังวล (88) ความดันโลหิตสูง (92) เบาหวาน (117) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 13',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 20
            $boxes = [
                'B1'     => ['frame' => '1',     'type' => 'S', 'q' => 'แขนขาแข็งแรงดี?'],
                'B2'     => ['frame' => '2',     'type' => 'S', 'q' => 'ความดันโลหิตช่วงบน ≥ 140 หรือ ช่วงล่าง ≥ 90 มม.ปรอท?'],
                'B3'     => ['frame' => '3',     'type' => 'S', 'q' => 'ปัสสาวะบ่อย? ดื่มน้ำบ่อย? หิวบ่อย? หรือ ตรวจพบน้ำตาลในปัสสาวะ?'],
                'B4'     => ['frame' => '4',     'type' => 'S', 'q' => 'มีประวัติดื่มแอลกอฮอล์จัด?'],
                'B5'     => ['frame' => '5',     'type' => 'S', 'q' => 'ชาเพียงซีกใดซีกหนึ่งของร่างกาย แบบเกิดขึ้นฉับพลัน?'],
                'B5_1'   => ['frame' => '5.1',   'type' => 'S', 'q' => 'เป็นอยู่นานไม่เกิน 30 นาทีแล้วหายได้เอง?'],
                'B6'     => ['frame' => '6',     'type' => 'S', 'q' => 'ปวดศีรษะติดต่อกันทุกวัน นานเกินกว่า 2 สัปดาห์?'],
                'B7'     => ['frame' => '7',     'type' => 'S', 'q' => 'บวม? หรือ ซีด?'],
                'B8'     => ['frame' => '8',     'type' => 'S', 'q' => 'ปากและลิ้นชา/มีความรู้สึกผิดเพี้ยนหลังกินปลาปักเป้า/แมงดาทะเล/ปลาหรือหอยทะเล?'],
                'B9'     => ['frame' => '9',     'type' => 'S', 'q' => 'ชาปลายมือ/ปลายเท้า?'],
                'B9_1'   => ['frame' => '9.1',   'type' => 'S', 'q' => 'ข้างเดียว?'],
                'B9_1_1' => ['frame' => '9.1.1', 'type' => 'S', 'q' => 'ปวดคอ? หรือ ปวดหลัง?'],
                'B9_2'   => ['frame' => '9.2',   'type' => 'S', 'q' => 'ปวดแสบมีดมากตอนกลางคืน? หรือ ปวดชาปลายมือเวลางอข้อมือเร็วๆ?'],
                'B9_3'   => ['frame' => '9.3',   'type' => 'S', 'q' => 'เป็นครั้งคราวเวลานั่ง นอน หรือหิ้วของนานๆ?'],
                'B9_4'   => ['frame' => '9.4',   'type' => 'S', 'q' => 'ปวดตามข้อต่างๆ?'],
                'B9_5'   => ['frame' => '9.5',   'type' => 'S', 'q' => 'หายใจหอบลึก มือจีบเกร็ง และเป็นเวลาที่มีเรื่องขัดใจ?'],
                'B10'    => ['frame' => '10',    'type' => 'S', 'q' => 'เข็มแทงเจ็บเท่าปกติ?'],
                'B10_1'  => ['frame' => '10.1',  'type' => 'S', 'q' => 'คิดมาก? นอนไม่หลับ? หรือ มีอารมณ์ซึมเศร้า?'],
                'B11'    => ['frame' => '11',    'type' => 'S', 'q' => 'ชาตรงรอยด่างขาว ตุ่มหรือแผ่นหนาที่ขึ้นตามผิวหนัง?'],
                'B12'    => ['frame' => '12',    'type' => 'S', 'q' => 'พบในผู้ที่ทำงานหนัก หญิงตั้งครรภ์ หญิงให้นมบุตร หรือเด็กในวัยเจริญเติบโต?'],
                'B13'    => ['frame' => '13',    'type' => 'S', 'q' => 'การดูแลรักษาเพิ่มเติม/ติดตามอาการ'],
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
            $addChoice = function (string $boxKey, string $text, ?string $nextBoxKey, ?string $ruleKey, int $order, ?string $nextDiagramId = null) use (&$choiceNumber, &$terminalChoices, $boxes, $now) {
                $choiceId = str_pad((string) $choiceNumber++, 10, '0', STR_PAD_LEFT);
                DB::table('answer_choices')->updateOrInsert(['choice_id' => $choiceId], [
                    'choice_text' => $text,
                    'choice_text_en' => null,
                    'order' => $order,
                    'status' => '1',
                    'box_id' => $boxes[$boxKey]['id'],
                    'next_box_id' => $nextBoxKey ? $boxes[$nextBoxKey]['id'] : null,
                    'next_diagram_id' => $nextDiagramId,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);
                if ($ruleKey) {
                    $terminalChoices[$ruleKey][] = ['box_id' => $boxes[$boxKey]['id'], 'choice_id' => $choiceId];
                }
            };

            // แผนที่การตัดสินใจ (Binary Decision Tree)
            $binary = [
                'B1'     => [[null, 'B2', null], [null, null, '00019']],
                'B2'     => [['hypertension', null, null], [null, 'B3', null]],
                'B3'     => [['diabetes_mellitus', null, null], [null, 'B4', null]],
                'B4'     => [['alcoholic_neuropathy_beriberi', null, null], [null, 'B5', null]],
                'B5'     => [[null, 'B5_1', null], [null, 'B6', null]],
                'B5_1'   => [['transient_ischemic_attack_numbness', null, null], ['stroke_numbness', null, null]],
                'B6'     => [['brain_tumor_numbness', null, null], [null, 'B7', null]],
                'B7'     => [['chronic_renal_failure_numbness', null, null], [null, 'B8', null]],
                'B8'     => [['marine_toxin_numbness', null, null], [null, 'B9', null]],
                'B9'     => [[null, 'B9_1', null], [null, 'B10', null]],
                'B9_1'   => [[null, 'B9_1_1', null], [null, 'B9_2', null]],
                'B9_1_1' => [['nerve_root_compression_numbness', null, null], [null, 'B9_2', null]],
                'B9_2'   => [['carpal_tunnel_syndrome', null, null], [null, 'B9_3', null]],
                'B9_3'   => [['paresthesia_posture', null, null], [null, 'B9_4', null]],
                'B9_4'   => [[null, null, '00052'], [null, 'B9_5', null]],
                'B9_5'   => [['hyperventilation_syndrome', null, null], [null, 'B10', null]],
                'B10'    => [[null, 'B10_1', null], [null, 'B11', null]],
                'B10_1'  => [['anxiety_depression_numbness', null, null], ['observe_mental_numbness', null, null]],
                'B11'    => [['leprosy', null, null], [null, 'B12', null]],
                'B12'    => [['beriberi_risk_groups', null, null], ['general_numbness_treatment', 'B13', null]], // แก้ไขจุดนี้: ถ้า ไม่ใช่ B12 ให้ส่งไปที่ Rule กรอบ 13
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1, $yes[2]);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2, $no[2]);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'hypertension' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ความดันโลหิตสูง (92)
• ขันสูตรเพิ่มเติม
• ยาลดความดัน (ย22)
⊕ ถ้าควบคุมความดันไม่ได้/มีภาวะแทรกซ้อน/สงสัยเป็นความดันโลหิตสูงชนิดทุติยภูมิ
NOTE,
                    'refs'       => ['92'],
                    'diagrams'   => [],
                ],
                'diabetes_mellitus' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เบาหวาน (117)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['117'],
                    'diagrams'   => [],
                ],
                'alcoholic_neuropathy_beriberi' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปลายประสาทอักเสบจากแอลกอฮอล์ (87)/โรคเหน็บชา (132)
• งดดื่มแอลกอฮอล์
• วิตามินบีรวม (ย24.8)/วิตามินบี 1 (ย24.4)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['87', '132'],
                    'diagrams'   => [],
                ],
                'transient_ischemic_attack_numbness' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
โรคสมองขาดเลือดชั่วขณะ/ทีไอเอ (76)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['76'],
                    'diagrams'   => [],
                ],
                'stroke_numbness' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
โรคหลอดเลือดสมอง (76)
⊕ ด่วน
NOTE,
                    'refs'       => ['76'],
                    'diagrams'   => [],
                ],
                'brain_tumor_numbness' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์
อาจเป็นเนื้องอกสมอง (83)
NOTE,
                    'refs'       => ['83'],
                    'diagrams'   => [],
                ],
                'chronic_renal_failure_numbness' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน
อาจเป็นภาวะไตวายเรื้อรัง (134)/อื่นๆ
NOTE,
                    'refs'       => ['134'],
                    'diagrams'   => [],
                ],
                'marine_toxin_numbness' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
พิษจากสัตว์น้ำ/สัตว์ทะเล (219.1-219.3)
⊕ ด่วน
NOTE,
                    'refs'       => ['219.1', '219.2', '219.3'],
                    'diagrams'   => [],
                ],
                'nerve_root_compression_numbness' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
รากประสาทถูกกด (108)/กระดูกคอกดทับรากประสาท (108.1)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['108', '108.1'],
                    'diagrams'   => [],
                ],
                'carpal_tunnel_syndrome' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เส้นประสาทมือถูกพังผืดรัดแน่น (115)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['115'],
                    'diagrams'   => [],
                ],
                'paresthesia_posture' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เหน็บกิน
• ไม่มีอันตรายและไม่ต้องทำอะไร
⊕ ถ้าเป็นบ่อยหรือเรื้อรัง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'hyperventilation_syndrome' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กลุ่มอาการระบายลมหายใจเกิน (89)
• ไดอะซีแพม (ย17.1)
• หายใจในกรวยกระดาษ
⊕ ถ้าไม่ดีขึ้นใน 6 ชั่วโมง
NOTE,
                    'refs'       => ['89'],
                    'diagrams'   => [],
                ],
                'anxiety_depression_numbness' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรควิตกกังวล/โรควิตกกังวลทั่วไป (88)/โรคอารมณ์แปรปรวน/โรคซึมเศร้า (88.2)
• ไดอะซีแพม (ย17.1) ถ้ามีเรื่องวิตกกังวล หรือนอนไม่หลับ
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['88', '88.2'],
                    'diagrams'   => [],
                ],
                'observe_mental_numbness' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• สังเกตอาการ
⊕ ถ้าไม่ดีขึ้นใน 1-2 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'leprosy' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
โรคเรื้อน (197)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['197'],
                    'diagrams'   => [],
                ],
                'beriberi_risk_groups' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคเหน็บชา (132)
NOTE,
                    'refs'       => ['132'],
                    'diagrams'   => [],
                ],
                'general_numbness_treatment' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• วิตามินบี 1 (ย24.4) หรือ วิตามินบีรวม (ย24.8)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือ แขนขาอ่อนแรง/มีประวัติกินยาหม้อ (มีสารหนูผสม)/สงสัยเกิดจากการใช้ยา (เช่น ไอโซเนียซิด)
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
                    'medical_reference' => 'แผนภูมิที่ 20',
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

                // สร้างเงื่อนไข (Rule Conditions)
                DB::table('rule_conditions')->where('rule_id', $ruleId)->delete();
                if (isset($terminalChoices[$key])) {
                    foreach ($terminalChoices[$key] as $conditionIndex => $condition) {
                        // ป้องกันไม่ให้ insert ถ้าไม่มี choice_id
                        if (empty($condition['choice_id'])) {
                            continue;
                        }

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

        $this->command->info('สร้างแผนภูมิที่ 20 (ชา) กรอบ 1-13 สำเร็จ');
    }
}
