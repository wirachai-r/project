<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram50RectalBleedingMelenaSeeder extends Seeder
{
    private const DIAGRAM_ID = '00050';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            // รายการกรอบทั้งหมดในแผนภูมิที่ 50
            $frameNumbers = ['1', '2', '3', '4', '5', '5.1', '5.1.1', '5.2', '6', '6.1', '6.2', '6.3'];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 50 ถูก seed แล้ว กรุณารัน Rollback หรือลบข้อมูลเดิมก่อน'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 50
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ถ่ายเป็นเลือด (RECTAL BLEEDING) / ถ่ายดำ (MELENA)',
                'diagram_name_en' => 'Rectal Bleeding / Melena',
                'description' => 'ถ่ายมีเลือดแดงสด หรือเลือดดำๆ ออกทางทวารหนัก สาเหตุที่พบบ่อย ริดสีดวงทวาร (58) แผลปริที่ปากทวารหนัก (58.1) แผลเพปติก (51) กระเพาะอาหารอักเสบ (50) ถ้าอาการไม่ชัดเจน ควรปรึกษาแพทย์ภายใน 1 สัปดาห์',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 50
            $boxes = [
                'B1'     => ['frame' => '1',     'type' => 'S', 'q' => 'เหงื่อออก หน้าซีด เป็นลม? หรือ ความดันต่ำ และชีพจรเบาเร็ว?'],
                'B2'     => ['frame' => '2',     'type' => 'S', 'q' => "มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้\n- อาเจียน?\n- น้ำหนักลด?\n- ท้องผูกสลับกับท้องเดินเรื้อรัง?\n- คลำได้ก้อนแข็งขรุขระในช่องท้อง?"],
                'B3'     => ['frame' => '3',     'type' => 'S', 'q' => 'มีจุดแดง/จ้ำเขียวขึ้นตามผิวหนัง? หรือ มีเลือดออกที่อื่น?'],
                'B4'     => ['frame' => '4',     'type' => 'S', 'q' => 'ถ่ายเป็นมูกปนเลือด?'],
                'B5'     => ['frame' => '5',     'type' => 'S', 'q' => 'ถ่ายเป็นเลือดสด?'],
                'B5_1'   => ['frame' => '5.1',   'type' => 'S', 'q' => 'เป็นสายติดอุจจาระ หรือเปื้อนกระดาษชำระ? หรือ ออกเป็นหยดในโถส้วม เฉพาะเวลาเบ่งถ่ายอุจจาระ?'],
                'B5_1_1' => ['frame' => '5.1.1', 'type' => 'S', 'q' => 'เจ็บปวดเวลาถ่าย? หรือ พบรอยปริที่ปากทวารหนัก?'],
                'B5_2'   => ['frame' => '5.2',   'type' => 'S', 'q' => 'เกิดหลังลงแช่น้ำ ในห้วยหนองคลองบึง?'],
                'B6'     => ['frame' => '6',     'type' => 'S', 'q' => 'ถ่ายดำ?'],
                'B6_1'   => ['frame' => '6.1',   'type' => 'S', 'q' => 'ปวดแสบใต้ลิ้นปี่ เวลาหิวหรือหลังกินอิ่ม? หรือมีประวัติกินยาสุด/แอสไพริน/ยากแก้ปวดข้อ/ดื่มแอลกอฮอล์เป็นประจำ?'],
                'B6_2'   => ['frame' => '6.2',   'type' => 'S', 'q' => 'หลังกินยาบำรุงโลหิต ตับ หรือเลือดหมู?'],
                'B6_3'   => ['frame' => '6.3',   'type' => 'S', 'q' => 'กลืนเลือดกำเดา หรือเลือดที่ออกหลังถอนฟัน?'],
            ];

            // รันสร้าง box_id ต่อเนื่อง
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

            // Entry Point ของแผนภูมิ
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'entry_box_id' => $boxes['B1']['id'],
            ]);

            // 3. ตัวเลือกตอบ (Choices) และเส้นทางเชื่อมต่อ
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

            // โครงสร้าง Decision Tree
            $binary = [
                'B1'     => [['severe_gastrointestinal_bleeding', null], [null, 'B2']],
                'B2'     => [['gastric_cancer_colon_cancer_237_11_237_13', null], [null, 'B3']],
                'B3'     => [['purpura_bleeding_disorder_103_104_106_225_227_228', null], [null, 'B4']],
                'B4'     => [
                    [null, null], // ไปแผนภูมิที่ 49 บิด กรอบที่ 1 (จัดการผ่าน diagram redirection ด้านล่างได้)
                    [null, 'B5']
                ],
                'B5'     => [[null, 'B5_1'], [null, 'B6']],
                'B5_1'   => [[null, 'B5_1_1'], [null, 'B5_2']],
                'B5_1_1' => [['anal_fissure_58_1', null], ['hemorrhoids_58', null]],
                'B5_2'   => [['leech_infestation_223', null], ['rectal_bleeding_investigation_1week', null]],
                'B6'     => [[null, 'B6_1'], [null, 'B6']], // สมมติเชื่อมไปตรวจดูอาการเพิ่มเติมถ้าไม่ใช่
                'B6_1'   => [['peptic_ulcer_gastritis_stomach_cancer_51_50_237_11', null], [null, 'B6_2']],
                'B6_2'   => [['food_drug_pigment_stool_black', null], [null, 'B6_3']],
                'B6_3'   => [['swallowed_blood_no_action', null], ['melena_investigation_1week', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                if ($boxKey === 'B4' && $yes[1] === null) {
                    // กรณีกระโดดข้ามไปแผนภูมิที่ 49
                    $choiceId = str_pad((string) $choiceNumber++, 10, '0', STR_PAD_LEFT);
                    $dysenteryDiagramId = DB::table('diagrams')->where('diagram_id', '00049')->value('diagram_id');
                    DB::table('answer_choices')->insert([
                        'choice_id' => $choiceId,
                        'choice_text' => 'ใช่',
                        'order' => 1,
                        'status' => '1',
                        'box_id' => $boxes['B4']['id'],
                        'next_box_id' => null,
                        'next_diagram_id' => $dysenteryDiagramId,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]);
                } else {
                    $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                }
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กฎการวินิจฉัย การรักษา และคำแนะนำ
            $rules = [
                'severe_gastrointestinal_bleeding' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะเลือดออกรุนแรงในทางเดินอาหาร
⊕ ด่วน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'gastric_cancer_colon_cancer_237_11_237_13' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งกระเพาะอาหาร (237.11)/ มะเร็งลำไส้ใหญ่ (237.13)
NOTE,
                    'refs'       => ['237.11', '237.13'],
                    'diagrams'   => [],
                ],
                'purpura_bleeding_disorder_103_104_106_225_227_228' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน (ดู "โรคที่ 103, 104, 106, 225, 227, 228")
NOTE,
                    'refs'       => ['103', '104', '106', '225', '227', '228'],
                    'diagrams'   => [],
                ],
                'anal_fissure_58_1' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
แผลปริที่ปากทวารหนัก (58.1)
• ให้ยาระบายอีแอฟพี (ยา16.5)
• ป้ายขี้ผึ้งเตตรา-ไซคลีน
NOTE,
                    'refs'       => ['58.1'],
                    'diagrams'   => [],
                ],
                'hemorrhoids_58' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ริดสีดวงทวาร (58)
• ยาเหน็บทวาร
• ยาระบาย (ยา16)
• ระวังอย่าให้ท้องผูก
⊕ ถ้า 1. ไม่หายใน 1 สัปดาห์หรือซีด 2. มีอาการของโรคตับแข็ง (44) 3. เป็นๆ หายๆ บ่อย 4. พบในคนอายุมากกว่า 40 ปี
NOTE,
                    'refs'       => ['58', '44'],
                    'diagrams'   => [],
                ],
                'leech_infestation_223' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ปลิงเข้าอวัยวะ (223)
