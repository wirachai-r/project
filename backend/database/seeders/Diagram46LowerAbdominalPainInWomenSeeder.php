<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram46LowerAbdominalPainInWomenSeeder extends Seeder
{
    private const DIAGRAM_ID = '00046';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '2.1', '2.2', '2.3', '2.3.1', '2.3.2', '2.3.3',
                '3', '4', '4.1', '4.2',
                '5', '5.1', '5.2', '5.3', '5.3.1', '6'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 46 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 46
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ปวดท้องน้อยในผู้หญิงวัยเจริญพันธุ์',
                'diagram_name_en' => 'Lower Abdominal Pain in Women of Reproductive Age',
                'description' => 'อาการปวดเจ็บ ปวดหน่วง หรือปวดบิดเป็นพัก ๆ ที่บริเวณท้องน้อย (ระดับใต้สะดือลงมาถึงหัวหน่าว) ในผู้หญิงวัยเจริญพันธุ์ตั้งแต่อายุประมาณ 12 ปี (เริ่มมีประจำเดือน) ถึง 50 ปี (วัยหมดประจำเดือน) สาเหตุที่พบบ่อย ปวดประจำเดือน (150) กระเพาะปัสสาวะอักเสบ (141) ไส้ติ่งอักเสบ (46) ปีกมดลูกอักเสบ (147) ครรภ์นอกมดลูก (157) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 6',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 46
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'ปวดที่บริเวณใต้สะดือลงมา?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => "มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้\n☐ ปวดรุนแรง?\n☐ หน้าท้องเกร็งแข็ง?\n☐ ปวดติดต่อกันนานเกิน 6 ชั่วโมง?\n☐ เหงื่อออก หน้าซีด ตัวเย็น?\n☐ ความดันต่ำและชีพจรเบาเร็ว?"],
                'B2_1'    => ['frame' => '2.1',   'type' => 'S', 'q' => 'กดเจ็บตรงท้องน้อยข้างขวา?'],
                'B2_2'    => ['frame' => '2.2',   'type' => 'S', 'q' => 'กดเจ็บตรงท้องน้อยข้างซ้าย หรือทั้ง 2 ข้าง? และ มีไข้สูง?'],
                'B2_3'    => ['frame' => '2.3',   'type' => 'S', 'q' => 'ประจำเดือนขาด?'],
                'B2_3_1'  => ['frame' => '2.3.1', 'type' => 'S', 'q' => 'ตกเลือดทางช่องคลอด และมีเศษเนื้อ หรือเศษรกออกมา?'],
                'B2_3_2'  => ['frame' => '2.3.2', 'type' => 'S', 'q' => "ประจำเดือนขาดไม่เกิน 3 เดือน? และ ลุกนั่งจะเป็นลม?"],
                'B2_3_3'  => ['frame' => '2.3.3', 'type' => 'S', 'q' => "อายุครรภ์มาก กว่า 6 เดือน? และ มดลูกเกร็งแข็ง?"],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => "ขัดเบา? ท้องเดิน? ตกขาว? หรือ เลือดออกทางช่องคลอด?"],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'มีไข้?'],
                'B4_1'    => ['frame' => '4.1',   'type' => 'S', 'q' => "หนาวสั่น? ปัสสาวะขุ่น? และ เคาะเจ็บที่สีข้าง?"],
                'B4_2'    => ['frame' => '4.2',   'type' => 'S', 'q' => 'กดเจ็บตรงท้องน้อยข้างขวา?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'ปวดบิดเป็นพัก ๆ?'],
                'B5_1'    => ['frame' => '5.1',   'type' => 'S', 'q' => "ครรภ์แก่และมีลักษณะแบบปวดท้องเจ็บคลอด?"],
                'B5_2'    => ['frame' => '5.2',   'type' => 'S', 'q' => "ปวดตรงท้องน้อยหรือสีข้าง และ ร้าวไปที่ช่องคลอดข้างเดียวกัน?"],
                'B5_3'    => ['frame' => '5.3',   'type' => 'S', 'q' => 'ปวดเวลามีประจำเดือน?'],
                'B5_3_1'  => ['frame' => '5.3.1', 'type' => 'S', 'q' => "ปวดรุนแรง? มีประจำเดือนออกมาก หรือกะปริบกะปรอย? หรือ มีบุตรยาก?"],
                'B6'      => ['frame' => '6',     'type' => 'T', 'q' => "• ยาแก้ปวด (ย1) หรือ แอนติสปาสโมดิก (ย20) ถ้าปวดบิดเป็นพักๆ\n⊕ ถ้าไม่ดีขึ้นใน 3 วัน/ปวดรุนแรง/กดเจ็บ"],
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

            // แผนที่การตัดสินใจ (Decision Tree Flow)
            $binary = [
                'B1'      => [[null, 'B2'], ['refer_diagram_43_frame_2', null]],
                'B2'      => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'    => [['severe_appendicitis_salpingitis', null], [null, 'B2_2']],
                'B2_2'    => [['severe_salpingitis', null], [null, 'B2_3']],
                'B2_3'    => [[null, 'B2_3_1'], ['severe_ovarian_cyst_torsion_other', null]],
                'B2_3_1'  => [['abortion', null], [null, 'B2_3_2']],
                'B2_3_2'  => [['ectopic_pregnancy', null], [null, 'B2_3_3']],
                'B2_3_3'  => [['placental_abruption', null], ['other_severe_obstetric_causes', null]],
                'B3'      => [['refer_diagram_54_47_57_58', null], [null, 'B4']],
                'B4'      => [[null, 'B4_1'], [null, 'B5']],
                'B4_1'    => [['acute_pyelonephritis', null], [null, 'B4_2']],
                'B4_2'    => [['appendicitis_salpingitis_moderate', null], ['symptomatic_treatment_fever', null]],
                'B5'      => [[null, 'B5_1'], [null, 'B6']],
                'B5_1'    => [['labor_pain', null], [null, 'B5_2']],
                'B5_2'    => [['ureteral_stone', null], [null, 'B5_3']],
                'B5_3'    => [[null, 'B5_3_1'], [null, 'B6']],
                'B5_3_1'  => [['uterine_myoma_endometriosis', null], ['dysmenorrhea_primary', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // กรอบที่ 6 (Terminal Text Box)
            $addChoice('B6', 'การดูแลรักษาตามกรอบที่ 6', null, 'unspecified_lower_abdominal_pain_care', 1);

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'refer_diagram_43_frame_2' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 43 ปวดท้อง กรอบที่ 2
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00043'],
                ],
                'severe_appendicitis_salpingitis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นไส้ติ่งอักเสบ (46)/ปีกมดลูกอักเสบ (147) ระยะรุนแรง
NOTE,
                    'refs'       => ['46', '147'],
                    'diagrams'   => [],
                ],
                'severe_salpingitis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นปีกมดลูกอักเสบ (147) ระยะรุนแรง
NOTE,
                    'refs'       => ['147'],
                    'diagrams'   => [],
                ],
                'abortion' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
แท้งบุตร (156)
⊕ ด่วน
• ให้น้ำเกลือถ้ามีอาการช็อก
NOTE,
                    'refs'       => ['156'],
                    'diagrams'   => [],
                ],
                'ectopic_pregnancy' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ครรภ์นอกมดลูก (157)
