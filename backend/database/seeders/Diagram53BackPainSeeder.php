<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram53BackPainSeeder extends Seeder
{
    private const DIAGRAM_ID = '00053';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '3.1', '3.2', '3.3', '3.3.1',
                '4', '4.1', '5', '6', '7', '8', '8.1', '8.2', '8.3',
                '9', '10', '10.1', '11'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 53 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 53
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ปวดหลัง (BACK PAIN)',
                'diagram_name_en' => 'Back Pain',
                'description' => 'มีอาการปวดเจ็บบริเวณหลัง ซึ่งอาจปวดตลอดเวลาหรือเป็นพักๆ สาเหตุที่พบบ่อย ปวดกล้ามเนื้อหลัง (107) ข้อเสื่อม (109) รากประสาทถูกกด (108) แผลไข้งูสวัด (51) ไข้หวัดใหญ่ (2) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 11',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 53
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'ปวดหลังส่วนบนรุนแรง เจ็บหน้าอกรุนแรง หรือปวดท้องรุนแรงเกิดขึ้นฉับพลัน? มีภาวะช็อก? หรือ เป็นอัมพาตฉับพลัน?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'น้ำหนักลดฮวบ?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'มีไข้?'],
                'B3_1'    => ['frame' => '3.1',   'type' => 'S', 'q' => 'ปวดท้องรุนแรง?'],
                'B3_2'    => ['frame' => '3.2',   'type' => 'S', 'q' => 'เคาะเจ็บที่สีข้าง? และ ปัสสาวะขุ่น?'],
                'B3_3'    => ['frame' => '3.3',   'type' => 'S', 'q' => 'มีไข้เกิน 7 วัน?'],
                'B3_3_1'  => ['frame' => '3.3.1', 'type' => 'S', 'q' => 'ปวดเมื่อยตามตัวมาก? หรือ มีน้ำมูกไหล?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'อาการเกิดหลังได้รับบาดเจ็บ?'],
                'B4_1'    => ['frame' => '4.1',   'type' => 'S', 'q' => 'แขนชาขาชา? หรือ เป็นอัมพาต?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'ปวดร้าว เสียวๆ แปลบๆ หรือรู้สึกชาลงมาตามด้านหลังของขา?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => 'ปัสสาวะขุ่นแดง หรือเป็นเม็ดทราย?'],
                'B7'      => ['frame' => '7',     'type' => 'S', 'q' => 'ปวดท้อง? ท้องเดิน? ดีซ่าน? หรือ ขัดเบา?'],
                'B8'      => ['frame' => '8',     'type' => 'S', 'q' => 'ปวดตรงกลางหลังส่วนล่าง (บริเวณกระเบนเหน็บ)?'],
                'B8_1'    => ['frame' => '8.1',   'type' => 'S', 'q' => 'ตั้งครรภ์นานกว่า 4 เดือน?'],
                'B8_2'    => ['frame' => '8.2',   'type' => 'S', 'q' => 'รูปร่างอ้วน?'],
                'B8_3'    => ['frame' => '8.3',   'type' => 'S', 'q' => 'ปวดมากเวลายกของหนัก? หลังเล่นกีฬา? นั่งนานๆ? ยืนนานๆ? ใส่ส้นสูง? หรือ เวลาตื่นนอน (นอนที่นอนนุ่มไป)?'],
                'B9'      => ['frame' => '9',     'type' => 'S', 'q' => 'ปวดหลังนานเกิน 3 เดือน ในคนอยู่น้อยกว่า 30 ปี? หรือ ปวดหลัง หลังแข็งก่อนตื่นนอนตอนเช้าและทุเลาหลังบริหารร่างกาย เป็นนานเกิน 3 เดือน?'],
                'B10'     => ['frame' => '10',    'type' => 'S', 'q' => 'พบในคนอายุมากกว่า 60 ปี?'],
                'B10_1'   => ['frame' => '10.1',  'type' => 'S', 'q' => 'ปวดตามข้อเข่า/ข้อนิ้วมือ หรือข้ออื่นๆ ร่วมด้วย?'],
                'B11'     => ['frame' => '11',    'type' => 'S', 'q' => 'ปวดหลังทั่วไป?'],
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
                'B1'      => [['cardiovascular_emergency', null], [null, 'B2']],
                'B2'      => [['pancreatic_spinal_cancer', null], [null, 'B3']],
                'B3'      => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'    => [['goto_diagram_44_frame1_1', null], [null, 'B3_2']],
                'B3_2'    => [['acute_pyelonephritis', null], [null, 'B3_3']],
                'B3_3'    => [['fever_over_7_days', null], [null, 'B3_3_1']],
                'B3_3_1'  => [['influenza', null], ['symptomatic_fever_care', null]],
                'B4'      => [[null, 'B4_1'], [null, 'B5']],
                'B4_1'    => [['spinal_cord_injury', null], ['back_trauma_care', null]],
                'B5'      => [['herniated_disc_nerve_compression', null], [null, 'B6']],
                'B6'      => [['kidney_stone', null], [null, 'B7']],
                'B7'      => [['goto_referred_pain_diagrams', null], [null, 'B8']],
                'B8'      => [[null, 'B8_1'], [null, 'B9']],
                'B8_1'    => [['pregnancy_back_pain', null], [null, 'B8_2']],
                'B8_2'    => [['obesity_back_pain', null], [null, 'B8_3']],
                'B8_3'    => [['back_muscle_strain', null], [null, 'B9']],
                'B9'      => [['ankylosing_spondylitis_b9', null], [null, 'B10']],
                'B10'     => [[null, 'B10_1'], ['general_back_pain_care', null]],
                'B10_1'   => [['osteoarthritis_b10_1', null], ['general_back_pain_care', null]],
                'B11'     => [['general_back_pain_care', null], ['general_back_pain_care', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'cardiovascular_emergency' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน
อาจเป็นกล้ามเนื้อหัวใจตาย (96)/ภาวะเลือดเซาะผนังหลอดเลือดแดงใหญ่ (92.1)/หลอดเลือดแดงใหญ่โป่งพองแตก (92.1)
NOTE,
                    'refs'       => ['96', '92.1'],
                    'diagrams'   => [],
                ],
                'pancreatic_spinal_cancer' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็น มะเร็งตับอ่อน (237.14)/มะเร็งกระดูกสันหลัง
NOTE,
                    'refs'       => ['237.14'],
                    'diagrams'   => [],
                ],
                'goto_diagram_44_frame1_1' => [
                    'urgency'    => 'P',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 44 ปวดท้องร่วมกับมีไข้ กรอบที่ 1.1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00044'],
                ],
                'acute_pyelonephritis' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
กรวยไตอักเสบเฉียบพลัน (137)
• ชันสูตรเพิ่มเติม
• ยาลดไข้ (ย1)
• โคไตรม็อกซาโซล (ย4.7) หรืออะม็อกซีซิลลิน (ย4.2) หรือซิโพรโฟล็กซาซิน (ย4.11.2)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือซีด/ดีซ่าน/มีจุดแดงจ้ำเขียว
NOTE,
                    'refs'       => ['137'],
                    'diagrams'   => [],
                ],
                'fever_over_7_days' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'influenza' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไข้หวัดใหญ่ (2)
