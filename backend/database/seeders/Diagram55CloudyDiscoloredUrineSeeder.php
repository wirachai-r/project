<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram55CloudyDiscoloredUrineSeeder extends Seeder
{
    private const DIAGRAM_ID = '00055';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            // รายการกรอบทั้งหมดในแผนภูมิที่ 55
            $frameNumbers = [
                '1', '1.1', '1.1.1', '1.1.2', '1.1.3',
                '1.2', '1.3', '1.4', '1.5', '1.6', '1.7', '1.7.1', '1.8',
                '2', '2.1', '2.2', '3', '4', '5', '6'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 55 ถูก seed แล้ว กรุณารัน Rollback หรือลบข้อมูลเดิมก่อน'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 55
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ปัสสาวะขุ่น/มีสีผิดปกติ (CLOUDY/DISCOLORED URINE)',
                'diagram_name_en' => 'Cloudy/Discolored Urine',
                'description' => 'มีอาการปัสสาวะขุ่น ไม่ใสเหมือนปกติ หรือมีสีผิดปกติ เช่น สีเหลืองเข้ม สีเขียว สีฟ้า สีส้ม สีแดง สีดำ หรือสีคล้ายเลือด อาจมีไข้ร่วมด้วยหรือไม่ก็ได้ สาเหตุที่พบบ่อย กระเพาะปัสสาวะอักเสบ (141) นิ่วกระเพาะปัสสาวะ (140) นิ่วไต (138) กรวยไตอักเสบ (137) หน่วยไตอักเสบ (136) สาเหตุจากสีในยาหรืออาหาร ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 6',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 55
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'ปัสสาวะแดง? ปัสสาวะดำ? หรือ สีคล้ายเลือด?'],
                'B1_1'    => ['frame' => '1.1',   'type' => 'S', 'q' => 'มีไข้?'],
                'B1_1_1'  => ['frame' => '1.1.1', 'type' => 'S', 'q' => 'หนาวสั่นมาก? และเคาะเจ็บที่สีข้าง?'],
                'B1_1_2'  => ['frame' => '1.1.2', 'type' => 'S', 'q' => 'บวมทั้งตัว? ความดันโลหิตสูง? หรือ มีประวัติเจ็บคอเมื่อ 1-4 สัปดาห์ก่อน?'],
                'B1_1_3'  => ['frame' => '1.1.3', 'type' => 'S', 'q' => 'ซีด? หรือ มีจุดแดงจ้ำเขียวตามตัว?'],
                'B1_2'    => ['frame' => '1.2',   'type' => 'S', 'q' => 'พบหลังได้รับบาดเจ็บ?'],
                'B1_3'    => ['frame' => '1.3',   'type' => 'S', 'q' => 'หลังถูกงูกัด?'],
                'B1_4'    => ['frame' => '1.4',   'type' => 'S', 'q' => 'มีก้อนแข็งในท้อง? หรือ น้ำหนักลดฮวบ?'],
                'B1_5'    => ['frame' => '1.5',   'type' => 'S', 'q' => 'ปวดที่สีข้าง? หรือ ถ่ายเป็นเม็ดทราย?'],
                'B1_6'    => ['frame' => '1.6',   'type' => 'S', 'q' => 'หลังลงแช่น้ำในห้วย หนอง คลอง บึง?'],
                'B1_7'    => ['frame' => '1.7',   'type' => 'S', 'q' => "ปวดแสบ ปวดร้อนเวลาปัสสาวะ?\nปัสสาวะสะดุดออกเป็นหยด? หรือ ถ่ายกะปริบกะปรอย?"],
                'B1_7_1'  => ['frame' => '1.7.1', 'type' => 'S', 'q' => 'เคยมีประวัติเป็นกระเพาะปัสสาวะอักเสบมาก่อน?'],
                'B1_8'    => ['frame' => '1.8',   'type' => 'S', 'q' => 'พบหลังกินยา?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'มีไข้?'],
                'B2_1'    => ['frame' => '2.1',   'type' => 'S', 'q' => 'ปัสสาวะขุ่น? และเคาะเจ็บที่สีข้าง?'],
                'B2_2'    => ['frame' => '2.2',   'type' => 'S', 'q' => 'ปัสสาวะมีสีน้ำปลา และ หน้าซีดเหลือง?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'ปัสสาวะมีสีเหลืองเข้มเหมือนขมิ้นทุกครั้งที่ถ่าย และตาเหลือง ตัวเหลือง?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'ปัสสาวะมีสีเขียว สีฟ้า หรือสีส้ม?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'ปัสสาวะมีสีเข้มคล้ายน้ำชาเป็นบางครั้ง?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => 'คำแนะนำและการดูแลรักษาตามอาการ'],
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
                'B1'     => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'   => [[null, 'B1_1_1'], [null, 'B1_2']],
                'B1_1_1' => [['acute_pyelonephritis_prostatitis_137_143_1', null], [null, 'B1_1_2']],
                'B1_1_2' => [['acute_glomerulonephritis_136', null], [null, 'B1_1_3']],
                'B1_1_3' => [['anemia_aplastic_itp_leukemia_leptospirosis_103_104_106_227', null], ['urine_discoloration_fever_investigate_24h', null]],
                'B1_2'   => [['kidney_bladder_urethra_rupture_56', null], [null, 'B1_3']],
                'B1_3'   => [['snake_bite_poison_221', null], [null, 'B1_4']],
                'B1_4'   => [['kidney_bladder_cancer_237_18_237_16', null], [null, 'B1_5']],
                'B1_5'   => [['kidney_stone_138', null], [null, 'B1_6']],
                'B1_6'   => [['leech_infestation_223', null], [null, 'B1_7']],
                'B1_7'   => [[null, 'B1_7_1'], [null, 'B1_8']],
                'B1_7_1' => [['cystitis_recurrent_141', null], ['bladder_stone_cystitis_prostate_140_141_143_143_1', null]],
                'B1_8'   => [['drug_induced_urine_color_change', null], ['kidney_prostate_bladder_cancer_investigate_1week', null]],
                'B2'     => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'   => [['acute_pyelonephritis_137', null], [null, 'B2_2']],
                'B2_2'   => [['hemolytic_anemia_101', null], [null, 'B3']],
                'B3'     => [
                    [null, null], // ไปแผนภูมิที่ 11 ดีซ่าน
                    [null, 'B4']
                ],
                'B4'     => [['drug_food_color_urine', null], [null, 'B5']],
                'B5'     => [['dehydration_urine_tea_colored', null], [null, 'B6']],
                'B6'     => [['symptomatic_treatment_cloudy_urine', null], ['symptomatic_treatment_cloudy_urine', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                if ($boxKey === 'B3' && $yes[1] === null) {
                    // กรณีตอบ ใช่ ให้ไปที่แผนภูมิที่ 11 (ดีซ่าน)
                    $choiceId = str_pad((string) $choiceNumber++, 10, '0', STR_PAD_LEFT);
                    $jaundiceDiagramId = DB::table('diagrams')->where('diagram_id', '00011')->value('diagram_id');
                    DB::table('answer_choices')->insert([
                        'choice_id' => $choiceId,
                        'choice_text' => 'ใช่',
                        'order' => 1,
                        'status' => '1',
                        'box_id' => $boxes['B3']['id'],
                        'next_box_id' => null,
                        'next_diagram_id' => $jaundiceDiagramId,
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
                'acute_pyelonephritis_prostatitis_137_143_1' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจเป็นกรวยไตอักเสบเฉียบพลัน (137)/ต่อมลูกหมากอักเสบ (143.1)
NOTE,
                    'refs'       => ['137', '143.1'],
                    'diagrams'   => [],
                ],
                'acute_glomerulonephritis_136' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
หน่วยไตอักเสบเฉียบพลัน (136)
⊕ ภายใน 3 วัน
NOTE,
                    'refs'       => ['136'],
                    'diagrams'   => [],
                ],
                'anemia_aplastic_itp_leukemia_leptospirosis_103_104_106_227' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจเป็นโลหิตจางจากไขกระดูกฝ่อ (103)/ไอทีพี (104)/มะเร็งเม็ดเลือดขาว (106)/เล็ปโตสไปโรซิส (227)
NOTE,
                    'refs'       => ['103', '104', '106', '227'],
                    'diagrams'   => [],
                ],
                'urine_discoloration_fever_investigate_24h' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'kidney_bladder_urethra_rupture_56' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นไตฉีกขาด (56)/กระเพาะปัสสาวะหรือท่อปัสสาวะฉีกขาด
• ให้น้ำเกลือถ้าช็อก
NOTE,
                    'refs'       => ['56'],
                    'diagrams'   => [],
                ],
                'snake_bite_poison_221' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน พิษงูแมวเซา/งูกะปะ/งูเขียวหางไหม้ (221)
• ให้เซรุ่มแก้พิษงู
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'kidney_bladder_cancer_237_18_237_16' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งไต (237.18)/มะเร็งกระเพาะปัสสาวะ (237.16)
NOTE,
                    'refs'       => ['237.18', '237.16'],
                    'diagrams'   => [],
                ],
                'kidney_stone_138' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นนิ่วไต (138)
NOTE,
                    'refs'       => ['138'],
                    'diagrams'   => [],
                ],
                'leech_infestation_223' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ปลิงเข้า (223)
⊕ ถ้าเลือดไม่หยุดใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['223'],
                    'diagrams'   => [],
                ],
                'cystitis_recurrent_141' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กระเพาะปัสสาวะอักเสบ (141)
