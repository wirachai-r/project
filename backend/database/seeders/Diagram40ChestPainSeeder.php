<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram40ChestPainSeeder extends Seeder
{
    private const DIAGRAM_ID = '00040';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '5', '5.1', '5.2', '5.3',
                '6', '6.1', '7', '8', '9', '10', '10.1',
                '11', '12', '13', '14', '15', '15.1', '16', '17'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 40 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 40
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'เจ็บหน้าอก (CHEST PAIN)',
                'diagram_name_en' => 'Chest Pain',
                'description' => 'มีอาการเจ็บ จุกแน่น ปวดแสบปวดร้อน หรือปวดแปลบในบริเวณหน้าอก สาเหตุที่พบบ่อย เจ็บตามกล้ามเนื้อหรือกระดูกหน้าอก โรควิตกกังวล/โรคกังวลทั่วไป (88) ไอจากไข้หวัด (1) หรือหลอดลมอักเสบ (15) เยื่อหุ้มปอดอักเสบ (21) โรคกระเพาะอาหาร (51) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 17',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 40
            $boxes = [
                'B1'     => ['frame' => '1',    'type' => 'S', 'q' => 'หอบ?'],
                'B2'     => ['frame' => '2',    'type' => 'S', 'q' => "เจ็บรุนแรง? มีภาวะช็อก?\nหรือ ลุกนัั่งจะเป็นลม?"],
                'B3'     => ['frame' => '3',    'type' => 'S', 'q' => 'น้ำหนักลดฮวบ?'],
                'B4'     => ['frame' => '4',    'type' => 'S', 'q' => "ปวดชายโครงขวา? และ ตับโต\nเป็นก้อนแข็ง หรือผิวขรุขระ?"],
                'B5'     => ['frame' => '5',    'type' => 'S', 'q' => 'มีไข้?'],
                'B5_1'   => ['frame' => '5.1',  'type' => 'S', 'q' => "เจ็บแปลบเวลาหายใจ\nเข้าลึกๆ?\nหรือ เจ็บหน้าอกมาก?"],
                'B5_2'   => ['frame' => '5.2',  'type' => 'S', 'q' => 'หนาวสั่นมาก?'],
                'B5_3'   => ['frame' => '5.3',  'type' => 'S', 'q' => 'มีน้ำมูกหรือไอ?'],
                'B6'     => ['frame' => '6',    'type' => 'S', 'q' => 'ได้รับบาดเจ็บ?'],
                'B6_1'   => ['frame' => '6.1',  'type' => 'S', 'q' => 'เจ็บมาก?'],
                'B7'     => ['frame' => '7',    'type' => 'S', 'q' => 'กดถูกเจ็บ?'],
                'B8'     => ['frame' => '8',    'type' => 'S', 'q' => "เจ็บแปลบเฉพาะเวลาหายใจเข้า\nแรงๆ จามหรือไอ?"],
                'B9'     => ['frame' => '9',    'type' => 'S', 'q' => 'เจ็บเฉพาะเวลาไอ?'],
                'B10'    => ['frame' => '10',   'type' => 'S', 'q' => "ปวดเค้นหรือจุกแน่นที่กลางอก\nร้าวไปที่ขากรรไกร คอ หรือแขน?"],
                'B10_1'  => ['frame' => '10.1', 'type' => 'S', 'q' => "นานครั้งละ 2-3 นาที\nไม่เกิน 15 นาที?\nหรือ นั่งพักแล้วดีขึ้น?"],
                'B11'    => ['frame' => '11',   'type' => 'S', 'q' => "ปวดแสบตรงใต้ลิ้นปี่เวลาหิว\nหรืออิ่มจัด? หรือ ปวดตอนดึก?"],
                'B12'    => ['frame' => '12',   'type' => 'S', 'q' => "จุกแน่นหรือแสบตรงยอดอก\n(ลิ้นปี่)? แสบร้าวจากยอดอกขึ้น\nไปถึงคอหอย? หรือ เรอเปรี้ยว\nขึ้นคอหอย?"],
                'B13'    => ['frame' => '13',   'type' => 'S', 'q' => "ปวดบิดเป็นพักๆ ตรงชายโครง\nขวาหลังกินอาหารมันๆ?"],
                'B14'    => ['frame' => '14',   'type' => 'S', 'q' => "ปวดเสียวหน้าอกแถบหนึ่งตรง\nบริเวณที่เคยเป็นงูสวัด?"],
                'B15'    => ['frame' => '15',   'type' => 'S', 'q' => 'เคยเป็นงูสวัดประจำถิ่น?'],
                'B15_1'  => ['frame' => '15.1', 'type' => 'S', 'q' => "ปวดแสบร้อน\nอยู่แถบหนึ่ง?"],
                'B16'    => ['frame' => '16',   'type' => 'S', 'q' => "เจ็บขณะนั่ง นอน หรืออยู่นิ่งๆ\n(เวลาออกกำลังหรือทำอะไร\nเพลินๆ ไม่เจ็บ? และ มีความกลัว\nรุนแรง วิตกกังวล หรือซึมเศร้า)?"],
                'B17'    => ['frame' => '17',   'type' => 'T', 'q' => "ยาแก้ปวด (ย1)\n⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์\nหรือน้ำหนักลด/คลำได้ก้อน\nที่ชายโครงขวา"],
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
                'B1'     => [['refer_diagram_39', null], [null, 'B2']],
                'B2'     => [['severe_chest_pain_emergency', null], [null, 'B3']],
                'B3'     => [['lung_or_liver_cancer_weight_loss', null], [null, 'B4']],
                'B4'     => [['liver_cancer', null], [null, 'B5']],
                'B5'     => [[null, 'B5_1'], [null, 'B6']],
                'B5_1'   => [['pneumonia_tb_empyema', null], [null, 'B5_2']],
                'B5_2'   => [['early_pneumonia_lung_abscess', null], [null, 'B5_3']],
                'B5_3'   => [['refer_diagram_2', null], ['symptomatic_fever_chest_pain', null]],
                'B6'     => [[null, 'B6_1'], [null, 'B7']],
                'B6_1'   => [['rib_fracture', null], [null, 'B7']],
                'B7'     => [['chest_wall_muscle_pain', null], [null, 'B8']],
                'B8'     => [['pleurisy_muscle_bone_pain', null], [null, 'B9']],
                'B9'     => [['cough_induced_chest_pain', null], [null, 'B10']],
                'B10'    => [[null, 'B10_1'], [null, 'B11']],
                'B10_1'  => [['angina_pectoris', null], ['myocardial_infarction_urgent', null]],
                'B11'    => [['dyspepsia_gastritis_peptic_ulcer', null], [null, 'B12']],
                'B12'    => [['gerd', null], [null, 'B13']],
                'B13'    => [['gallstones', null], [null, 'B14']],
                'B14'    => [['post_herpetic_neuralgia', null], [null, 'B15']],
                'B15'    => [[null, 'B16'], [null, 'B15_1']],
                'B15_1'  => [['early_herpes_zoster', null], [null, 'B16']],
                'B16'    => [['anxiety_panic_depression', null], [null, 'B17']],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // กรอบที่ 17 (Terminal Text Box)
            $addChoice('B17', 'การดูแลรักษาตามกรอบที่ 17', null, 'unspecified_chest_pain_care', 1);

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'refer_diagram_39' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 39 หอบ กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00039'],
                ],
                'severe_chest_pain_emergency' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นกล้ามเนื้อหัวใจตาย (96)/เยื่อหุ้มหัวใจอักเสบ/ภาวะสิ่งหลุดอุดตันหลอดเลือดแดงปอด (ดู "โรคที่ 99.1")/ภาวะเลือดเซาะผนังหลอดเลือดแดงใหญ่ (92.1)
• ฉีดมอร์ฟีน
NOTE,
                    'refs'       => ['96', '99.1', '92.1'],
                    'diagrams'   => [],
                ],
                'lung_or_liver_cancer_weight_loss' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งปอด (237.6)/มะเร็งตับ (45)
