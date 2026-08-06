<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram64RashWithItchingSeeder extends Seeder
{
    private const DIAGRAM_ID = '00064';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '5', '5.1', '5.2',
                '6', '7', '8', '9', '10', '11', '10.1', '10.2'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 64 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 64
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ผื่น ตุ่ม วงด่าง ร่วมกับมีอาการคัน',
                'diagram_name_en' => 'Rash, Bumps, Macules with Itching',
                'description' => 'ผิวหนังขึ้นเป็นผื่น ตุ่ม หรือวงด่าง ซึ่งมีอาการคัน สาเหตุที่พบบ่อย ลมพิษ (198) ผิวหนังอักเสบจากการสัมผัส (199) ผิวหนังอักเสบจากภูมิแพ้ (200) ยุง แมลงกัด ผด กลาก (190) หิด (195) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 12',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes)
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'มีไข้?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'เป็นวง ๆ หรือปื้นนูน สีแดงเรื่อ ๆ มีขอบชัดเจน ขึ้นเป็นพักๆ และกระจายทั่วไป?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'เป็นวง ๆ มีลักษณะเป็นขุย ๆ ขอบแดง ขึ้นเฉพาะแห่งเดียวหรือหลายแห่ง และวงลามใหญ่ออกไปเรื่อยๆ?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'ผื่นแดงคล้ายรอยถลอกมีขอบเขตชัดเจน พบที่รักแร้/ขาหนีบ/ใต้ราวนม/สะดือ/ซอกสะโพก/ง่ามนิ้ว?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'เป็นผื่นแดง หรือตุ่มน้ำเล็ก ๆ คันและเกา จนน้ำเหลืองเยิ้ม?'],
                'B5_1'    => ['frame' => '5.1',   'type' => 'S', 'q' => 'ขึ้นเหมือนกันทั้ง 2 ซีกของร่างกาย (เช่น ที่แก้ม ต้นคอ ตามข้อพับทั้ง 2 ข้าง)? หรือ มีประวัติโรคภูมิแพ้ในครอบครัว?'],
                'B5_2'    => ['frame' => '5.2',   'type' => 'S', 'q' => 'ขึ้นเฉพาะในบริเวณที่สัมผัสกับสิ่งระคายเคือง (เช่น สร้อยคอ กำไล แหวน ผงซักฟอก ปูน)? หรือ หลังถูกยุง/แมลงกัด?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => 'เป็นผื่นแดงและมีเกล็ดรังแคที่หนังศีรษะ ใบหน้า หู หน้าอก หลัง รักแร้ สะดือ ขาหนีบ หรือหัวหน่าว?'],
                'B7'      => ['frame' => '7',     'type' => 'S', 'q' => 'เป็นตุ่มใส ตามง่ามมือง่ามเท้า/หน้าท้อง/อวัยวะเพศ ขึ้นพร้อมกันทั้ง 2 ข้างของร่างกาย และคันมากตอนกลางคืน? หรือ ตุ่มคันและลามได้?'],
                'B8'      => ['frame' => '8',     'type' => 'S', 'q' => 'ตุ่มแดงคันขึ้นเฉพาะแห่ง?'],
                'B9'      => ['frame' => '9',     'type' => 'S', 'q' => 'ผด?'],
                'B10'     => ['frame' => '10',    'type' => 'S', 'q' => 'ผื่นแดง คันเล็กน้อย กระจายตามลำตัวด้านหน้าคล้ายรูป T ด้านหลังคล้ายต้นคริสต์มาส? หรือ พบผื่นขนาด 2-6 ซม. ดูคล้ายกลากขึ้น 1-2 แห่ง?'],
                'B11'     => ['frame' => '11',    'type' => 'S', 'q' => 'ผื่นคันที่ง่ามเท้า หรือบริเวณร่มผ้า?'],
                'B10_1'   => ['frame' => '10.1',  'type' => 'S', 'q' => 'ทาด้วยครีมสเตียรอยด์ได้ผล?'],
                'B10_2'   => ['frame' => '10.2',  'type' => 'S', 'q' => 'ผื่นลอกเป็นขุย ๆ และลามได้?'],
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
                'B1'     => [['see_diagram4_fever_rash', null], [null, 'B2']],
                'B2'     => [['urticaria', null], [null, 'B3']],
                'B3'     => [['tinea_corporis', null], [null, 'B4']],
                'B4'     => [['candidiasis', null], [null, 'B5']],
                'B5'     => [[null, 'B5_1'], [null, 'B6']],
                'B5_1'   => [['atopic_dermatitis', null], [null, 'B5_2']],
                'B5_2'   => [['contact_dermatitis', null], [null, 'B6']],
                'B6'     => [['seborrheic_dermatitis', null], [null, 'B7']],
                'B7'     => [['scabies', null], [null, 'B8']],
                'B8'     => [['insect_bites', null], [null, 'B9']],
                'B9'     => [['miliaria', null], [null, 'B10']],
                'B10'    => [['pityriasis_rosea', null], [null, 'B11']],
                'B11'    => [[null, 'B10_1'], ['unclear_rash_symptomatic_care_2w', null]],
                'B10_1'  => [['contact_dermatitis_steroid_responsive', null], [null, 'B10_2']],
                'B10_2'  => [['tinea_pedis_cruris', null], ['unclear_rash_symptomatic_care_2w', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนด Diagnosis Rules
            $rules = [
                'see_diagram4_fever_rash' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 4 ไข้ร่วมกับมีผื่นหรือตุ่มขึ้น กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00004'],
                ],
                'urticaria' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ลมพิษ (198)
• ยาแก้แพ้ (ย7)
• ทายาแก้ผดผื่นคัน (ย25.5)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือเป็นเรื้อรังเกิน 2 เดือน
NOTE,
                    'refs'       => ['198'],
                    'diagrams'   => [],
                ],
                'tinea_corporis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
กลาก (190)
• ทาขี้ผึ้งรักษากลากเกลื้อน (ย25.1) หรือครีมรักษาโรคเชื้อรา (ย25.2)
• ถ้าดีขึ้นทาต่ออีก 2-4 สัปดาห์
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['190'],
                    'diagrams'   => [],
                ],
                'candidiasis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
โรคเชื้อราแคนดิดา (191.1)
• ทาครีมรักษาโรคเชื้อรา (ย25.2)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ อาจเป็นโซริอาซิส (203.2)/อื่น ๆ
NOTE,
                    'refs'       => ['191.1', '203.2'],
                    'diagrams'   => [],
                ],
                'atopic_dermatitis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