• โคไตรม็อกซาโซล (ยา4.7) หรืออะม็อกซีซิลลิน (ยา4.2) หรือไซโพรฟล็อกซาซิน (ยา4.11.2)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือน้ำหนักลด/เป็นๆ หายๆ บ่อย/พบในผู้ชาย
NOTE,
                    'refs'       => ['141'],
                    'diagrams'   => [],
                ],
                'bladder_stone_cystitis_prostate_140_141_143_143_1' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน อาจเป็นนิ่วกระเพาะปัสสาวะ (140)/กระเพาะปัสสาวะอักเสบ (141)/ต่อมลูกหมากโต (143)/ต่อมลูกหมากอักเสบเรื้อรัง (143.1)
NOTE,
                    'refs'       => ['140', '141', '143', '143.1'],
                    'diagrams'   => [],
                ],
                'drug_induced_urine_color_change' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากยา
• หยุดยาที่กิน
• แนะนำให้กลับไปหาแพทย์ที่รักษาอยู่เดิม
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'kidney_prostate_bladder_cancer_investigate_1week' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งไต/ต่อมลูกหมาก/กระเพาะปัสสาวะ (237) หรืออื่น ๆ
NOTE,
                    'refs'       => ['237'],
                    'diagrams'   => [],
                ],
                'acute_pyelonephritis_137' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กรวยไตอักเสบเฉียบพลัน (137)
