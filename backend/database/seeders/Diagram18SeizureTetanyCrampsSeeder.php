<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram18SeizureTetanyCrampsSeeder extends Seeder
{
    private const DIAGRAM_ID = '00018';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '3.1', '3.1.1', '3.1.2', '3.2', '3.3', '3.4', '3.5', '3.6',
                '4', '5', '5.1', '5.2', '6', '7', '7.1', '7.2', '8', '8.1', '9'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 18 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 18
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ชัก (SEIZURE)/มือเท้าเกร็ง (TETANY)/ตะคริว',
                'diagram_name_en' => 'Seizure / Tetany / Cramps',
                'description' => 'แขนขากระตุก หรือมีอาการมือเท้าเกร็งหรือเป็นตะคริว หรือแขนขามีการเคลื่อนไหวผิดปกติหรือลำบาก สาเหตุที่พบบ่อย 1. ชักร่วมกับมีไข้ : ชักจากไข้ (68) มาลาเรียขึ้นสมอง (224) บาดทะยัก (67) เยื่อหุ้มสมองอักเสบ (66) 2. ชักโดยไม่มีไข้ : ลมบ้าหมู (70) น้ำตาลในเลือดต่ำ (118) กลุ่มอาการระบายลมหายใจเกิน (89) ถ้าอาการไม่ชัดเจน ให้สังเกตอาการ ถ้ามีความวิตกกังวลให้ยาทางจิตประสาท (ย17) หากไม่ดีขึ้นใน 3 วัน ควรส่งโรงพยาบาล',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 18
            $boxes = [
                'B1'     => ['frame' => '1',     'type' => 'S', 'q' => 'พบอาการชักในทารกแรกเกิด?'],
                'B2'     => ['frame' => '2',     'type' => 'S', 'q' => 'มีไข้?'],
                'B3'     => ['frame' => '3',     'type' => 'S', 'q' => 'ขณะชัก มีอาการไม่รู้สึกตัว กัดลิ้น หรือปัสสาวะราด?'],
                'B3_1'   => ['frame' => '3.1',   'type' => 'S', 'q' => 'ความดันโลหิต ช่วงบน ≥ 140 หรือ ช่วงล่าง ≥ 90 มม.ปรอท?'],
                'B3_1_1' => ['frame' => '3.1.1', 'type' => 'S', 'q' => 'พบในผู้หญิงขณะคลอด?'],
                'B3_1_2' => ['frame' => '3.1.2', 'type' => 'S', 'q' => 'ปัสสาวะแดงเหมือนน้ำล้างเนื้อ?'],
                'B3_2'   => ['frame' => '3.2',   'type' => 'S', 'q' => 'มีประวัติศีรษะได้รับบาดเจ็บมาก่อน? หรือ รูม่านตา 2 ข้างไม่เท่ากัน?'],
                'B3_3'   => ['frame' => '3.3',   'type' => 'S', 'q' => 'อดข้าว? ดื่มแอลกอฮอล์จัด? หรือ ผู้ป่วยกินยาหรือฉีดยารักษาเบาหวาน?'],
                'B3_4'   => ['frame' => '3.4',   'type' => 'S', 'q' => 'ใช้สารเสพติด (แอมเฟตามีน/โคเคน)? หรือ หยุดการดื่มแอลกอฮอล์กะทันหันในผู้ที่เป็นพิษสุราเรื้อรัง?'],
                'B3_5'   => ['frame' => '3.5',   'type' => 'S', 'q' => 'เคยเป็นลมบ้าหมู? เคยชักเป็นประจำ? หรือ ชักครั้งละ 1-3 นาที?'],
                'B3_6'   => ['frame' => '3.6',   'type' => 'S', 'q' => 'ตับโต? และ ก่อนชักมีอาการอาเจียน หลังจากฟื้นหายจากไข้หวัด/โรคติดเชื้อไวรัสอื่นๆ?'],
                'B4'     => ['frame' => '4',     'type' => 'S', 'q' => 'แขนขาหรือลำตัวกระตุกเพียงส่วนเดียวหรือพร้อมกันหลายส่วน นาน 2-3 วินาที?'],
                'B5'     => ['frame' => '5',     'type' => 'S', 'q' => 'แขนขาเคลื่อนไหวผิดปกติ?'],
                'B5_1'   => ['frame' => '5.1',   'type' => 'S', 'q' => 'เป็นมาตั้งแต่เกิด? หรือ หลังจากเป็นโรคร้ายแรงทางสมอง?'],
                'B5_2'   => ['frame' => '5.2',   'type' => 'S', 'q' => 'มีประวัติเจ็บคอมาก่อน 1-4 สัปดาห์? ข้อบวม แดงร้อน? หรือ ใช้เครื่องฟังตรวจหัวใจมีเสียงฟู่ (murmur)?'],
                'B6'     => ['frame' => '6',     'type' => 'S', 'q' => 'มือสั่น? แขนขาเกร็ง? และ เคลื่อนไหวลำบาก?'],
                'B7'     => ['frame' => '7',     'type' => 'S', 'q' => 'มือจีบเกร็ง (tetany)?'],
                'B7_1'   => ['frame' => '7.1',   'type' => 'S', 'q' => 'เป็นทันทีหลังมีเรื่องขัดใจ แล้วหายใจหอบลึก?'],
                'B7_2'   => ['frame' => '7.2',   'type' => 'S', 'q' => 'เคยผ่าตัดต่อมไทรอยด์?'],
                'B8'     => ['frame' => '8',     'type' => 'S', 'q' => 'คอเอียง? หรือเอี้ยวคอไม่ได้?'],
                'B8_1'   => ['frame' => '8.1',   'type' => 'S', 'q' => 'ลิ้นเกร็ง (พูดอ้อแอ้) เกิดขึ้นหลังกินยาเมโทโคลพราไมด์ (ย19.2) หรือยารักษาโรคจิตบางชนิด?'],
                'B9'     => ['frame' => '9',     'type' => 'S', 'q' => 'เป็นตะคริว (ปวดเกร็งกล้ามเนื้อเฉพาะที่)?'],
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
                'B1'     => [['neonatal_seizure', null, null], [null, 'B2', null]],
                'B2'     => [[null, null, '00001'], [null, 'B3', null]], // ใช่ -> ไปดูแผนภูมิที่ 1 กรอบที่ 1
                'B3'     => [[null, 'B3_1', null], [null, 'B4', null]],
                'B3_1'   => [[null, 'B3_1_1', null], [null, 'B3_2', null]],
                'B3_1_1' => [['eclampsia', null, null], [null, 'B3_1_2', null]],
                'B3_1_2' => [['acute_glomerulonephritis', null, null], ['hypertension_seizure', null, null]],
                'B3_2'   => [['head_brain_injury', null, null], [null, 'B3_3', null]],
                'B3_3'   => [['hypoglycemia', null, null], [null, 'B3_4', null]],
                'B3_4'   => [['substance_alcohol_withdrawal', null, null], [null, 'B3_5', null]],
                'B3_5'   => [['epilepsy', null, null], [null, 'B3_6', null]],
                'B3_6'   => [['reye_syndrome', null, null], ['unexplained_severe_seizure', null, null]],
                'B4'     => [['partial_myoclonic_seizure', null, null], [null, 'B5', null]],
                'B5'     => [[null, 'B5_1', null], [null, 'B6', null]],
                'B5_1'   => [['cerebral_palsy', null, null], [null, 'B5_2', null]],
                'B5_2'   => [['rheumatic_fever', null, null], ['brain_tumor_movement', null, null]],
                'B6'     => [['parkinsonism', null, null], [null, 'B7', null]],
                'B7'     => [[null, 'B7_1', null], [null, 'B8', null]],
                'B7_1'   => [['hyperventilation_syndrome', null, null], [null, 'B7_2', null]],
                'B7_2'   => [['hypocalcemia_post_thyroidectomy', null, null], ['hypocalcemia_other', null, null]],
                'B8'     => [[null, 'B8_1', null], [null, 'B9', null]],
                'B8_1'   => [['drug_induced_dystonia', null, null], ['neck_muscle_strain', null, null]],
                'B9'     => [['muscle_cramps', null, null], ['observation_anxiety', null, null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1, $yes[2]);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2, $no[2]);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'neonatal_seizure' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ชักในทารกแรกเกิด (69)
⊕ ด่วน
NOTE,
                    'refs'       => ['69'],
                    'diagrams'   => [],
                ],
                'eclampsia' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ครรภ์เป็นพิษ (155)
• ฉีดหรือสวน ไดอะซีแพม (ย17.1)
⊕ ด่วน
NOTE,
                    'refs'       => ['155'],
                    'diagrams'   => [],
                ],
                'acute_glomerulonephritis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
