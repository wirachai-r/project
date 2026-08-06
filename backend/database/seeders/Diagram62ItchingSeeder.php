<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram62ItchingSeeder extends Seeder
{
    private const DIAGRAM_ID = '00062';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '2.1', '2.2', '2.2.1',
                '3', '3.1', '3.2', '3.3', '4', '5', '6'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 62 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 62
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'คัน (ITCHING/PRURITUS)',
                'diagram_name_en' => 'Itching / Pruritus',
                'description' => 'มีอาการคันตามตัว คันศีรษะ คันช่องคลอด หรือคันก้นโดยไม่มีผื่น ตุ่ม หรือวงด่างให้เห็น สาเหตุที่พบบ่อย เหา (196) รังแค (201) โรคพยาธิเส้นด้าย (231) ริดสีดวงทวาร (58) ดีซ่าน (แผนภูมิที่ 11) ถ้าอาการไม่ชัดเจน ให้ยาแก้แพ้ (ย7) ถ้าไม่ดีขึ้นใน 1 สัปดาห์ ควรปรึกษาแพทย์',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes)
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'มีผื่น ตุ่ม หรือวงด่าง?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'คันศีรษะ?'],
                'B2_1'    => ['frame' => '2.1',   'type' => 'S', 'q' => 'พบไข่เหา หรือตัวเหา?'],
                'B2_2'    => ['frame' => '2.2',   'type' => 'S', 'q' => 'มีรังแค?'],
                'B2_2_1'  => ['frame' => '2.2.1', 'type' => 'S', 'q' => 'มีผื่นแดงที่หนังศีรษะ ใบหน้า หู หน้าอก สะดือ รักแร้ ขาหนีบ หัวหน่าว?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'คันก้น?'],
                'B3_1'    => ['frame' => '3.1',   'type' => 'S', 'q' => 'เป็นมากตอนกลางคืน? หรือ เห็นตัวพยาธิเส้นด้ายที่ปากทวารหนัก?'],
                'B3_2'    => ['frame' => '3.2',   'type' => 'S', 'q' => 'มีเลือดสดๆ ออกเวลาถ่ายอุจจาระ?'],
                'B3_3'    => ['frame' => '3.3',   'type' => 'S', 'q' => 'เพิ่งหายจากท้องเดิน?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'คันในช่องคลอด?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'ตาเหลืองตัวเหลือง?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => 'พบในผู้สูงอายุ? หรือ ผิวหนังแห้ง?'],
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
                'B1'     => [['see_diagram64_itchy_rash', null], [null, 'B2']],
                'B2'     => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'   => [['head_lice', null], [null, 'B2_2']],
                'B2_2'   => [[null, 'B2_2_1'], ['clean_hair_hygiene', null]],
                'B2_2_1' => [['seborrheic_dermatitis', null], ['dandruff', null]],
                'B3'     => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'   => [['pinworm_infection', null], [null, 'B3_2']],
                'B3_2'   => [['hemorrhoids_anal_fissure', null], [null, 'B3_3']],
                'B3_3'   => [['anal_itching_post_diarrhea', null], [null, 'B4']],
                'B4'     => [['see_diagram57_vaginal_itching', null], [null, 'B5']],
                'B5'     => [['see_diagram11_jaundice', null], [null, 'B6']],
                'B6'     => [['elderly_dry_skin', null], ['antihistamine_symptomatic_itching', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนด Diagnosis Rules
            $rules = [
                'see_diagram64_itchy_rash' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 64 ผื่น/ตุ่ม/วงด่าง/ร่วมกับมีอาการคัน กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00064'],
                ],
                'head_lice' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เหา (196)
• เบนซิลเบนโซเอต (ย25.4)
NOTE,
                    'refs'       => ['196'],
                    'diagrams'   => [],
                ],
                'clean_hair_hygiene' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• หมั่นสระผมและรักษาผมให้สะอาด
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'seborrheic_dermatitis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
ผิวหนังอักเสบชนิดเกล็ดรังแค
• สระผมด้วยแชมพูกำจัดรังแค
• ทาครีมหรือโลชั่นสเตียรอยด์/คีโตโคนาโซล
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือสงสัยเป็นโซริอาซิส (203.2)/เอดส์ (238)
NOTE,
                    'refs'       => ['203.2', '238'],
                    'diagrams'   => [],
                ],
                'dandruff' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
รังแค (201)
• สระผมด้วยแชมพูกำจัดรังแค
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือเป็นๆ หายๆ บ่อย อาจเป็นโซริอาซิส (203.2)/อื่น ๆ
NOTE,
                    'refs'       => ['201'],
                    'diagrams'   => [],
                ],
                'pinworm_infection' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคพยาธิเส้นด้าย (231)
• ยาถ่ายพยาธิ (ย6)
NOTE,
                    'refs'       => ['231'],
                    'diagrams'   => [],
                ],
                'hemorrhoids_anal_fissure' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ริดสีดวงทวาร (58)/แผลปริที่ปากทวารหนัก (58.1)
• ยาระบาย (ย16)
• ยาเหน็บทวาร
NOTE,
                    'refs'       => ['58', '58.1'],
                    'diagrams'   => [],
                ],
                'anal_itching_post_diarrhea' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
คันเนื่องจากการระคายเคือง หายได้เองใน 1-2 วัน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'see_diagram57_vaginal_itching' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 57 คันในช่องคลอด กรอบที่ 3.1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00057'],
                ],
                'see_diagram11_jaundice' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 11 ดีซ่าน กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00011'],
                ],
                'elderly_dry_skin' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ผิวหนังผู้สูงอายุ/ผิวหนังแห้ง
• ทาน้ำมันมะกอก หรือโลชั่นกันผิวแห้ง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'antihistamine_symptomatic_itching' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
• ยาแก้แพ้ (ย7)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือมีอาการกลัวลม หรือกลัวน้ำตามมา (ผู้ป่วยที่เป็นพิษสุนัขบ้าระยะแรก อาจมีอาการคันในบริเวณที่เคยถูกสุนัข/แมวกัดหรือข่วน)
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
                    'medical_reference' => 'แผนภูมิที่ 62',
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

        $this->command->info('สร้างแผนภูมิที่ 62 (คัน) สำเร็จ');
    }
}
