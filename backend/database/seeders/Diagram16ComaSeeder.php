<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram16ComaSeeder extends Seeder
{
    private const DIAGRAM_ID = '00016';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '5', '6', '7',
                '8', '9', '10', '11', '12', '12.1', '13', '14', '15'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 16 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 16
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'หมดสติ (COMA)',
                'diagram_name_en' => 'Coma',
                'description' => 'มีอาการแน่นิ่ง หมดความรู้สึกทุกอย่าง สาเหตุที่พบบ่อย น้ำตาลในเลือดต่ำ (118) มาลาเรียขึ้นสมอง (224) เยื่อหุ้มสมองอักเสบ (66) ศีรษะได้รับบาดเจ็บ (81) หลอดเลือดสมองแตก (76) กล้ามเนื้อหัวใจตาย (96) กินยาพิษหรือยาฆ่าแมลง (219) ถ้าอาการไม่ชัดเจน ควรให้การปฐมพยาบาล และส่งโรงพยาบาลด่วน',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 16
            $boxes = [
                'B1'    => ['frame' => '1',    'type' => 'S', 'q' => 'ได้รับบาดเจ็บที่ศีรษะ? หรือ รูม่านตา 2 ข้างไม่เท่ากัน?'],
                'B2'    => ['frame' => '2',    'type' => 'S', 'q' => 'กินยาพิษ ยาฆ่าแมลง สัตว์พิษ เห็ดพิษ หรือยานอนหลับ? ดื่มแอลกอฮอล์จัด? หรือ ใช้ยาเสพติดเกินขนาด?'],
                'B3'    => ['frame' => '3',    'type' => 'S', 'q' => 'จมน้ำ?'],
                'B4'    => ['frame' => '4',    'type' => 'S', 'q' => 'ไฟฟ้าช็อต?'],
                'B5'    => ['frame' => '5',    'type' => 'S', 'q' => 'เจ็บหน้าอกรุนแรงก่อนหมดสติ?'],
                'B6'    => ['frame' => '6',    'type' => 'S', 'q' => 'รูม่านตาหดเล็กทั้ง 2 ข้าง? และ ความดันโลหิตสูง?'],
                'B7'    => ['frame' => '7',    'type' => 'S', 'q' => 'ก่อนหมดสติตามีอาการปวดศีรษะ หรืออาเจียนรุนแรง? หรือ ตรวจพบคอแข็ง?'],
                'B8'    => ['frame' => '8',    'type' => 'S', 'q' => 'อดข้าว? หรือ ผู้ป่วยกินยาหรือฉีดยารักษาเบาหวาน?'],
                'B9'    => ['frame' => '9',    'type' => 'S', 'q' => 'ดีซ่าน? มีจุดแดงรูปแมงมุม? หรือ มีประวัติเป็นโรคตับแข็ง?'],
                'B10'   => ['frame' => '10',   'type' => 'S', 'q' => 'มีประวัติเป็นโรคไต ความดันโลหิตสูงหรือเบาหวานมานาน?'],
                'B11'   => ['frame' => '11',   'type' => 'S', 'q' => 'หายใจหอบลึกในผู้ป่วยที่เป็นเบาหวาน? หรือ ตรวจพบน้ำตาลหรือสารคีโตนในปัสสาวะ?'],
                'B12'   => ['frame' => '12',   'type' => 'S', 'q' => 'มีไข้?'],
                'B12_1' => ['frame' => '12.1', 'type' => 'S', 'q' => 'มีไข้สูงและมีประวัติเผชิญคลื่นความร้อน? หรือทำงาน/ออกกำลังกายกลางแจ้งท่ามกลางอากาศร้อน?'],
                'B13'   => ['frame' => '13',   'type' => 'S', 'q' => 'มีประวัติฉีดยาก่อนหมดสติ?'],
                'B14'   => ['frame' => '14',   'type' => 'S', 'q' => 'ตัวเย็น ซีด ชีพจรช้า และมีประวัติสัมผัสอากาศหนาวเย็น หรือแช่อยู่ในน้ำเย็นจัด?'],
                'B15'   => ['frame' => '15',   'type' => 'S', 'q' => 'ก่อนหมดสติ มีอาการอาเจียน หลังจากฟื้นหายจากไข้หวัด/โรคติดเชื้อไวรัสอื่นๆ? และ ตรวจพบตับโต?'],
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
                'B1'    => [['head_brain_trauma', null], [null, 'B2']],
                'B2'    => [['poisoning_substance', null], [null, 'B3']],
                'B3'    => [['drowning', null], [null, 'B4']],
                'B4'    => [['electric_shock', null], [null, 'B5']],
                'B5'    => [['myocardial_infarction', null], [null, 'B6']],
                'B6'    => [['hemorrhagic_stroke', null], [null, 'B7']],
                'B7'    => [['meningitis_stroke', null], [null, 'B8']],
                'B8'    => [['hypoglycemia', null], [null, 'B9']],
                'B9'    => [['hepatic_coma', null], [null, 'B10']],
                'B10'   => [['uremic_coma', null], [null, 'B11']],
                'B11'   => [['diabetic_coma', null], [null, 'B12']],
                'B12'   => [[null, 'B12_1'], [null, 'B13']],
                'B12_1' => [['heat_stroke', null], ['cns_infection_malaria', null]],
                'B13'   => [['anaphylactic_shock', null], [null, 'B14']],
                'B14'   => [['hypothermia', null], [null, 'B15']],
                'B15'   => [['reye_syndrome', null], ['unexplained_coma', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'head_brain_trauma' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ศีรษะได้รับบาดเจ็บ (81)/ฝีสมอง (82)/เนื้องอกสมอง (83)
⊕ ด่วน
NOTE,
                    'refs'       => ['81', '82', '83'],
                    'diagrams'   => [],
                ],
                'poisoning_substance' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
สาเหตุจากสารพิษ/ยาพิษ (219)/สัตว์พิษ (219.1-219.4)/พิษเห็ด (219.5)/แอลกอฮอล์/ยาเสพติด (มอร์ฟีน เฮโรอีน)
⊕ ด่วน
NOTE,
                    'refs'       => ['219', '219.1-219.4', '219.5'],
                    'diagrams'   => [],
                ],
                'drowning' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
จมน้ำ (216)
• ช่วยหายใจถ้าหยุดหายใจ
• นวดหัวใจถ้าหัวใจหยุดเต้น
⊕ ด่วน
NOTE,
                    'refs'       => ['216'],
                    'diagrams'   => [],
                ],
                'electric_shock' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ไฟฟ้าช็อต (217)
• ช่วยหายใจถ้าหยุดหายใจ
• นวดหัวใจถ้าหัวใจหยุดเต้น
⊕ ด่วน
NOTE,
                    'refs'       => ['217'],
                    'diagrams'   => [],
                ],
                'myocardial_infarction' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