• ชันสูตรเพิ่มเติม
• ยาลดไข้ (ยา1)
• โคไตรม็อกซาโซล (ยา4.7) หรืออะม็อกซีซิลลิน (ยา4.2) หรือไซโพรฟล็อกซาซิน (ยา4.11.2)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือซีด/ดีซ่าน/มีจุดแดงจ้ำเขียว
NOTE,
                    'refs'       => ['137'],
                    'diagrams'   => [],
                ],
                'hemolytic_anemia_101' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจเป็นโลหิตจางจากเม็ดเลือดแดงแตก (101)
NOTE,
                    'refs'       => ['101'],
                    'diagrams'   => [],
                ],
                'drug_food_color_urine' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เกิดจากสีที่ผสมในยาหรืออาหาร
• งดยาหรืออาหารที่กิน
• ดื่มน้ำมากๆ
• แนะนำให้กลับไปหาแพทย์ที่รักษาอยู่เดิม
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'dehydration_urine_tea_colored' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากร่างกายขาดน้ำ เช่น เป็นไข้ อากาศร้อน เหงื่อออก ดื่มน้ำน้อย ท้องเดิน หรืออาเจียน
• ดื่มน้ำมากๆ
• ถ้าท้องเดินหรืออาเจียน ดูแผนภูมิตามอาการที่พบ
⊕ ถ้ามีสีเข้มทุกครั้งที่ถ่ายติดต่อกันนานเกิน 3 วัน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'symptomatic_treatment_cloudy_urine' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ
• ยาลดไข้ (ยา1) ถ้ามีไข้
• ดื่มน้ำมากๆ
⊕ ถ้าปัสสาวะขุ่นทุกครั้งที่ถ่าย หรือมีไข้เกิน 7 วัน/หนาวสั่นมาก/ซีด/มีจุดแดงจ้ำเขียว
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
                    'medical_reference' => 'แผนภูมิที่ 55',
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

        $this->command->info('สร้างแผนภูมิที่ 55 (ปัสสาวะขุ่น/มีสีผิดปกติ) กรอบ 1-6 สำเร็จ');
    }
}