ผิวหนังอักเสบจากภูมิแพ้ (200)
• ทาครีมสเตียรอยด์ (ย25.6)
• ถ้ามีน้ำเหลืองเยิ้มให้ เพนิซิลลินวี (ย4.1) หรืออีริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['200'],
                    'diagrams'   => [],
                ],
                'contact_dermatitis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
ผิวหนังอักเสบจากการสัมผัส (199)
• ทาครีมสเตียรอยด์ (ย25.6)
• ถ้ามีน้ำเหลืองเยิ้มให้ เพนิซิลลินวี (ย4.1) หรืออีริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['199'],
                    'diagrams'   => [],
                ],
                'seborrheic_dermatitis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
ผิวหนังอักเสบชนิดเกล็ดรังแค (201)
• สระผมด้วยแชมพูกำจัดรังแค
• ทาครีมหรือโลชั่นสเตียรอยด์/คีโตโคนาโซล
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือสงสัยเป็นโซริอาซิส (203.2) หรือเอดส์ (238)
NOTE,
                    'refs'       => ['201', '203.2', '238'],
                    'diagrams'   => [],
                ],
                'scabies' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
หิด (195)
• ทาเบนซิลเบนโซเอต (ย25.4)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['195'],
                    'diagrams'   => [],
                ],
                'insect_bites' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
ยุง แมลงกัด
• ทาครีมสเตียรอยด์ (ย25.6)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'miliaria' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
ผด
• ทายาแก้ผดผื่นคัน (ย25.5) หรือครีมสเตียรอยด์ (ย25.6)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'pityriasis_rosea' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 6 สัปดาห์',
                    'note'       => <<<NOTE
ผื่นพีอาร์ (203.1)
• ถ้าคันให้กินยาแก้แพ้ (ย7) และทาครีมสเตียรอยด์ (ย25.6)
⊕ ถ้าไม่ดีขึ้นใน 6 สัปดาห์ หรือลุกลามมากขึ้นหรือสงสัยโรคอื่น
NOTE,
                    'refs'       => ['203.1'],
                    'diagrams'   => [],
                ],
                'contact_dermatitis_steroid_responsive' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
ผิวหนังอักเสบจากการสัมผัส (199)
• ทาครีมสเตียรอยด์ (ย25.6)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['199'],
                    'diagrams'   => [],
                ],
                'tinea_pedis_cruris' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
กลาก (190)
• ทาขี้ผึ้งรักษากลากเกลื้อน (ย25.1) หรือครีมรักษาโรคเชื้อรา (ย25.2)
• ถ้าดีขึ้นทาต่ออีก 2-4 สัปดาห์
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['190'],
                    'diagrams'   => [],
                ],
                'unclear_rash_symptomatic_care_2w' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
• ทายาแก้ผดผื่นคัน (ย25.5) หรือครีมสเตียรอยด์ (ย25.6)
• ถ้าเป็นทั่วตัวให้ยาแก้แพ้ (ย7)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ อาจเป็นผื่นพีอาร์ (203.1)/อื่น ๆ
NOTE,
                    'refs'       => ['203.1'],
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
                    'medical_reference' => 'แผนภูมิที่ 64',
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

        $this->command->info('สร้างแผนภูมิที่ 64 (ผื่น ตุ่ม วงด่าง ร่วมกับมีอาการคัน) สำเร็จ');
    }
}