⊕ ถ้าเลือดไม่หยุดภายใน 24 ชั่วโมง หรือซีด
NOTE,
                    'refs'       => ['223'],
                    'diagrams'   => [],
                ],
                'rectal_bleeding_investigation_1week' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ ถ้ารั่วออกมากหรือซีด หรือมีไข้เกิน 7 วัน ควรไปพบแพทย์ทันที
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'peptic_ulcer_gastritis_stomach_cancer_51_50_237_11' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง เลือดออกในกระเพาะอาหาร อาจมีสาเหตุจากแผลเพปติก (51)/กระเพาะอาหารอักเสบ (50)/มะเร็งกระเพาะอาหาร (237.11)
NOTE,
                    'refs'       => ['51', '50', '237.11'],
                    'diagrams'   => [],
                ],
                'food_drug_pigment_stool_black' => [
                    'urgency'    => 'G',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
สิ่งเหล่านี้ทำให้อุจจาระมีสีดำได้ ถ้าหยุดกินแล้วอุจจาระกลับมีสีปกติภายใน 3 วัน ก็ไม่ต้องทำอะไร
⊕ ถ้ายังมีอาการถ่ายดำต่อไป
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'swallowed_blood_no_action' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
• ไม่ต้องทำอะไร
⊕ ถ้ามีอาการนานเกิน 1 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'melena_investigation_1week' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ ถ้าซีด หรือลุกนั่งจะเป็นลม ควรไปพบแพทย์ทันที
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
            ];

            // 5. บันทึก Diagnosis Rules และ Relations
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
                    'medical_reference' => 'แผนภูมิที่ 50',
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

                // Rule Conditions
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

                // Rule Diseases (เชื่อมรหัสโรค)
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

                // Rule Next Diagrams (เชื่อมแผนภูมิถัดไป)
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

        $this->command->info('สร้างแผนภูมิที่ 50 (ถ่ายเป็นเลือด/ถ่ายดำ) กรอบ 1-6.3 สำเร็จ');
    }
}
