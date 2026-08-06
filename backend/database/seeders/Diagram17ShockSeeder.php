<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram17ShockSeeder extends Seeder
{
    private const DIAGRAM_ID = '00017';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '4.1', '4.2', '5', '6',
                '7', '7.1', '7.2', '8', '9'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 17 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 17
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ช็อก (SHOCK)',
                'diagram_name_en' => 'Shock',
                'description' => 'อ่อนเพลีย ลุกขึ้นหน้ามืด ผิวหนังซีดเขียว เหงื่อออก ตัวเย็น มือเท้าเย็น กระหายน้ำ กระสับกระส่าย ปัสสาวะออกน้อย ไม่ค่อยรู้สึกตัว หายใจเร็ว ชีพจรเบาและเร็ว (เต้นมากกว่านาทีละ 100 ครั้ง) และความดันโลหิตต่ำกว่าปกติ (หรือความดันช่วงบนต่างจากความดันช่วงล่างน้อยกว่า 30 มม.ปรอท) สาเหตุที่พบบ่อย ท้องเดิน (32) เสียเลือด ไข้เลือดออก (225) ครรภ์นอกมดลูก (157) กระเพาะอาหารทะลุ (52) กล้ามเนื้อหัวใจตาย (96) แพ้ยา ถ้าอาการไม่ชัดเจน ให้น้ำเกลือ แล้วส่งโรงพยาบาลด่วน',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 17
            $boxes = [
                'B1'    => ['frame' => '1',    'type' => 'S', 'q' => 'หมดสติ?'],
                'B2'    => ['frame' => '2',    'type' => 'S', 'q' => 'มีประวัติการเสียเลือด เช่น บาดเจ็บ? กระดูกหัก? อาเจียนเป็นเลือด? ไอเป็นเลือด? ถ่ายเป็นเลือด? ถ่ายดำ? ตกเลือดทางช่องคลอด? เป็นต้น'],
                'B3'    => ['frame' => '3',    'type' => 'S', 'q' => 'ท้องเดินรุนแรง? หรือ อาเจียนรุนแรง?'],
                'B4'    => ['frame' => '4',    'type' => 'S', 'q' => 'ปวดท้องรุนแรง?'],
                'B4_1'  => ['frame' => '4.1',  'type' => 'S', 'q' => 'พบในผู้หญิงที่แต่งงานแล้ว มีประวัติขาดประจำเดือน หรือมีประจำเดือนกระปริดกระปรอย?'],
                'B4_2'  => ['frame' => '4.2',  'type' => 'S', 'q' => 'มีประวัติเป็นโรคกระเพาะ? หรือ กินยาแก้ปวด? หรือดื่มแอลกอฮอล์จัด?'],
                'B5'    => ['frame' => '5',    'type' => 'S', 'q' => 'เจ็บหน้าอกรุนแรง?'],
                'B6'    => ['frame' => '6',    'type' => 'S', 'q' => 'บาดแผลไฟไหม้ น้ำร้อนลวกรุนแรง?'],
                'B7'    => ['frame' => '7',    'type' => 'S', 'q' => 'มีไข้?'],
                'B7_1'  => ['frame' => '7.1',  'type' => 'S', 'q' => 'มีไข้นานเกิน 7 วัน? หรือ มีอาการติดเชื้อชัดเจน (เช่น ฝี ปอดบวม กรวยไตอักเสบ ปีกมดลูกอักเสบ ไทฟอยด์)?'],
                'B7_2'  => ['frame' => '7.2',  'type' => 'S', 'q' => 'มีไข้สูงนำมาก่อน 3-4 วัน พบในช่วงฤดูฝน? หรือ อยู่ในแหล่งที่มีการระบาดของไข้เลือดออก?'],
                'B8'    => ['frame' => '8',    'type' => 'S', 'q' => 'มีประวัติกินยาชุด ยาลูกกลอน หรือยาสเตียรอยด์ติดต่อกันมานาน แล้วหยุดยากะทันหัน? หรือ รูปร่างอ้วน หน้าอูมกลม มีก้อนไขมันที่หลังคอ พุงป่อง?'],
                'B9'    => ['frame' => '9',    'type' => 'S', 'q' => 'อาการเกิดหลังฉีดยา? หรือ หลังถูกผึ้ง/ต่อต่อน?'],
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
            // รูปแบบ: 'BoxKey' => [[ Yes: ruleKey, nextBoxKey, nextDiagramId ], [ No: ruleKey, nextBoxKey, nextDiagramId ]]
            $binary = [
                'B1'    => [[null, null, '00016'], [null, 'B2', null]], // ใช่ -> ไปดูแผนภูมิที่ 16 หมดสติ กรอบ 1
                'B2'    => [['hypovolemic_shock_blood', null, null], [null, 'B3', null]],
                'B3'    => [['hypovolemic_shock_fluid', null, null], [null, 'B4', null]],
                'B4'    => [[null, 'B4_1', null], [null, 'B5', null]],
                'B4_1'  => [['ectopic_pregnancy', null, null], [null, 'B4_2', null]],
                'B4_2'  => [['perforated_peptic_ulcer', null, null], ['severe_abdominal_cause', null, null]],
                'B5'    => [['cardiogenic_shock_chest', null, null], [null, 'B6', null]],
                'B6'    => [['burns_scalds', null, null], [null, 'B7', null]],
                'B7'    => [[null, 'B7_1', null], [null, 'B8', null]],
                'B7_1'  => [['septic_shock', null, null], [null, 'B7_2', null]],
                'B7_2'  => [['dengue_hemorrhagic_fever', null, null], ['thyroid_adrenal_crisis', null, null]],
                'B8'    => [['acute_adrenal_insufficiency', null, null], [null, 'B9', null]],
                'B9'    => [['anaphylactic_shock', null, null], ['other_shock_causes', null, null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1, $yes[2]);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2, $no[2]);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'hypovolemic_shock_blood' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ช็อกจากปริมาตรของเลือดลดลง (91)
⊕ ด่วน พร้อมกับให้น้ำเกลือไประหว่างทาง
NOTE,
                    'refs'       => ['91'],
                    'diagrams'   => [],
                ],
                'hypovolemic_shock_fluid' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ช็อกจากปริมาตรของเลือดลดลง (91)
⊕ ด่วน พร้อมกับให้น้ำเกลือไประหว่างทาง (ดู "โรคที่ 32, 34, 35, 54, 91")
NOTE,
                    'refs'       => ['32', '34', '35', '54', '91'],
                    'diagrams'   => [],
                ],
                'ectopic_pregnancy' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ครรภ์นอกมดลูก (157)
⊕ ด่วน พร้อมกับให้น้ำเกลือไประหว่างทาง
NOTE,
                    'refs'       => ['157'],
                    'diagrams'   => [],
                ],
                'perforated_peptic_ulcer' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