NOTE,
                    'refs'       => ['237.6', '45'],
                    'diagrams'   => [],
                ],
                'liver_cancer' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
มะเร็งตับ (45)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['45'],
                    'diagrams'   => [],
                ],
                'pneumonia_tb_empyema' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจเป็นปอดอักเสบ (19)/วัณโรคปอด (14)/ภาวะมีหนองในโพรงเยื่อหุ้มปอด (20)
NOTE,
                    'refs'       => ['19', '14', '20'],
                    'diagrams'   => [],
                ],
                'early_pneumonia_lung_abscess' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจเป็นปอดอักเสบระยะแรก (19)/ฝีตับอะมีบา (39)/อื่นๆ
NOTE,
                    'refs'       => ['19', '39'],
                    'diagrams'   => [],
                ],
                'refer_diagram_2' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 2 ใช้ร่วมกับน้ำมูก หรือไอ กรอบที่ 2
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00002'],
                ],
                'symptomatic_fever_chest_pain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ
⊕ ถ้าไม่ดีขึ้นใน 4 วัน หรือน้ำหนักลด/หอบ/เจ็บหน้าอกมาก
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'rib_fracture' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อาจเป็นกระดูกซี่โครงหัก (214)
• ยาแก้ปวด (ย1)
• นอนพัก
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือมีอาการซีด/หอบ/ช็อก
NOTE,
                    'refs'       => ['214'],
                    'diagrams'   => [],
                ],
                'chest_wall_muscle_pain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เจ็บตามกล้ามเนื้อ หรือกระดูก