หน่วยไตอักเสบฉับพลัน (136)
• ฉีดหรือสวน ไดอะซีแพม (ย17.1)
⊕ ด่วน
NOTE,
                    'refs'       => ['136'],
                    'diagrams'   => [],
                ],
                'hypertension_seizure' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ความดันโลหิตสูง (92)
• ฉีดหรือสวน ไดอะซีแพม (ย17.1)
⊕ ด่วน
NOTE,
                    'refs'       => ['92'],
                    'diagrams'   => [],
                ],
                'head_brain_injury' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ศีรษะได้รับบาดเจ็บ/เลือดออกในสมอง (81)
⊕ ด่วน
NOTE,
                    'refs'       => ['81'],
                    'diagrams'   => [],
                ],
                'hypoglycemia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
น้ำตาลในเลือดต่ำ (118)
• ฉีดกลูโคส
⊕ ถ้าไม่ดีขึ้นใน 30 นาที
NOTE,
                    'refs'       => ['118'],
                    'diagrams'   => [],
                ],
                'substance_alcohol_withdrawal' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ชักจากสารกระตุ้น/การถอนแอลกอฮอล์
• ปฐมพยาบาล
⊕ ด่วน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'epilepsy' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ลมบ้าหมู (70)
• ยากันชัก (ย18)
⊕ ถ้ายังชักบ่อย/ชักต่อเนื่อง
NOTE,
                    'refs'       => ['70'],
                    'diagrams'   => [],
                ],
                'reye_syndrome' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