กระเพาะอาหารทะลุ (52)
⊕ ด่วน พร้อมกับให้น้ำเกลือไประหว่างทาง
NOTE,
                    'refs'       => ['52'],
                    'diagrams'   => [],
                ],
                'severe_abdominal_cause' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุร้ายแรง เช่น เยื่อบุช่องท้องอักเสบ (47)/ตับอ่อนอักเสบ (48)/กระเพาะ/ลำไส้อุดตัน (54)
⊕ พร้อมกับให้น้ำเกลือไประหว่างทาง
NOTE,
                    'refs'       => ['47', '48', '54'],
                    'diagrams'   => [],
                ],
                'cardiogenic_shock_chest' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นกล้ามเนื้อหัวใจตาย (96)/เยื่อหุ้มหัวใจอักเสบ/ภาวะสิ่งหลุดอุดตันหลอดเลือดแดงปอด (ดู "โรคที่ 99.1")/ภาวะเลือดเซาะผนังหลอดเลือดแดงใหญ่ (92.1)
⊕ พร้อมกับให้น้ำเกลือไประหว่างทาง
NOTE,
                    'refs'       => ['96', '99.1', '92.1'],
                    'diagrams'   => [],
                ],
                'burns_scalds' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ไฟไหม้น้ำร้อนลวก (218)
⊕ ด่วน พร้อมกับให้น้ำเกลือไประหว่างทาง
NOTE,
                    'refs'       => ['218'],
                    'diagrams'   => [],
                ],
                'septic_shock' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
โลหิตเป็นพิษ (228)
⊕ ด่วน
NOTE,
                    'refs'       => ['228'],
                    'diagrams'   => [],
                ],
                'dengue_hemorrhagic_fever' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ไข้เลือดออก (225)
⊕ ด่วน พร้อมกับให้น้ำเกลือไประหว่างทาง
NOTE,
                    'refs'       => ['225'],
                    'diagrams'   => [],
                ],
                'thyroid_adrenal_crisis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุอื่นๆ เช่น ภาวะต่อมไทรอยด์วิกฤติ (ดู "โรคที่ 121") ภาวะต่อมหมวกไตบกพร่องฉับพลัน (ดู "โรคที่ 91") เป็นต้น
NOTE,
                    'refs'       => ['121', '91'],
                    'diagrams'   => [],
                ],
                'acute_adrenal_insufficiency' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะต่อมหมวกไตบกพร่องฉับพลัน (ดู "โรคที่ 91")
⊕ ด่วน
NOTE,
                    'refs'       => ['91'],
                    'diagrams'   => [],
                ],
                'anaphylactic_shock' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ช็อกจากการแพ้ (91)/ผึ้งหรือต่อต่อน (222)
• ฉีดอะดรีนาลีน (ย11) ไดเฟนไฮดรามีน (ย7.2) รานิทิดีน (ย14.3) และเมทิลเพรดนิโซโลน (ย12)
⊕ ถ้าไม่ดีขึ้นใน 30 นาที
NOTE,
                    'refs'       => ['91', '222'],
                    'diagrams'   => [],
                ],
                'other_shock_causes' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
สาเหตุอื่นๆ
⊕ ด่วน พร้อมกับให้น้ำเกลือไประหว่างทาง
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
                    'medical_reference' => 'แผนภูมิที่ 17',
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

        $this->command->info('สร้างแผนภูมิที่ 17 (ช็อก - SHOCK) กรอบ 1-9 สำเร็จ');
    }
}
