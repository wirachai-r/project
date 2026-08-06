<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram42VomitingSeeder extends Seeder
{
    private const DIAGRAM_ID = '00042';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            // รายการกรอบทั้งหมดในแผนภูมิที่ 42
            $frameNumbers = [
                '1', '1.1', '1.2', '1.3', '1.4', '1.4.1', '1.5', '1.6',
                '2', '2.1', '2.2', '2.3', '3', '4', '5', '6', '7',
                '8', '9', '10', '11', '11.1', '11.2', '11.3', '11.4',
                '12', '12.1', '13'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 42 ถูก seed แล้ว กรุณารัน Rollback หรือลบข้อมูลเดิมก่อน'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 42
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'อาเจียน (VOMITING)',
                'diagram_name_en' => 'Vomiting',
                'description' => 'มีอาการอาเจียน มีเศษอาหาร เสมหะหรือเลือดออกมา อาจมีอาการคลื่นไส้นำมาก่อน สาเหตุที่พบบ่อย อาหารไม่ย่อย (49) แผลเพ็ปติก (51) อาหารเป็นพิษ (34) อาเจียนจากการไอ แพ้ท้อง (154) กระเพาะอาหารอักเสบ (50) เด็กที่อาเจียนเรื้อรัง อาจมีสาเหตุจากโรคพยาธิไส้เดือน (230) เด็กไม่อยากไปโรงเรียน (90) ถ้าอาการไม่ชัดเจน ถ้าเป็นเฉียบพลันให้การดูแลรักษาดังกรอบที่ 13 ถ้าเป็นเรื้อรังในเด็กให้ยาถ่ายพยาธิไส้เดือน (ย6)',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 42
            $boxes = [
                'B1'     => ['frame' => '1',      'type' => 'S', 'q' => 'อาเจียนเป็นเลือด หรือ เป็นสีกาแฟ (hematemesis)?'],
                'B1_1'   => ['frame' => '1.1',    'type' => 'S', 'q' => 'มีไข้? มีจุดแดงจ้ำเขียว? หรือ มีเลือดออกที่อื่น ๆ?'],
                'B1_2'   => ['frame' => '1.2',    'type' => 'S', 'q' => 'หลังกินยาแก้ปวดหรือยาชุด หรือดื่มแอลกอฮอล์?'],
                'B1_3'   => ['frame' => '1.3',    'type' => 'S', 'q' => 'ตาเหลือง? ท้องบวม? หรือ เป็นโรคตับแข็งอยู่ก่อน?'],
                'B1_4'   => ['frame' => '1.4',    'type' => 'S', 'q' => 'เคยมีอาการปวดท้องเรื้อรัง หรือเป็นโรคกระเพาะมาก่อน?'],
                'B1_4_1' => ['frame' => '1.4.1',  'type' => 'S', 'q' => 'เบื่ออาหารและน้ำหนักลด?'],
                'B1_5'   => ['frame' => '1.5',    'type' => 'S', 'q' => 'หลังมีไข้สูง 3-4 วัน? หรือ ทดสอบทูร์นิเกต์ให้ผลบวก?'],
                'B1_6'   => ['frame' => '1.6',    'type' => 'S', 'q' => 'หลังมีเลือดกำเดาไหล และอาเจียนเป็นลิ่มเลือดเพียง 1-2 ครั้ง?'],
                'B2'     => ['frame' => '2',      'type' => 'S', 'q' => 'อาเจียนรุนแรง?'],
                'B2_1'   => ['frame' => '2.1',    'type' => 'S', 'q' => 'ปวดศีรษะรุนแรง? คอแข็ง? หรือ มีประวัติศีรษะได้รับบาดเจ็บภายใน 1 สัปดาห์?'],
                'B2_2'   => ['frame' => '2.2',    'type' => 'S', 'q' => 'พบหลังจากเริ่มฟื้นหายจากไข้หวัด/โรคติดเชื้อไวรัส?'],
                'B2_3'   => ['frame' => '2.3',    'type' => 'S', 'q' => 'ปวดท้องรุนแรง? ท้องแข็ง? หรือ กดถูกหน้าท้องเจ็บ?'],
                'B3'     => ['frame' => '3',      'type' => 'S', 'q' => 'หลังกินปลาปักเป้า/แมงดาทะเล/คางคก/เห็ดป่า/อาหารบรรจุในภาชนะที่ปิดมิดชิด?'],
                'B4'     => ['frame' => '4',      'type' => 'S', 'q' => 'หลังกินอาหารทะเล อาหารที่มีแมลงวันตอม หรืออาหารไม่สุก?'],
                'B5'     => ['frame' => '5',      'type' => 'S', 'q' => 'อาเจียนเฉพาะเวลาไอ?'],
                'B6'     => ['frame' => '6',      'type' => 'S', 'q' => 'ดีซ่าน (ตาเหลือง)? ปวดศีรษะ? เวียนศีรษะ? ปวดท้อง? หรือ ท้องเดิน?'],
                'B7'     => ['frame' => '7',      'type' => 'S', 'q' => 'เป็นขณะขึ้นรถลงเรือ หรือนั่งเครื่องบิน?'],
                'B8'     => ['frame' => '8',      'type' => 'S', 'q' => 'เป็นหลังกินยาแอสไพริน (ย1.1) ยาแก้ข้ออักเสบ (ย2) หรือดื่มแอลกอฮอล์?'],
                'B9'     => ['frame' => '9',      'type' => 'S', 'q' => 'เป็นหลังกินยา หรือฉีดยาบางชนิด?'],
                'B10'    => ['frame' => '10',     'type' => 'S', 'q' => 'พบในผู้หญิงที่แต่งงานแล้วและประจำเดือนขาด? หรือ สงสัยตั้งครรภ์?'],
                'B11'    => ['frame' => '11',     'type' => 'S', 'q' => 'อาเจียนเป็นๆ หายๆ เรื้อรัง?'],
                'B11_1'  => ['frame' => '11.1',   'type' => 'S', 'q' => 'บวม? ซีด? ความดันโลหิตสูง? หรือ มีประวัติเป็นโรคไต เบาหวาน หรือความดันโลหิตสูงมานาน?'],
                'B11_2'  => ['frame' => '11.2',   'type' => 'S', 'q' => 'จุกแน่นท้อง? หรือ มีลมในท้อง?'],
                'B11_3'  => ['frame' => '11.3',   'type' => 'S', 'q' => 'เด็กไม่อยากไปโรงเรียนหรือเรียนหนัก (เด็กที่เพิ่งเข้าเรียนใน 1-2 ปีแรก)?'],
                'B11_4'  => ['frame' => '11.4',   'type' => 'S', 'q' => 'เด็กอาเจียน หรือถ่ายเป็นตัวไส้เดือน? หรือ สงสัยเป็นพยาธิ?'],
                'B12'    => ['frame' => '12',     'type' => 'S', 'q' => 'พบในเด็ก?'],
                'B12_1'  => ['frame' => '12.1',   'type' => 'S', 'q' => 'เป็นเรื้อรัง?'],
                'B13'    => ['frame' => '13',     'type' => 'S', 'q' => 'ข้อแนะนำการดูแลรักษาการอาเจียนเฉียบพลัน/ไม่ชัดเจน'],
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

            // โครงสร้าง Decision Tree แบบ Binary
            $binary = [
                'B1'     => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'   => [['hematemesis_infection_bleeding', null], [null, 'B1_2']],
                'B1_2'   => [['acute_gastritis_50', null], [null, 'B1_3']],
                'B1_3'   => [['cirrhosis_severe_44', null], [null, 'B1_4']],
                'B1_4'   => [[null, 'B1_4_1'], [null, 'B1_5']],
                'B1_4_1' => [['stomach_cancer_237_11', null], ['peptic_ulcer_51', null]],
                'B1_5'   => [['dengue_fever_225', null], [null, 'B1_6']],
                'B1_6'   => [['swallowed_epistaxis_30', null], ['hematemesis_urgent_other', null]],
                'B2'     => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'   => [['cns_emergency_65_81_82_83', null], [null, 'B2_2']],
                'B2_2'   => [['reye_syndrome_65_1', null], [null, 'B2_3']],
                'B2_3'   => [['acute_abdomen_46_47_52_53_54_56', null], [null, 'B3']],
                'B3'     => [['botulism_toxin_poisoning_67_1_219', null], [null, 'B4']],
                'B4'     => [['food_poisoning_34_1', null], [null, 'B5']],
                'B5'     => [['cough_induced_vomiting', null], [null, 'B6']],
                'B6'     => [['refer_other_diagrams_11_21_22_43_47', null], [null, 'B7']],
                'B7'     => [['motion_sickness', null], [null, 'B8']],
                'B8'     => [['drug_alcohol_gastritis_50', null], [null, 'B9']],
                'B9'     => [['drug_side_effect_nausea', null], [null, 'B10']],
                'B10'    => [['morning_sickness_154', null], [null, 'B11']],
                'B11'    => [[null, 'B11_1'], [null, 'B12']],
                'B11_1'  => [['chronic_renal_failure_134', null], [null, 'B11_2']],
                'B11_2'  => [['indigestion_cholecystitis_49_40', null], [null, 'B11_3']],
                'B11_3'  => [['school_refusal_90', null], [null, 'B11_4']],
                'B11_4'  => [['ascariasis_230', null], ['chronic_vomiting_adult_cancer_check', null]],
                'B12'    => [[null, 'B12_1'], [null, 'B13']],
                'B12_1'  => [[null, 'B11_3'], ['pediatric_vomiting_55', null]],
                'B13'    => [['acute_vomiting_general_care_13', null], ['acute_vomiting_general_care_13', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กฎการวินิจฉัย การรักษา และคำแนะนำ
            $rules = [
                'hematemesis_infection_bleeding' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน (ดู "โรคที่ 103, 104, 106, 225, 227, 228")
NOTE,
                    'refs'       => ['103', '104', '106', '225', '227', '228'],
                    'diagrams'   => [],
                ],
                'acute_gastritis_50' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
กระเพาะอาหารอักเสบเฉียบพลัน (50)
⊕ ด่วน
• ให้น้ำเกลือถ้าช็อก
NOTE,
                    'refs'       => ['50'],
                    'diagrams'   => [],
                ],
                'cirrhosis_severe_44' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ตับแข็ง (44) ระยะร้ายแรง
⊕ ด่วน
• ให้น้ำเกลือถ้าช็อก
NOTE,
                    'refs'       => ['44'],
                    'diagrams'   => [],
                ],
                'stomach_cancer_237_11' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
มะเร็งกระเพาะอาหาร (237.11)
⊕ ด่วน
• ให้น้ำเกลือถ้าช็อก
NOTE,
                    'refs'       => ['237.11'],
                    'diagrams'   => [],
                ],
                'peptic_ulcer_51' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
แผลเพ็ปติก (51)
⊕ ด่วน
• ให้น้ำเกลือถ้าช็อก
NOTE,
                    'refs'       => ['51'],
                    'diagrams'   => [],
                ],
                'dengue_fever_225' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ไข้เลือดออก (225)
⊕ ด่วน
• ให้น้ำเกลือถ้าช็อก
NOTE,
                    'refs'       => ['225'],
                    'diagrams'   => [],
                ],
                'swallowed_epistaxis_30' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากการกลืนเลือดกำเดา (ดู "โรคที่ 30")
NOTE,
                    'refs'       => ['30'],
                    'diagrams'   => [],
                ],
                'hematemesis_urgent_other' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุอื่น ๆ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'cns_emergency_65_81_82_83' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็น เยื่อหุ้มสมองอักเสบ (65)/ เลือดออกในสมอง (81)/ ฝีสมอง (82)/ เนื้องอกสมอง (83)
