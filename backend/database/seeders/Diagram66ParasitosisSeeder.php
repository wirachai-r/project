<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram66ParasitosisSeeder extends Seeder
{
    private const DIAGRAM_ID = '00066';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 66 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 66
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'โรคหนอนพยาธิ (PARASITOSIS)',
                'diagram_name_en' => 'Parasitosis',
                'description' => 'ถ่ายหรืออาเจียนเป็นตัวหนอนพยาธิ หรือมีอาการที่ชวนสงสัยว่าเป็นโรคพยาธิ เช่น พุงโรตูดปอด กินจุแต่ไม่อ้วน ปวดท้องหรืออาเจียนบ่อยๆ โดยไม่ทราบสาเหตุ คันก้นตอนกลางคืน ซีด โลหิตจาง ตับโต ชัก ทวารหนักโผล่ เป็นต้น สาเหตุที่พบบ่อย โรคพยาธิเส้นด้าย (231) ไส้เดือน (230) ปากขอ (233) ตัวตืด (232) ตัวจี๊ด (236) โรคพยาธิใบไม้ตับ (45) ถ้าอาการไม่ชัดเจน ให้ส่งตรวจอุจจาระหาสาเหตุให้แน่ชัด',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes)
            $boxes = [
                'B1' => ['frame' => '1', 'type' => 'S', 'q' => 'มีพยาธิไส้เดือนออกทางปาก หรือทวารหนัก?'],
                'B2' => ['frame' => '2', 'type' => 'S', 'q' => 'ถ่ายเป็นปล้องแบนๆ หรือเส้นยาวๆ แบนๆ คล้ายก๋วยเตี๋ยว?'],
                'B3' => ['frame' => '3', 'type' => 'S', 'q' => 'คันก้นหรือช่องคลอดตอนกลางคืน และพบตัวพยาธิเส้นด้ายที่ปากทวารหนัก?'],
                'B4' => ['frame' => '4', 'type' => 'S', 'q' => 'ทวารหนักโผล่และมีตัวพยาธิเกาะที่ทวารหนัก?'],
                'B5' => ['frame' => '5', 'type' => 'S', 'q' => 'ซีด?'],
                'B6' => ['frame' => '6', 'type' => 'S', 'q' => 'มีไข้ ท้องเดิน และเจ็บปวดตามกล้ามเนื้อมาก ซึ่งพบหลังกินแหนม ลาบหมู หรือลาบวัว?'],
                'B7' => ['frame' => '7', 'type' => 'S', 'q' => 'ดีซ่าน และตับโตเป็นก้อนแข็ง ในคนอีสานอายุมากกว่า 40 ปี?'],
                'B8' => ['frame' => '8', 'type' => 'S', 'q' => 'มีก้อนบวมคันตามผิวหนัง ซึ่งเลื่อนที่ไปเรื่อยๆ?'],
                'B9' => ['frame' => '9', 'type' => 'S', 'q' => 'เด็กกินจุแต่น้ำหนักไม่ขึ้น หรือน้ำหนักลด? ปวดท้องหรืออาเจียนบ่อยๆ โดยไม่ทราบสาเหตุชัดเจน? หรือ ท้องเดินเรื้อรัง?'],
            ];

            // สร้าง box_id ถัดไปอัตโนมัติ
            $nextBoxId = ((int) DB::table('question_boxes')->max('box_id')) + 1;
            foreach ($boxes as &$box) {
                $box['id'] = str_pad((string) $nextBoxId++, 10, '0', STR_PAD_LEFT);
            }
            unset($box);

            // Insert คำถามลง DB
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

            // ตั้งค่า Entry Box
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'entry_box_id' => $boxes['B1']['id'],
            ]);

            // 3. กำหนด Choice และ Flow
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

            // โครงสร้างการตัดสินใจแบบ Binary
            $binary = [
                'B1' => [['ascariasis', null], [null, 'B2']],
                'B2' => [['taeniasis', null], [null, 'B3']],
                'B3' => [['enterobiasis', null], [null, 'B4']],
                'B4' => [['trichuriasis', null], [null, 'B5']],
                'B5' => [['hookworm', null], [null, 'B6']],
                'B6' => [['trichinosis_urgent', null], [null, 'B7']],
                'B7' => [['opisthorchiasis_1w', null], [null, 'B8']],
                'B8' => [['gnathostomiasis', null], [null, 'B9']],
                'B9' => [['suspected_helminthiasis', null], ['asymptomatic_or_other_symptoms', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนด Diagnosis Rules
            $rules = [
                'ascariasis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคพยาธิไส้เดือน (230)
• ยาถ่ายพยาธิ (ย6)
NOTE,
                    'refs'       => ['230'],
                    'diagrams'   => [],
                ],
                'taeniasis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคพยาธิตัวตืด (232)
• ยาถ่ายพยาธิ (ย6)
• ดีเกลือ (ย16.4)
NOTE,
                    'refs'       => ['232'],
                    'diagrams'   => [],
                ],
                'enterobiasis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคพยาธิเส้นด้าย (231)
• ยาถ่ายพยาธิ (ย6)
NOTE,
                    'refs'       => ['231'],
                    'diagrams'   => [],
                ],
                'trichuriasis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคพยาธิแส้ม้า (234)
• มีเบนดาโซล (ย6.4)
NOTE,
                    'refs'       => ['234'],
                    'diagrams'   => [],
                ],
                'hookworm' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคพยาธิปากขอ (233)
• ตรวจอุจจาระยืนยัน
• ยาถ่ายพยาธิ (ย6)
• ยาบำรุงโลหิต (ย24.11)
NOTE,
                    'refs'       => ['233'],
                    'diagrams'   => [],
                ],
                'trichinosis_urgent' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
อาจเป็นทริคิโนซิส (235)
⊕ ด่วน
NOTE,
                    'refs'       => ['235'],
                    'diagrams'   => [],
                ],
                'opisthorchiasis_1w' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
อาจเป็นโรคพยาธิใบไม้ตับ (45)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['45'],
                    'diagrams'   => [],
                ],
                'gnathostomiasis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคพยาธิตัวจี๊ด (236)
• ชันสูตรเพิ่มเติม
• อัลเบนดาโซล (ย6.3)
NOTE,
                    'refs'       => ['236'],
                    'diagrams'   => [],
                ],
                'suspected_helminthiasis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อาจเป็นโรคพยาธิ
• ตรวจอุจจาระดูไข่พยาธิและให้การรักษาตามสาเหตุที่พบ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'asymptomatic_or_other_symptoms' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ถ้าผู้ป่วยสบายดีไม่ต้องทำอะไร
• ถ้ามีอาการผิดปกติอื่นๆ ควรตรวจดูอาการเพิ่มเติม
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
            ];

            // 5. Insert Rules, Conditions, Diseases, Next Diagrams
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
                    'medical_reference' => 'แผนภูมิที่ 66',
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

                // Insert Conditions
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

                // Insert Rule Diseases
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

                // Insert Rule Next Diagrams
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

        $this->command->info('สร้างแผนภูมิที่ 66 (โรคหนอนพยาธิ) สำเร็จ');
    }
}
