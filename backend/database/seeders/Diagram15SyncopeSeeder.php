<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram15SyncopeSeeder extends Seeder
{
    private const DIAGRAM_ID = '00015';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '4.1', '5', '6', '7',
                '8', '9', '10', '11', '12', '13', '14', '15', '16'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 15 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 15
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'เป็นลม (SYNCOPE/FAINTING)',
                'diagram_name_en' => 'Syncope / Fainting',
                'description' => 'อาการหน้ามืด ตาฟาง มือเท้าเย็น เหงื่อออก ใจหวิว ใจสั่น และมีอาการแน่นิ่ง หมดความรู้สึกตัวไปชั่วประเดี๋ยวเดียว (มักเป็นอยู่ไม่เกิน 5 นาที) แล้วกลับฟื้นคืนสติได้เอง สาเหตุที่พบบ่อย เป็นลมธรรมดา (74) น้ำตาลในเลือดต่ำ (118) โรคลมชัก (70) ความดันตกในท่ายืน (93) ทีไอเอ (76) ถ้าอาการไม่ชัดเจน ให้การปฐมพยาบาล ถ้าไม่ดีขึ้น หรือเป็นซ้ำอีกควรปรึกษาแพทย์',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 15
            $boxes = [
                'B1'    => ['frame' => '1',    'type' => 'S', 'q' => 'แน่นิ่ง หมดความรู้สึกตัว?'],
                'B2'    => ['frame' => '2',    'type' => 'S', 'q' => 'หมดความรู้สึกตัว ชั่วประเดี๋ยวเดียว แล้วฟื้นได้เอง?'],
                'B3'    => ['frame' => '3',    'type' => 'S', 'q' => 'ศีรษะได้รับบาดเจ็บ?'],
                'B4'    => ['frame' => '4',    'type' => 'S', 'q' => 'ขณะหมดสติ มีอาการชักเกร็ง หรือกระตุก หรือแขนขาอ่อนแรงชั่วประเดี๋ยว?'],
                'B4_1'  => ['frame' => '4.1',  'type' => 'S', 'q' => 'เป็นๆ หายๆ ประจำ? หรือมีประวัติเป็นโรคลมชัก?'],
                'B5'    => ['frame' => '5',    'type' => 'S', 'q' => 'เห็นภาพซ้อน? พูดไม่ชัด? กลืนลำบาก? หรือแขนขาชาหรืออ่อนแรง?'],
                'B6'    => ['frame' => '6',    'type' => 'S', 'q' => 'เจ็บหน้าอกรุนแรง?'],
                'B7'    => ['frame' => '7',    'type' => 'S', 'q' => 'ถ่ายดำ?'],
                'B8'    => ['frame' => '8',    'type' => 'S', 'q' => 'มีเลือดออก? ปวดท้องรุนแรง? อาเจียนรุนแรง? ท้องเดินรุนแรง? หรือ ความดันโลหิตช่วงบนต่างจากช่วงล่างน้อยกว่า 30 มม.ปรอท?'],
                'B9'    => ['frame' => '9',    'type' => 'S', 'q' => 'ปวดแน่นลิ้นปี่ร้าวขึ้นขากรรไกร คอ แขน? ชีพจรเต้น > 120 ครั้ง หรือ < 50 ครั้ง/นาที หรือเต้นไม่สม่ำเสมอ? เท้าบวม? หรือฟังหัวใจมีเสียงฟู่ (murmur)?'],
                'B10'   => ['frame' => '10',   'type' => 'S', 'q' => 'ความดันช่วงบนในท่ายืนต่ำกว่าท่านอน > 20 มม.ปรอท? ความดันช่วงล่างในท่ายืนต่ำกว่าท่านอน > 10 มม.ปรอท? หรือ พบร่วมกันทั้ง 2 อย่าง?'],
                'B11'   => ['frame' => '11',   'type' => 'S', 'q' => 'อดข้าว? หรือช่วยกินยาหรือฉีดยารักษาเบาหวาน?'],
                'B12'   => ['frame' => '12',   'type' => 'S', 'q' => 'เป็นลมขณะไอแรงๆ เบ่งถ่ายหลังถ่ายอุจจาระ ขณะก้นคอ หรือโกนหนวด?'],
                'B13'   => ['frame' => '13',   'type' => 'S', 'q' => 'ปวดตุบๆ ที่ขมับ? หรือ มีสาเหตุกระตุ้นแบบเดียวกับไมเกรน?'],
                'B14'   => ['frame' => '14',   'type' => 'S', 'q' => 'ตื่นเต้นตกใจกลัว? เสียใจ? เจ็บปวด? อากาศร้อนอึดอัด? อยู่ในฝูงชนแออัด? อดนอน? หิวข้าว? หรือ ร่างกายอ่อนเพลียมาก?'],
                'B15'   => ['frame' => '15',   'type' => 'S', 'q' => 'หายใจหอบลึก มือจีบเกร็ง? หรือ วิตกกังวล?'],
                'B16'   => ['frame' => '16',   'type' => 'S', 'q' => 'เป็นๆ หายๆ โดยไม่ทราบสาเหตุ?'],
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
                'B1'    => [[null, 'B2', null], [null, null, '00022']], // ไม่ใช่ -> ไปดูแผนภูมิที่ 22 กรอบที่ 2
                'B2'    => [[null, 'B3', null], [null, null, '00016']], // ไม่ใช่ -> ไปดูแผนภูมิที่ 16 กรอบที่ 1
                'B3'    => [['head_injury', null, null], [null, 'B4', null]],
                'B4'    => [[null, 'B4_1', null], [null, 'B5', null]],
                'B4_1'  => [['epilepsy', null, null], [null, null, '00018']], // ไม่ใช่ -> ไปดูแผนภูมิที่ 18 กรอบที่ 3.1
                'B5'    => [['brain_syncope', null, null], [null, 'B6', null]],
                'B6'    => [['chest_pain_severe', null, null], [null, 'B7', null]],
                'B7'    => [['melena', null, null], [null, 'B8', null]],
                'B8'    => [['severe_bleeding_shock', null, null], [null, 'B9', null]],
                'B9'    => [['cardiac_syncope', null, null], [null, 'B10', null]],
                'B10'   => [['postural_hypotension', null, null], [null, 'B11', null]],
                'B11'   => [['hypoglycemia', null, null], [null, 'B12', null]],
                'B12'   => [['situational_syncope', null, null], [null, 'B13', null]],
                'B13'   => [['migraine', null, null], [null, 'B14', null]],
                'B14'   => [['vasovagal_syncope', null, null], [null, 'B15', null]],
                'B15'   => [['hyperventilation_anxiety', null, null], [null, 'B16', null]],
                'B16'   => [['unexplained_recurrent_syncope', null, null], ['first_aid_care', null, null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1, $yes[2]);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2, $no[2]);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'head_injury' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ศีรษะได้รับบาดเจ็บ (81)
• สังเกตอาการใกล้ชิด
⊕ ถ้ามีอาการปวดศีรษะมาก/อาเจียนรุนแรง/แขนขาอ่อนแรง/รูม่านตา 2 ข้างไม่เท่ากัน
NOTE,
                    'refs'       => ['81'],
                    'diagrams'   => [],
                ],
                'epilepsy' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคลมชัก (70)
• ยากันชัก (ย18)
⊕ ถ้ายังชักบ่อย
NOTE,
                    'refs'       => ['70'],
                    'diagrams'   => [],
                ],
                'brain_syncope' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