NOTE,
                    'refs'       => ['65', '81', '82', '83'],
                    'diagrams'   => [],
                ],
                'reye_syndrome_65_1' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็น โรคเรย์ซินโดรม (65.1)
NOTE,
                    'refs'       => ['65.1'],
                    'diagrams'   => [],
                ],
                'acute_abdomen_46_47_52_53_54_56' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีภาวะรุนแรงในช่องท้อง (ดู "โรคที่ 46, 47, 52, 53, 54, 56")
NOTE,
                    'refs'       => ['46', '47', '52', '53', '54', '56'],
                    'diagrams'   => [],
                ],
                'botulism_toxin_poisoning_67_1_219' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นโบทูลิซึม (67.1)/พิษสัตว์ (219.1, 219.4)/พิษเห็ด (219.5)
NOTE,
                    'refs'       => ['67.1', '219.1', '219.4', '219.5'],
                    'diagrams'   => [],
                ],
                'food_poisoning_34_1' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
อาหารเป็นพิษจากเชื้อโรค (34.1)
• กินหรือฉีดน้ำเกลือ
⊕ ถ้าไม่ดีขึ้นใน 24 ชั่วโมง หรือมีภาวะขาดน้ำรุนแรง/ปากและลิ้นชาหรือเสียวแปลบๆ
NOTE,
                    'refs'       => ['34.1'],
                    'diagrams'   => [],
                ],
                'cough_induced_vomiting' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อาเจียนจากการไอ