กล้ามเนื้อหัวใจตาย (96)
⊕ ด่วน
NOTE,
                    'refs'       => ['96'],
                    'diagrams'   => [],
                ],
                'hemorrhagic_stroke' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
หลอดเลือดสมองแตก (76)
⊕ ด่วน
NOTE,
                    'refs'       => ['76'],
                    'diagrams'   => [],
                ],
                'meningitis_stroke' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
เยื่อหุ้มสมองอักเสบ (66)/หลอดเลือดสมองแตก (76)
⊕ ด่วน
NOTE,
                    'refs'       => ['66', '76'],
                    'diagrams'   => [],
                ],
                'hypoglycemia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
น้ำตาลในเลือดต่ำ (118)
• ฉีดกลูโคส และให้น้ำเกลือที่มีเดกซ์โทรสผสม
⊕ ถ้าไม่ดีขึ้นใน 30 นาที
NOTE,
                    'refs'       => ['118'],
                    'diagrams'   => [],
                ],
                'hepatic_coma' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะหมดสติจากตับวาย (44)
⊕ ด่วน
NOTE,
                    'refs'       => ['44'],
                    'diagrams'   => [],
                ],
                'uremic_coma' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะไตวาย (134)
⊕ ด่วน
NOTE,
                    'refs'       => ['134'],
                    'diagrams'   => [],
                ],
                'diabetic_coma' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะหมดสติจากเบาหวาน (117)
⊕ ด่วน พร้อมกับให้น้ำเกลือนอร์มัลซาไลน์ไประหว่างทาง
NOTE,
                    'refs'       => ['117'],
                    'diagrams'   => [],
                ],
                'heat_stroke' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
โรคลมจากความร้อน (218.1)
• ปฐมพยาบาล
⊕ ด่วน
NOTE,
                    'refs'       => ['218.1'],
                    'diagrams'   => [],
                ],
                'cns_infection_malaria' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นเยื่อหุ้มสมองอักเสบ (66)/สมองอักเสบ (65)/โรคพิษสุนัขบ้า (64)/มาลาเรียขึ้นสมอง (224)
NOTE,
                    'refs'       => ['66', '65', '64', '224'],
                    'diagrams'   => [],
                ],
                'anaphylactic_shock' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
แพ้ยา (ดูเรื่อง "การแพ้ยา" ในภาค 2)
• ฉีดอะดรีนาลีน (ย11) ไดเฟนไฮดรามีน (ย7.2) รานิทิดีน (ย14.3) และเมทิลเพรดนิโซโลน (ย12)
• ปฐมพยาบาล
⊕ ด่วน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'hypothermia' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะตัวเย็นเกิน (218.2)
• ปฐมพยาบาล
⊕ ด่วน
NOTE,
                    'refs'       => ['218.2'],
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
                'unexplained_coma' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุร้ายแรงอื่นๆ
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
                    'medical_reference' => 'แผนภูมิที่ 16',
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

        $this->command->info('สร้างแผนภูมิที่ 16 (หมดสติ - COMA) กรอบ 1-15 สำเร็จ');
    }
}