เป็นลมจากโรคสมอง (74)
⊕ ด่วน
NOTE,
                    'refs'       => ['74'],
                    'diagrams'   => [],
                ],
                'chest_pain_severe' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นโรคกล้ามเนื้อหัวใจตาย (96)/ภาวะสิ่งหลุดอุดตันหลอดเลือดแดงปอด (ดู "โรคที่ 99.1")/ภาวะเลือดเซาะผนังหลอดเลือดแดงใหญ่ (92.1)
NOTE,
                    'refs'       => ['96', '99.1', '92.1'],
                    'diagrams'   => [],
                ],
                'melena' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน ภาวะเลือดออกในกระเพาะอาหาร จากกระเพาะอาหารอักเสบ (50)/แผลเพ็ปติก (51)/อื่นๆ
NOTE,
                    'refs'       => ['50', '51'],
                    'diagrams'   => [],
                ],
                'severe_bleeding_shock' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน พร้อมกับให้น้ำเกลือในระหว่างทาง อาจมีภาวะขาดน้ำรุนแรง หรือช็อก (ดู "โรคที่ 32, 34, 35, 47, 48, 52, 54, 56, 91, 96, 157")
NOTE,
                    'refs'       => ['32', '34', '35', '47', '48', '52', '54', '56', '91', '96', '157'],
                    'diagrams'   => [],
                ],
                'cardiac_syncope' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
เป็นลมจากโรคหัวใจ (74)
⊕ ด่วน
NOTE,
                    'refs'       => ['74'],
                    'diagrams'   => [],
                ],
                'postural_hypotension' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ความดันตกในท่ายืน (93)
• รักษาตามสาเหตุ เช่น แก้ไขภาวะขาดน้ำ ปรับลดลดยลดความดันโลหิต
⊕ ถ้ามีภาวะตกเลือด/เป็นลมนานเกิน 5 นาที
NOTE,
                    'refs'       => ['93'],
                    'diagrams'   => [],
                ],
                'hypoglycemia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
น้ำตาลในเลือดต่ำ (118)
• ให้ดื่มน้ำหวานหรือฉีดกลูโคส
NOTE,
                    'refs'       => ['118'],
                    'diagrams'   => [],
                ],
                'situational_syncope' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เป็นลมจากอากัปกิริยาบางอย่าง (74)
• ปฐมพยาบาล
⊕ ถ้าไม่ฟื้นสติใน 5 นาทีหรือเป็นๆ หายๆ บ่อย
NOTE,
                    'refs'       => ['74'],
                    'diagrams'   => [],
                ],
                'migraine' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไมเกรน (71)
• ให้การดูแลรักษาแบบไมเกรน
NOTE,
                    'refs'       => ['71'],
                    'diagrams'   => [],
                ],
                'vasovagal_syncope' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เป็นลมธรรมดา (74)
• ปฐมพยาบาล
⊕ ถ้าไม่ฟื้นสติใน 5 นาทีหรือเป็นๆ หายๆ บ่อย
NOTE,
                    'refs'       => ['74'],
                    'diagrams'   => [],
                ],
                'hyperventilation_anxiety' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กลุ่มอาการระบายลมหายใจเกิน (89)/โรควิตกกังวล/โรคกังวลทั่วไป (88)
• ให้การดูแลรักษาตามโรคที่เป็นสาเหตุ
NOTE,
                    'refs'       => ['89', '88'],
                    'diagrams'   => [],
                ],
                'unexplained_recurrent_syncope' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'first_aid_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ปฐมพยาบาล นอนราบศีรษะต่ำ/ปลดเสื้อผ้า เข็มขัด สิ่งรัดคอให้หลวม
⊕ ถ้าไม่ฟื้นสติใน 5 นาที/มีอาการเป็นลมอีก
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
                    'medical_reference' => 'แผนภูมิที่ 15',
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

        $this->command->info('สร้างแผนภูมิที่ 15 (เป็นลม - SYNCOPE/FAINTING) กรอบ 1-16 สำเร็จ');
    }
}