• รักษาโรคที่เป็นสาเหตุ เช่น ไข้หวัด (1) หลอดลมอักเสบ (15) ไอกรน (13)
NOTE,
                    'refs'       => ['1', '15', '13'],
                    'diagrams'   => [],
                ],
                'refer_other_diagrams_11_21_22_43_47' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่:
• 11 ดีซ่าน (กรอบที่ 3)
• 21 ปวดศีรษะ (กรอบที่ 3)
• 22 เวียนศีรษะ (กรอบที่ 5)
• 43 ปวดท้อง (กรอบที่ 5)
• 47 ท้องเดิน (กรอบที่ 2)
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00011', '00021', '00022', '00043', '00047'],
                ],
                'motion_sickness' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เมารถ/เรือ/เครื่องบิน (motion sickness)
• กินไดเมนไฮดริเนต (ย19.1) แก้อาเจียน (ป้องกันโดยการกินไดเมนไฮดริเนตก่อนเดินทาง 30 นาที)
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'drug_alcohol_gastritis_50' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กระเพาะอาหารอักเสบ (50)
• หยุดยาที่กิน/งดแอลกอฮอล์
• ให้ยาลดกรด (ย14.1)
NOTE,
                    'refs'       => ['50'],
                    'diagrams'   => [],
                ],
                'drug_side_effect_nausea' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ผลข้างเคียงจากยา* ถ้าคลื่นไส้ อาเจียนมากให้ เมโทโคลพราไมด์ (ย19.2)
