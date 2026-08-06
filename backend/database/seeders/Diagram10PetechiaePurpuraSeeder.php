<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram10PetechiaePurpuraSeeder extends Seeder
{
    private const DIAGRAM_ID = '00010';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.2', '1.3', '2', '3', '4', '5', '5.1', '5.2', '6', '7', '7.1', '7.2', '7.3', '8'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 10 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 10
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'จุดแดง (PETECHIAE) จ้ำเขียว (PURPURA)',
                'diagram_name_en' => 'Petechiae / Purpura',
                'description' => 'จุดแดง (ขนาด 1 มม. หรือเท่าปลายเข็มหมุด) หรือจ้ำเขียวพรายย้ำ (ขนาด 1-10 มม.) หรือเป็นรอยแดงแผ่นบางกว้างๆ ตื้นๆ ขึ้นตามผิวหนัง เมื่อใช้มือกดดึงหนังส่วนนั้นให้ตึง จุดหรือจ้ำเหล่านี้ไม่จางหาย จุดแดงหรือจ้ำเขียวอาจเกิดแยกกันโดดๆ หรือเกิดร่วมกันก็ได้ สาเหตุที่พบบ่อย 1. ถ้ามีไข้ร่วมด้วย: ไข้เลือดออก (225), มะเร็งเม็ดเลือดขาว (106), เอสแอลอี (111), โลหิตจางจากไขกระดูกฝ่อ (103) 2. ถ้าไม่มีไข้: ไอทีพี (104), ผู้หญิงเวลาที่มีประจำเดือน, ผู้สูงอายุ, รอยฟกช้ำ / ถ้าอาการไม่ชัดเจน ควรแนะนำไปโรงพยาบาลภายใน 1 สัปดาห์',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 10
            $boxes = [
                'B1'     => ['frame' => '1',   'type' => 'S', 'q' => 'มีไข้?'],
                'B1_1'   => ['frame' => '1.1', 'type' => 'S', 'q' => 'คอแข็ง?'],
                'B1_2'   => [
                    'frame' => '1.2',
                    'type'  => 'S',
                    'q'     => 'มีไข้เกิน 7 วัน? ต่อมน้ำเหลืองโตทั่วไป? ซีด? หรือ มีเลือดออก (เช่น เลือดกำเดา เลือดออกตามไรฟัน ถ่ายเป็นเลือด)?'
                ],
                'B1_3'   => [
                    'frame' => '1.3',
                    'type'  => 'S',
                    'q'     => 'มีจุดแดงจ้ำเขียวเกิดขึ้นหลังมีไข้สูงตลอดเวลาอยู่ 3-4 วัน และ การทดสอบทูร์นิเกต์ให้ผลบวก?'
                ],
                'B2'     => ['frame' => '2',   'type' => 'S', 'q' => 'มีประวัติเลือดออกแล้วหยุดยาก เป็นๆหายๆ มาตั้งแต่เด็ก?'],
                'B3'     => [
                    'frame' => '3',
                    'type'  => 'S',
                    'q'     => 'ความดันโลหิตสูงร่วมกับซีด? หรือ มีประวัติเป็นโรคไต หรือ เบาหวาน หรือความดันโลหิตสูงมานาน?'
                ],
                'B4'     => ['frame' => '4',   'type' => 'S', 'q' => 'มีจุดแดงรูปแมงมุมที่หน้าอก/ต้นแขน? หรือ ฝ่ามือแดง?'],
                'B5'     => ['frame' => '5',   'type' => 'S', 'q' => 'ขึ้นเป็นจ้ำเขียว โดยไม่มีจุดแดง?'],
                'B5_1'   => ['frame' => '5.1', 'type' => 'S', 'q' => 'หลังถูกงูกัด?'],
                'B5_2'   => ['frame' => '5.2', 'type' => 'S', 'q' => 'พบในผู้หญิง เป็นเวลามีประจำเดือน โดยไม่มีอาการอื่นๆ?'],
                'B6'     => ['frame' => '6',   'type' => 'S', 'q' => 'มีจุดแดงขึ้นเพียงอย่างเดียว? หรือ มีจุดแดงร่วมกับจ้ำเขียว?'],
                'B7'     => ['frame' => '7',   'type' => 'S', 'q' => 'รอยเลือดออกใต้ผิวหนัง ลักษณะแผ่นกว้างๆ ตื้นๆ?'],
                'B7_1'   => [
                    'frame' => '7.1',
                    'type'  => 'S',
                    'q'     => 'กินยาชุดหรือยาลูกกลอนหรือยาสเตียรอยด์เป็นประจำ? หรือ น้ำหนักขึ้น หน้าอูมคล้ายพระจันทร์ และอ่อนเพลีย?'
                ],
                'B7_2'   => ['frame' => '7.2', 'type' => 'S', 'q' => 'พบเป็นๆ หายๆ ในผู้สูงอายุ?'],
                'B7_3'   => ['frame' => '7.3', 'type' => 'S', 'q' => 'พบในคนที่รูปร่างผอมกะหร่อง?'],
                'B8'     => ['frame' => '8',   'type' => 'S', 'q' => 'รอยฟกช้ำ เช่น ถูกกระทบกระแทก หยิก ข่วน?'],
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
                'B1'    => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'  => [['meningococcal_meningitis', null], [null, 'B1_2']],
                'B1_2'  => [['fever_bleeding_severe_causes', null], [null, 'B1_3']],
                'B1_3'  => [['dengue_hemorrhagic_fever', null], ['unexplained_fever_petechiae', null]],
                'B2'    => [['hemophilia', null], [null, 'B3']],
                'B3'    => [['chronic_renal_failure', null], [null, 'B4']],
                'B4'    => [['cirrhosis', null], [null, 'B5']],
                'B5'    => [[null, 'B5_1'], [null, 'B6']],
                'B5_1'  => [['snake_bite', null], [null, 'B5_2']],
                'B5_2'  => [['normal_female_purpura', null], ['unexplained_ecchymosis', null]],
                'B6'    => [['itp_or_others', null], [null, 'B7']],
                'B7'    => [[null, 'B7_1'], [null, 'B8']],
                'B7_1'  => [['cushing_or_steroid', null], [null, 'B7_2']],
                'B7_2'  => [['senile_purpura', null], [null, 'B7_3']],
                'B7_3'  => [['vascular_fragility_emaciated', null], [null, 'B8']],
                'B8'    => [['ecchymosis_contusion', null], ['unexplained_skin_hemorrhage', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'meningococcal_meningitis' => [
                    'urgency'  => 'R',
                    'note'     => "ไข้กาฬหลังแอ่น (65.1)\n⊕ ด่วน",
                    'refs'     => ['65.1'],
                    'diagrams' => [],
                ],
                'fever_bleeding_severe_causes' => [
                    'urgency'  => 'R',
                    'note'     => "⊕ ภายใน 24 ชั่วโมง อาจเป็นมะเร็งเม็ดเลือดขาว (106)/โลหิตจางจากไขกระดูกฝ่อ (103)/ไข้เลือดออก (225)/เล็ปโตสไปโรซิส (227)/เยื่อบุหัวใจอักเสบ (95)/โลหิตเป็นพิษ (228)/เอดส์ (238)/เอสแอลอี (111)",
                    'refs'     => ['106', '103', '225', '227', '95', '228', '238', '111'],
                    'diagrams' => [],
                ],
                'dengue_hemorrhagic_fever' => [
                    'urgency'  => 'R',
                    'note'     => implode("\n", [
                        'ไข้เลือดออก (225)',
                        '• รักษาตามอาการ',
                        '• สังเกตอาการใกล้ชิดทุกวัน',
                        '⊕ ด่วน ถ้ามีเลือดออกหรือช็อก',
                    ]),
                    'refs'     => ['225'],
                    'diagrams' => [],
                ],
                'unexplained_fever_petechiae' => [
                    'urgency'  => 'P',
                    'note'     => "⊕ ภายใน 3 วัน เพื่อตรวจหาสาเหตุ",
                    'refs'     => [],
                    'diagrams' => [],
                ],
                'hemophilia' => [
                    'urgency'  => 'R',
                    'note'     => "ฮีโมฟีเลีย (104) (พบในผู้ชายเป็นส่วนมาก)\n⊕ ด่วน ถ้ามีเลือดออก",
                    'refs'     => ['104'],
                    'diagrams' => [],
                ],
                'chronic_renal_failure' => [
                    'urgency'  => 'P',
                    'note'     => "ภาวะไตวายเรื้อรัง (134)\n⊕ ภายใน 3 วัน",
                    'refs'     => ['134'],
                    'diagrams' => [],
                ],
                'cirrhosis' => [
                    'urgency'  => 'P',
                    'note'     => "ตับแข็ง (44)\n⊕ ภายใน 3 วัน\n⊕ ด่วน ถ้ามีเลือดออก",
                    'refs'     => ['44'],
                    'diagrams' => [],
                ],
                'snake_bite' => [
                    'urgency'  => 'R',
                    'note'     => "งูแมวเซา/งูกะปะ/งูเขียวหางไหม้กัด (221)\n⊕ ด่วน ถ้ามีเลือดออกควรฉีดเซรุ่มแก้พิษงูให้ก่อนส่ง",
                    'refs'     => ['221'],
                    'diagrams' => [],
                ],
                'normal_female_purpura' => [
                    'urgency'  => 'G',
                    'note'     => "เป็นภาวะปกติที่พบในผู้หญิง อาจเป็นๆ หายๆ ได้บ่อย โดยไม่มีอันตรายแต่อย่างใด\n⊕ ถ้าซีด",
                    'refs'     => [],
                    'diagrams' => [],
                ],
                'unexplained_ecchymosis' => [
                    'urgency'  => 'P',
                    'note'     => "⊕ ภายใน 3 วัน\n⊕ ด่วน ถ้ามีเลือดออก/ช็อก/ซีด",
                    'refs'     => [],
                    'diagrams' => [],
                ],
                'itp_or_others' => [
                    'urgency'  => 'P',
                    'note'     => "⊕ ภายใน 3 วัน อาจเป็นไอทีพี (104)/อื่นๆ",
                    'refs'     => ['104'],
                    'diagrams' => [],
                ],
                'cushing_or_steroid' => [
                    'urgency'  => 'P',
                    'note'     => "โรคคุชชิง (125) หรือเป็นผลจากยาสเตียรอยด์ (ย12)\n⊕ ภายใน 3 วัน",
                    'refs'     => ['125'],
                    'diagrams' => [],
                ],
                'senile_purpura' => [
                    'urgency'  => 'G',
                    'note'     => "หลอดเลือดบางเปราะเนื่องจากสูงอายุ\n• คอยระวังอย่าให้กระทบกระแทก",
                    'refs'     => [],
                    'diagrams' => [],
                ],
                'vascular_fragility_emaciated' => [
                    'urgency'  => 'G',
                    'note'     => "หลอดเลือดบางเปราะ ควรหาสาเหตุของอาการผอม ดูแผนภูมิที่ 6",
                    'refs'     => [],
                    'diagrams' => ['00006'],
                ],
                'ecchymosis_contusion' => [
                    'urgency'  => 'G',
                    'note'     => implode("\n", [
                        'ฟกช้ำ',
                        'ภายใน 48 ชั่วโมงแรก ประคบด้วยน้ำเย็น',
                        'ระยะหลัง 48 ชั่วโมง ประคบด้วยน้ำอุ่นจัดๆ',
                        '⊕ ถ้ามีไข้/ซีด/มีเลือดออกจากอวัยวะต่างๆ',
                    ]),
                    'refs'     => [],
                    'diagrams' => [],
                ],
                'unexplained_skin_hemorrhage' => [
                    'urgency'  => 'Y',
                    'note'     => "⊕ ภายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ\n⊕ ด่วน ถ้ามีเลือดออกจากอวัยวะต่างๆ",
                    'refs'     => [],
                    'diagrams' => [],
                ],
            ];

            // 5. บันทึก Rules, Conditions, Disease references, Next Diagram references ลง DB
            $ruleNumber = ((int) DB::table('diagnosis_rules')->max('rule_id')) + 1;
            $conditionNumber = ((int) DB::table('rule_conditions')->max('condition_id')) + 1;

            foreach ($rules as $key => $rule) {
                $ruleId = str_pad((string) $ruleNumber, 10, '0', STR_PAD_LEFT);
                DB::table('diagnosis_rules')->updateOrInsert(['rule_id' => $ruleId], [
                    'urgency_level' => $rule['urgency'],
                    'time_frame' => null,
                    'time_frame_en' => null,
                    'note' => $rule['note'],
                    'note_en' => null,
                    'medical_reference' => 'แผนภูมิที่ 10',
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

        $this->command->info('สร้างแผนภูมิที่ 10 (จุดแดง / จ้ำเขียว - PETECHIAE / PURPURA) กรอบ 1-8 สำเร็จ');
    }
}
