<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram37HoarsenessSeeder extends Seeder
{
    private const DIAGRAM_ID = '00037';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '2', '3', '3.1', '4',
                '5', '5.1', '6', '7', '8'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 37 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 37
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'เสียงแหบ (HOARSENESS)',
                'diagram_name_en' => 'Hoarseness',
                'description' => 'เสียงแหบห้าวผิดไปจากปกติ หรือพูดไม่มีเสียง อาจมีไข้ร่วมด้วยหรือไม่ก็ได้ สาเหตุที่พบบ่อย กล่องเสียงอักเสบ (12) ไข้หวัด (1) ทอนซิลอักเสบ (8) หลอดลมอักเสบ (15) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 8',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 37
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => "หอบ? ไอเสียงก้อง? หรือ\nหายใจเข้ามีเสียงดังอี้ด (stridor)?"],
                'B1_1' => ['frame' => '1.1', 'type' => 'S', 'q' => "มีแผ่นเยื่อสีเทา/เหลือง\nปนเทาในลำคอ?"],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => "น้ำหนักลดฮวบ? หรือ มีก้อนแข็ง\nโตมากกว่า 1 ซม. ที่ข้างคอ?"],
                'B3'   => ['frame' => '3',   'type' => 'S', 'q' => "กลืนลำบาก? หนังตาตก?\nหรือ แขนขาเป็นอัมพาต?"],
                'B3_1' => ['frame' => '3.1', 'type' => 'S', 'q' => "เป็นหลังกินอาหารบรรจุ\nปี๊บ กระป๋อง ขวดแก้ว\nหรือภาชนะที่ปิดมิดชิด?"],
                'B4'   => ['frame' => '4',   'type' => 'S', 'q' => "มีอาการอย่างน้อย 2 อย่าง ดังต่อไปนี้\n- หนังตาบวม?\n- เส้นผมบางและหักง่าย?\n- ผิวหนังหยาบ แห้ง และเย็น?\n- ขี้หนาว?\n- อ้วนขึ้น?"],
                'B5'   => ['frame' => '5',   'type' => 'S', 'q' => 'เสียงแหบนานเกิน 3 สัปดาห์?'],
                'B5_1' => ['frame' => '5.1', 'type' => 'S', 'q' => "เสียงแหบเฉพาะช่วงหลัง\nตื่นนอน? และ มีอาการ\nจุกแน่นหรือแสบตรง\nลิ้นปี่หรือเรอเปรี้ยว\nเป็นๆหายๆ เรื้อรัง?"],
                'B6'   => ['frame' => '6',   'type' => 'S', 'q' => 'เป็นหวัด? เจ็บคอ? หรือ ไอ?'],
                'B7'   => ['frame' => '7',   'type' => 'S', 'q' => "เกิดหลังใช้เสียงมาก\n(เช่น ครู นักเทศน์ นักร้อง)?\nดื่มแอลกอฮอล์จัด?\nหรือ สูบบุหรี่จัด?"],
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
                'B1'   => [[null, 'B1_1'], [null, 'B2']],
                'B1_1' => [['diphtheria', null], ['croup', null]],
                'B2'   => [['laryngeal_cancer_suspect', null], [null, 'B3']],
                'B3'   => [[null, 'B3_1'], [null, 'B4']],
                'B3_1' => [['botulism', null], ['refer_diagram_19', null]],
                'B4'   => [['hypothyroidism', null], [null, 'B5']],
                'B5'   => [[null, 'B5_1'], [null, 'B6']],
                'B5_1' => [['gerd', null], ['chronic_hoarseness_severe', null]],
                'B6'   => [['infectious_laryngitis', null], [null, 'B7']],
                'B7'   => [['irritative_laryngitis', null], ['general_hoarseness_care', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'diphtheria' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
คอดิบ (10)
⊕ ด่วน
NOTE,
                    'refs'       => ['10'],
                    'diagrams'   => [],
                ],
                'croup' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ครู้ป (11)
• ถ้ามีไข้ให้พาราเซตามอล (ย1.2)
• ดื่มน้ำมาก ๆ • สูดไอน้ำอุ่น
⊕ ด่วน ถ้าหอบ/กลืนลำบาก/ซึม/กระสับกระส่าย/ขาดน้ำ/สงสัยสำลักสิ่งแปลกปลอม
NOTE,
                    'refs'       => ['11'],
                    'diagrams'   => [],
                ],
                'laryngeal_cancer_suspect' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งกล่องเสียง (237.7)