• พาราเซตามอล (ย1.2)
⊕ ถ้าไม่ดีขึ้นใน 4 วัน หรือสงสัยไข้เลือดออก (240)
NOTE,
                    'refs'       => ['2', '240'],
                    'diagrams'   => [],
                ],
                'symptomatic_fever_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ (พักผ่อน เช็ดตัว ยาลดไข้)
⊕ ถ้ามีไข้เกิน 4 วัน หรือปวดหลังรุนแรง/ปวดศีรษะมาก/อาเจียนมาก/ซีด/ดีซ่าน/จุดแดงจ้ำเขียว/น้ำหนักลดฮวบ/ปัสสาวะขุ่นเป็นหนอง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'spinal_cord_injury' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ไขสันหลังได้รับบาดเจ็บ (85)
⊕ ด่วน
NOTE,
                    'refs'       => ['85'],
                    'diagrams'   => [],
                ],
                'back_trauma_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ปวด (ย1)
• ยาคลายกล้ามเนื้อ (ย3)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือปัสสาวะแดง/ปวดร้าวลงขา
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'herniated_disc_nerve_compression' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
หมอนรองกระดูกสันหลังเคลื่อน/ข้อกระดูกสันหลังตีบ/รากประสาทถูกกด (108)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['108'],
                    'diagrams'   => [],
                ],
                'kidney_stone' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
นิ่วไต (138)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['138'],
                    'diagrams'   => [],
                ],
                'goto_referred_pain_diagrams' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่:
• 43 ปวดท้อง กรอบที่ 6
• 47 ท้องเดิน กรอบที่ 1
• 11 ดีซ่าน กรอบที่ 3
• 54 ขัดเบา กรอบที่ 1.1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00043', '00047', '00011', '00054'],
                ],
                'pregnancy_back_pain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากการตั้งครรภ์
• ไม่ต้องทำอะไร
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'obesity_back_pain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากน้ำหนักมาก
• ยาแก้ปวด (ย1)
• พยายามลดน้ำหนัก
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'back_muscle_strain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปวดกล้ามเนื้อหลัง (107)
• ยาแก้ปวด (ย1)
• ยาคลายกล้ามเนื้อ (ย3)
• ถ้าปวดหลังเวลาตื่นนอน หลีกเลี่ยงการนอนที่นอนนุ่ม
• ปรับท่านั่ง/ยืน/ยกของให้ถูกต้อง
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['107'],
                    'diagrams'   => [],
                ],
                'ankylosing_spondylitis_b9' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ข้อสันหลังอักเสบเรื้อรัง (110.1)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['110.1'],
                    'diagrams'   => [],
                ],
                'osteoarthritis_b10_1' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ข้อเสื่อม (109)
• ยาแก้ปวด-พาราเซตามอล (ย1.2)
• ถ้าปวดมาก หรือข้อบวม ให้ยาต้านอักเสบที่ไม่ใช่สเตียรอยด์ (ย2)
• ลดน้ำหนัก
• หลีกเลี่ยงท่าที่ทำให้ปวด
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือน้ำหนักลดฮวบ
NOTE,
                    'refs'       => ['109'],
                    'diagrams'   => [],
                ],
                'general_back_pain_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ปวด (ย1)
• ยาคลายกล้ามเนื้อ (ย3)
• ยาทางจิตประสาท (ย17) ถ้ามีภาวะวิตกกังวล/ซึมเศร้า/เครียด
• หลีกเลี่ยงการนอนที่นอนนุ่ม
• ปรับท่านั่ง/ยืน/ยกของให้ถูกต้อง
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือมีอาการน้ำหนักลดฮวบ/ดีซ่าน/เป็นๆ หายๆ เรื้อรัง
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
                    'medical_reference' => 'แผนภูมิที่ 53',
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

        $this->command->info('สร้างแผนภูมิที่ 53 (ปวดหลัง - BACK PAIN) กรอบ 1-11 สำเร็จ');
    }
}
