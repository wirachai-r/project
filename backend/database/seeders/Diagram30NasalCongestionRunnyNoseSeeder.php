<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram30NasalCongestionRunnyNoseSeeder extends Seeder
{
    private const DIAGRAM_ID = '00030';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '2.1', '2.2',
                '3', '3.1', '4', '5', '6'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 30 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 30
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'คัดจมูก (NASAL CONGESTION)/น้ำมูกไหล (RUNNY NOSE/RHINORRHEA)',
                'diagram_name_en' => 'Nasal Congestion / Runny Nose / Rhinorrhea',
                'description' => 'แนะนำ: คัดจมูก หรือมีน้ำมูกไหล สาเหตุที่พบบ่อย ไข้หวัด (1) หวัดภูมิแพ้ (25) ไซนัสอักเสบ (26) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 7',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 30
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => 'มีไข้?'],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => "น้ำมูกข้นเหลืองหรือเขียวเกิน 24 ชั่วโมง?\nหรือ หายใจมีกลิ่นเหม็น?"],
                'B2_1' => ['frame' => '2.1', 'type' => 'S', 'q' => "เป็นเรื้อรัง?\nหรือ ปวดและกดเจ็บตรงหัวคิ้วหรือใต้ตา?"],
                'B2_2' => ['frame' => '2.2', 'type' => 'S', 'q' => 'ในเด็กที่นำเมล็ดผลไม้ ยางลบ หรือของอื่นๆ ใส่เข้าจมูก?'],
                'B3'   => ['frame' => '3',   'type' => 'S', 'q' => 'มีน้ำมูกใส?'],
                'B3_1' => ['frame' => '3.1', 'type' => 'S', 'q' => "จาม คันจมูก หรือคันคอ?\nหรือมีประวัติโรคภูมิแพ้?"],
                'B4'   => ['frame' => '4',   'type' => 'S', 'q' => 'ตรวจพบก้อนเนื้อในรูจมูก?'],
                'B5'   => ['frame' => '5',   'type' => 'S', 'q' => 'ผนังกั้นจมูกคด?'],
                'B6'   => ['frame' => '6',   'type' => 'S', 'q' => 'จามบ่อย คัดจมูก หรือคันคอ?'],
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
                'B1'   => [['refer_diagram_2', null], [null, 'B2']],
                'B2'   => [[null, 'B2_1'], [null, 'B3']],
                'B2_1' => [['chronic_sinusitis', null], [null, 'B2_2']],
                'B2_2' => [['nasal_foreign_body', null], ['purulent_rhinitis', null]],
                'B3'   => [[null, 'B3_1'], [null, 'B4']],
                'B3_1' => [['allergic_rhinitis', null], ['common_cold', null]],
                'B4'   => [['nasal_polyp', null], [null, 'B5']],
                'B5'   => [['deviated_nasal_septum', null], [null, 'B6']],
                'B6'   => [['allergic_rhinitis', null], ['unclear_nasal_symptoms', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'refer_diagram_2' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 2 ไข้ร่วมกับมีน้ำมูกหรือไอ กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00002'],
                ],
                'chronic_sinusitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไซนัสอักเสบเรื้อรัง (26)
• ยาแก้ปวด (ย1)
• อะม็อกซีซิลลิน (ย4.2) หรือโคไตรม็อกซาโซล (ย4.7) หรืออีริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['26'],
                    'diagrams'   => [],
                ],
                'nasal_foreign_body' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สิ่งแปลกปลอมเข้าจมูก (31)
• นำสิ่งแปลกปลอมออก
• อะม็อกซีซิลลิน (ย4.2) หรือโคไตรม็อกซาโซล (ย4.7) หรืออีริโทรไมซิน (ย4.4)
⊕ ถ้าสิ่งแปลกปลอมเอายาก
NOTE,
                    'refs'       => ['31'],
                    'diagrams'   => [],
                ],
                'purulent_rhinitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เยื่อจมูกอักเสบเป็นหนอง (27)
• อะม็อกซีซิลลิน (ย4.2) หรือโคไตรม็อกซาโซล (ย4.7) หรืออีริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['27'],
                    'diagrams'   => [],
                ],
                'allergic_rhinitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หวัดภูมิแพ้ (25)
• ยาแก้แพ้ (ย7)
• หลีกเลี่ยงสิ่งที่แพ้
• ออกกำลังกายเป็นประจำ
NOTE,
                    'refs'       => ['25'],
                    'diagrams'   => [],
                ],
                'common_cold' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไข้หวัด (1)
• รักษาตามอาการ
NOTE,
                    'refs'       => ['1'],
                    'diagrams'   => [],
                ],
                'nasal_polyp' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 เดือน',
                    'note'       => <<<NOTE
ติ่งเนื้อเมือกจมูก (28)
⊕ ภายใน 1 เดือน
NOTE,
                    'refs'       => ['28'],
                    'diagrams'   => [],
                ],
                'deviated_nasal_septum' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 เดือน',
                    'note'       => <<<NOTE
ผนังกั้นจมูกคด (29)
⊕ ภายใน 1 เดือน
NOTE,
                    'refs'       => ['29'],
                    'diagrams'   => [],
                ],
                'unclear_nasal_symptoms' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้แพ้ (ย7)
• ยาแก้คัดจมูก (ย8)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือเป็นๆ หายๆ เรื้อรัง/มีก้อนแข็งขนาดโตกว่า 1 ซม. ที่ข้างคอ/หูอื้อ/เลือดกำเดาไหลบ่อย
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
                    'medical_reference' => 'แผนภูมิที่ 30',
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

        $this->command->info('สร้างแผนภูมิที่ 30 (คัดจมูก/น้ำมูกไหล - NASAL CONGESTION/RUNNY NOSE/RHINORRHEA) กรอบ 1-7 สำเร็จ');
    }
}