* ยาที่ทำให้มีอาการคลื่นไส้ อาเจียน เช่น แอสไพริน (ย1.1) อิริโทรไมซิน (ย4.4) เตตราไซคลีน (ย4.5) ดอกซีไซคลีน (ย4.5.1) คลอโรควิน (ย5.1) ควินิน (ย5.3) อะดรีนาลิน (ย11) ทีโอฟิลลีน (ย10.2) ยาบำรุงโลหิต (ย24.11) ดิจิทาลิส (digitalis) เอสโทรเจน เป็นต้น
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'morning_sickness_154' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
แพ้ท้อง (154)
• ตรวจปัสสาวะ
• แนะนำไปฝากครรภ์
• ระวังการใช้ยา
• ห้ามดื่มแอลกอฮอล์/สูบบุหรี่
NOTE,
                    'refs'       => ['154'],
                    'diagrams'   => [],
                ],
                'chronic_renal_failure_134' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ภาวะไตวายเรื้อรัง (134)
• ให้ยาแก้อาเจียน (ย19)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['134'],
                    'diagrams'   => [],
                ],
                'indigestion_cholecystitis_49_40' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อาหารไม่ย่อย (49)/ ถุงน้ำดีอักเสบเรื้อรัง (40)
• ยาแก้ท้องอืดท้องเฟ้อ (ย13)
⊕ ถ้าสงสัยเป็นถุงน้ำดีอักเสบ/ดีซ่าน/น้ำหนักลด/ถ่ายดำ
NOTE,
                    'refs'       => ['49', '40'],
                    'diagrams'   => [],
                ],
                'school_refusal_90' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เด็กไม่อยากไปโรงเรียน (90)
• ให้ความมั่นใจแก่พ่อแม่เด็ก ว่าไม่ใช่สาเหตุร้ายแรง และควรให้เด็กไปเรียนตามปกติ
⊕ ถ้าไม่หายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['90'],
                    'diagrams'   => [],
                ],
                'ascariasis_230' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคพยาธิไส้เดือน (230)
• ยาถ่ายพยาธิ (ย6)
⊕ ถ้าไม่หายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['230'],
                    'diagrams'   => [],
                ],
                'chronic_vomiting_adult_cancer_check' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจมีสาเหตุอื่นๆ ในผู้ใหญ่อาจเป็นมะเร็งกระเพาะอาหาร (237.11)
NOTE,
                    'refs'       => ['237.11'],
                    'diagrams'   => [],
                ],
                'pediatric_vomiting_55' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดู อาเจียนในเด็ก (55)
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00055'],
                ],
                'acute_vomiting_general_care_13' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
การดูแลรักษาการอาเจียนเฉียบพลัน/ไม่ชัดเจน:
• งดอาหารแข็งหรืออาหารที่ย่อยยาก
• กินอาหารเหลว/น้ำหวาน ทีละน้อยแต่บ่อยครั้ง
• ให้ยาแก้อาเจียน (ย19)
⊕ ถ้าไม่หายอาเจียนภายใน 48 ชั่วโมง หรือมีภาวะขาดน้ำรุนแรง/ยาอาเจียนรุนแรง/กินอะไรไม่ได้/บวม/ซีด/ความดันโลหิตสูง
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
                    'medical_reference' => 'แผนภูมิที่ 42',
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

        $this->command->info('สร้างแผนภูมิที่ 42 (อาเจียน) กรอบ 1-13 สำเร็จ');
    }
}