NOTE,
                    'refs'       => ['237.7'],
                    'diagrams'   => [],
                ],
                'botulism' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
โรคโบทูลิซึม (67.1)
⊕ ด่วน
NOTE,
                    'refs'       => ['67.1'],
                    'diagrams'   => [],
                ],
                'refer_diagram_19' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 19 อัมพาต/แขนขาไม่มีแรง/หนังตาตก กรอบที่ 2
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00019'],
                ],
                'hypothyroidism' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ภาวะขาดไทรอยด์/ต่อมไทรอยด์ทำงานน้อย (124)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['124'],
                    'diagrams'   => [],
                ],
                'gerd' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคกรดไหลย้อน (49.1)
• ยาต้านกรด (ย14.1)
• รานิทิดีน (ย14.3)
• หลีกเลี่ยงสิ่งกระตุ้น
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['49.1'],
                    'diagrams'   => [],
                ],
                'chronic_hoarseness_severe' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์
อาจเป็นติ่งเนื้อเมือกกล่องเสียง/สายเสียงเป็นอัมพาต (12)/มะเร็งกล่องเสียง (237.7)/วัณโรคกล่องเสียง
NOTE,
                    'refs'       => ['12', '237.7'],
                    'diagrams'   => [],
                ],
                'infectious_laryngitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กล่องเสียงอักเสบ (12) จากการติดเชื้อ
• ให้การรักษาโรคที่พบร่วม (ดู "โรคที่ 1, 2, 8, 13, 15, 16")
• ถ้ามีไข้ให้พาราเซตามอล (ย1.2)
• ดื่มน้ำอุ่นมาก ๆ หรือสูดดมไอน้ำอุ่น
• งดแอลกอฮอล์ บุหรี่
• พักการใช้เสียง
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือมีไข้เกิน 1 สัปดาห์/คัดจมูกเรื้อรัง/หูอื้อเรื้อรัง/เลือดกำเดาไหลหรือเสียงแหบเป็นๆหายๆ บ่อย
NOTE,
                    'refs'       => ['12'],
                    'diagrams'   => [],
                ],
                'irritative_laryngitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กล่องเสียงอักเสบ (12) จากการระคายเคือง
• ถ้ามีไข้ให้พาราเซตามอล (ย1.2)
• ดื่มน้ำอุ่นมาก ๆ หรือสูดดมไอน้ำอุ่น
• งดแอลกอฮอล์ บุหรี่
• พักการใช้เสียง
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือมีไข้เกิน 1 สัปดาห์/คัดจมูกเรื้อรัง/หูอื้อเรื้อรัง/เลือดกำเดาไหลหรือเสียงแหบเป็นๆหายๆ บ่อย
NOTE,
                    'refs'       => ['12'],
                    'diagrams'   => [],
                ],
                'general_hoarseness_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ถ้ามีไข้ให้พาราเซตามอล (ย1.2)
• ดื่มน้ำอุ่นมาก ๆ หรือสูดดมไอน้ำอุ่น
• งดแอลกอฮอล์ บุหรี่
• พักการใช้เสียง
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือมีไข้เกิน 1 สัปดาห์/คัดจมูกเรื้อรัง/หูอื้อเรื้อรัง/เลือดกำเดาไหลหรือเสียงแหบเป็นๆหายๆ บ่อย
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
                    'medical_reference' => 'แผนภูมิที่ 37',
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

        $this->command->info('สร้างแผนภูมิที่ 37 (เสียงแหบ - HOARSENESS) กรอบ 1-8 สำเร็จ');
    }
}