⊕ ด่วน
• ให้น้ำเกลือถ้ามีอาการช็อก
NOTE,
                    'refs'       => ['157'],
                    'diagrams'   => [],
                ],
                'placental_abruption' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
รกลอกตัวก่อนกำหนด (160)
NOTE,
                    'refs'       => ['160'],
                    'diagrams'   => [],
                ],
                'severe_ovarian_cyst_torsion_other' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุร้ายแรง เช่น เนื้องอกรังไข่/ถุงน้ำรังไข่ (153.1)/มะเร็งรังไข่ (237.5) ที่มีภาวะบิดขั้ว/อื่น ๆ
NOTE,
                    'refs'       => ['153.1', '237.5'],
                    'diagrams'   => [],
                ],
                'other_severe_obstetric_causes' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุร้ายแรงอื่น ๆ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'refer_diagram_54_47_57_58' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 54 ขัดเบา กรอบที่ 1.1
ดูแผนภูมิที่ 47 ท้องเดิน กรอบที่ 1
ดูแผนภูมิที่ 57 ตกขาว กรอบที่ 1
ดูแผนภูมิที่ 58 เลือดออกทางช่องคลอด กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00054', '00047', '00057', '00058'],
                ],
                'acute_pyelonephritis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กรวยไตอักเสบเฉียบพลัน (137)
• ชันสูตรเพิ่มเติม
• ยาลดไข้ (ย1)
• โคไตรม็อกซาโซล (ย4.7) หรืออะม็อกซีซิลลิน (ย4.2) หรือไซโพรฟล็อกซาซิน (ย4.11.2)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือซึม/ดีซ่าน/มีจุดแดงจ้ำเขียว
NOTE,
                    'refs'       => ['137'],
                    'diagrams'   => [],
                ],
                'appendicitis_salpingitis_moderate' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ไส้ติ่งอักเสบ (46)/ปีกมดลูกอักเสบ (147)
⊕ ด่วน
NOTE,
                    'refs'       => ['46', '147'],
                    'diagrams'   => [],
                ],
                'symptomatic_treatment_fever' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ
⊕ ถ้ามีไข้เกิน 4 วัน/หนาวสั่นมาก/กดเจ็บ/ปวดท้องติดต่อกันเกิน 6 ชั่วโมง/ตกขาวเป็นหนอง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'labor_pain' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ปวดท้องคลอด
⊕ ด่วน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'ureteral_stone' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
นิ่วท่อไต (139)
• แอนติสปาสโมดิก (ย20)
⊕ ถ้าไม่ดีขึ้นใน 6 ชั่วโมง หรือกำเริบซ้ำอีก
NOTE,
                    'refs'       => ['139'],
                    'diagrams'   => [],
                ],
                'uterine_myoma_endometriosis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1-2 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1-2 สัปดาห์ อาจเป็นเนื้องอกมดลูก (152.1)/เยื่อบุ มดลูกต่างที่ (153)
NOTE,
                    'refs'       => ['152.1', '153'],
                    'diagrams'   => [],
                ],
                'dysmenorrhea_primary' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปวดประจำเดือน (150)
• ยาแก้ปวด (ย1) หรือแอนติสปาสโมดิก (ย20)
⊕ ถ้าปวดรุนแรงขึ้นกว่าเดิม/เริ่มปวดครั้งแรกเมื่ออายุมากกว่า 25 ปี
NOTE,
                    'refs'       => ['150'],
                    'diagrams'   => [],
                ],
                'unspecified_lower_abdominal_pain_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ปวด (ย1) หรือ แอนติสปาสโมดิก (ย20) ถ้าปวดบิดเป็นพักๆ
⊕ ถ้าไม่ดีขึ้นใน 3 วัน/ปวดรุนแรง/กดเจ็บ
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
                    'medical_reference' => 'แผนภูมิที่ 46',
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

        $this->command->info('สร้างแผนภูมิที่ 46 (ปวดท้องน้อยในผู้หญิงวัยเจริญพันธุ์) กรอบ 1-6 สำเร็จ');
    }
}