• ยาแก้ปวด (ย1) นวดยา
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือปวดรุนแรง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'pleurisy_muscle_bone_pain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เยื่อหุ้มปอดอักเสบ (21)/เจ็บกล้ามเนื้อหรือกระดูก
• ยาต้านอักเสบที่ไม่ใช่สเตียรอยด์ (ย2)
⊕ ถ้าไม่ดีขึ้นใน 7 วัน หรือมีไข้/หายใจหอบ/ช็อก/น้ำหนักลด/ไอเป็นเลือด/เจ็บรุนแรง/ใช้เครื่องฟังตรวจปอดพบว่าเสียงหายใจค่อย
NOTE,
                    'refs'       => ['21'],
                    'diagrams'   => [],
                ],
                'cough_induced_chest_pain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เกิดจากการไอ
• รักษาโรคที่เป็นสาเหตุ เช่น ไข้หวัด (1)/หลอดลมอักเสบ (15)
NOTE,
                    'refs'       => ['1', '15'],
                    'diagrams'   => [],
                ],
                'angina_pectoris' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
หัวใจขาดเลือดชั่วขณะ (96)
⊕ ภายใน 3 วัน
• หลีกเลี่ยงสาเหตุกระตุ้น
• ให้อมยายายหลอดเลือดหัวใจเวลาเจ็บ
NOTE,
                    'refs'       => ['96'],
                    'diagrams'   => [],
                ],
                'myocardial_infarction_urgent' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นกล้ามเนื้อหัวใจตาย (96)
NOTE,
                    'refs'       => ['96'],
                    'diagrams'   => [],
                ],
                'dyspepsia_gastritis_peptic_ulcer' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อาหารไม่ย่อย (49)/กระเพาะอาหารอักเสบ (50)/แผลเพ็ปติก (51)
• ยาต้านกรด (ย14.1)
• รานิทีดีน (ย14.3)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือถ่ายดำ/ซีด/กลืนลำบาก/อาเจียนมาก/น้ำหนักลด/คลำได้ก้อนในท้อง/ตับโต/อายุเกิน 40 ปี/กำเริบซ้ำ
NOTE,
                    'refs'       => ['49', '50', '51'],
                    'diagrams'   => [],
                ],
                'gerd' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคกรดไหลย้อน (49.1)
NOTE,
                    'refs'       => ['49.1'],
                    'diagrams'   => [],
                ],
                'gallstones' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
นิ่วในถุงน้ำดี (40)
• แอนติสปาสโมดิก (ย20)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['40'],
                    'diagrams'   => [],
                ],
                'post_herpetic_neuralgia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปวดประสาทหลังเป็นงูสวัด (188)
• ยาแก้ปวด (ย1)/อะมิทริปไทลีน (ย17.2)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือปวดรุนแรง
NOTE,
                    'refs'       => ['188'],
                    'diagrams'   => [],
                ],
                'early_herpes_zoster' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
งูสวัด (188) ระยะแรกเริ่ม
• ยาแก้ปวด (ย1)
• รักษาแบบงูสวัดเมื่อมีตุ่มใสขึ้น
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['188'],
                    'diagrams'   => [],
                ],
                'anxiety_panic_depression' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรควิตกกังวล/โรคกังวลทั่วไป (88)/โรคแพนิก (88.1)/โรคอารมณ์แปรปรวน/โรคซึมเศร้า (88.2)
• ยาทางจิตประสาท (ย17)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['88', '88.1', '88.2'],
                    'diagrams'   => [],
                ],
                'unspecified_chest_pain_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ปวด (ย1)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือน้ำหนักลด/คลำได้ก้อนที่ชายโครงขวา
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
                    'medical_reference' => 'แผนภูมิที่ 40',
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

        $this->command->info('สร้างแผนภูมิที่ 40 (เจ็บหน้าอก - CHEST PAIN) กรอบ 1-17 สำเร็จ');
    }
}