โรคเรย์ซินโดรม (65.1)
⊕ ด่วน
NOTE,
                    'refs'       => ['65.1'],
                    'diagrams'   => [],
                ],
                'unexplained_severe_seizure' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ โดยเฉพาะอย่างยิ่งถ้าเป็นการชักครั้งแรก
⊕ ด่วน ถ้านานเกิน 5 นาที/ชักต่อเนื่อง อาจเป็นลมบ้าหมู (70)/เนื้องอกสมอง (83)/พยาธิในสมอง (232)/ตะกั่วเป็นพิษ (220)/สารพิษ (219)/พิษเห็ด (219.5)/พิษคางคก (219.4)/ไตวาย (134)/ตับวาย/อื่นๆ
NOTE,
                    'refs'       => ['70', '83', '232', '220', '219', '219.5', '219.4', '134'],
                    'diagrams'   => [],
                ],
                'partial_myoclonic_seizure' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
โรคลมชักเฉพาะส่วน แบบธรรมดา/โรคลมชักแบบกระตุก (70)
⊕ ภายใน 3 วัน
NOTE,
                    'refs'       => ['70'],
                    'diagrams'   => [],
                ],
                'cerebral_palsy' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สมองพิการ (80)
⊕ ถ้าช่วยตัวเองไม่ได้ อาจต้องทำการกายภาพบำบัด
NOTE,
                    'refs'       => ['80'],
                    'diagrams'   => [],
                ],
                'rheumatic_fever' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ไข้รูมาติก (94)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['94'],
                    'diagrams'   => [],
                ],
                'brain_tumor_movement' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน อาจเป็นเนื้องอกสมอง (83)/อื่นๆ
NOTE,
                    'refs'       => ['83'],
                    'diagrams'   => [],
                ],
                'parkinsonism' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
พาร์กินสัน (79.1) (มักพบในวัยกลางคนและผู้สูงอายุ)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['79.1'],
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
                'hypocalcemia_post_thyroidectomy' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ภาวะแคลเซียมในเลือดต่ำ (119) เนื่องจากต่อมพาราไทรอยด์ถูกตัดออกไปบางส่วน
• ให้แคลเซียม (ย24.2, ย24.2.1) ฉีดหรือกิน
⊕ ถ้าไม่ดีขึ้นใน 6 ชั่วโมง
NOTE,
                    'refs'       => ['119'],
                    'diagrams'   => [],
                ],
                'hypocalcemia_other' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจมีสาเหตุอื่นๆ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'drug_induced_dystonia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ผลข้างเคียงจากยา* (เช่น เมโทโคลพราไมด์, อะมิทริปไทลีน, ฟีโนไทอาซีน, ฮาโลเพอริดอล, ซินนาริซีน, ฟลุนาริซีน, เลโวโดพา, เมทิลโดพา เป็นต้น)
• หยุดยา
• ไดเฟนไฮดรามีน (ย7.2)
⊕ ถ้าไม่หายใน 6 ชั่วโมง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'neck_muscle_strain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ปวด (ย1) ถ้าปวด
• ยาคลายกล้ามเนื้อ (ย3)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'muscle_cramps' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ตะคริว (116)
• นวด
⊕ ถ้าเป็นๆ หายๆ บ่อย
NOTE,
                    'refs'       => ['116'],
                    'diagrams'   => [],
                ],
                'observation_anxiety' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• สังเกตอาการ
• ยาทางจิตประสาท (ย17) ถ้ามีความวิตกกังวล
⊕ ถ้าไม่ดีขึ้นใน 3 วัน
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
                    'medical_reference' => 'แผนภูมิที่ 18',
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

        $this->command->info('สร้างแผนภูมิที่ 18 (ชัก/มือเท้าเกร็ง/ตะคริว) กรอบ 1-9 สำเร็จ');
    }
}
