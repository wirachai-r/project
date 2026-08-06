<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram43AbdominalPainSeeder extends Seeder
{
    private const DIAGRAM_ID = '00043';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '3.1', '3.2', '3.3', '3.4', '3.5', '3.6',
                '4', '5', '6', '6.1', '6.2', '6.3',
                '7', '7.1', '7.1.1', '7.2', '7.3', '7.4',
                '8', '9', '10'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 43 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 43
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ปวดท้อง (ABDOMINAL PAIN)',
                'diagram_name_en' => 'Abdominal Pain',
                'description' => 'มีอาการปวดท้อง จุกแน่น ท้องอืดท้องเฟ้อ หรือปวดบิดเป็นพักๆ ในท้อง อาจเป็นเฉพาะที่ หรือเป็นทั่วทั้งท้องก็ได้ สาเหตุที่พบบ่อย อาหารไม่ย่อย (49) กระเพาะอาหารอักเสบ (50) แผลเพ็ปติก (51) ท้องเดิน (32) ปวดประจำเดือน (150) นิ่วท่อไต (139) ไส้ติ่งอักเสบ (46) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 10',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 43
            $boxes = [
                'B1'     => ['frame' => '1',     'type' => 'S', 'q' => 'ปวดท้องน้อยในผู้หญิงวัยเจริญพันธุ์?'],
                'B2'     => ['frame' => '2',     'type' => 'S', 'q' => "ปวดแบบเดียวกับที่เคยเป็นมาก่อน?\nหรือ ปวดเป็นครั้งคราวเป็น ๆ หาย ๆ\nหรือเป็นเรื้อรัง?"],
                'B3'     => ['frame' => '3',     'type' => 'S', 'q' => "มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้\n☐ ปวดรุนแรง?\n☐ หน้าท้องเกร็งแข็ง?\n☐ ปวดติดต่อกันนานเกิน 6 ชั่วโมง?\n☐ เหงื่อออก หน้าซีด ตัวเย็น?\n☐ ความดันต่ำและชีพจรเบาเร็ว?"],
                'B3_1'   => ['frame' => '3.1',   'type' => 'S', 'q' => 'กดเจ็บตรงท้องน้อยข้างขวา?'],
                'B3_2'   => ['frame' => '3.2',   'type' => 'S', 'q' => "ปวดตรงใต้ลิ้นปี่? มีประวัติเป็นโรคกระเพาะ?\nหรือ กินยาแก้ปวดหรือยาชุดมานาน?"],
                'B3_3'   => ['frame' => '3.3',   'type' => 'S', 'q' => 'ได้รับบาดเจ็บที่ท้อง?'],
                'B3_4'   => ['frame' => '3.4',   'type' => 'S', 'q' => 'กดเจ็บทั่วทั้งท้อง?'],
                'B3_5'   => ['frame' => '3.5',   'type' => 'S', 'q' => 'อาเจียนรุนแรง?'],
                'B3_6'   => ['frame' => '3.6',   'type' => 'S', 'q' => 'ในทารกแรกเกิดที่กินกล้วย?'],
                'B4'     => ['frame' => '4',     'type' => 'S', 'q' => "ท้องเดิน? ดีซ่าน (ตาเหลือง)?\nขัดเบา? หรือ ปัสสาวะเป็นเลือด?"],
                'B5'     => ['frame' => '5',     'type' => 'S', 'q' => 'มีไข้?'],
                'B6'     => ['frame' => '6',     'type' => 'S', 'q' => 'ปวดบิดเป็นพัก ๆ?'],
                'B6_1'   => ['frame' => '6.1',   'type' => 'S', 'q' => "อาเจียนรุนแรง (กินอะไรลงไปก็อาเจียนออกหมด)?"],
                'B6_2'   => ['frame' => '6.2',   'type' => 'S', 'q' => 'ปวดตรงชายโครงขวาหลังกินอาหารมัน ๆ?'],
                'B6_3'   => ['frame' => '6.3',   'type' => 'S', 'q' => 'ตรงท้องน้อยหรืออัณฑะหรือช่องคลอดข้างเดียวกัน?'],
                'B7'     => ['frame' => '7',     'type' => 'S', 'q' => 'ปวดตรงใต้ลิ้นปี่หรือยอดอก?'],
                'B7_1'   => ['frame' => '7.1',   'type' => 'S', 'q' => "ปวดเค้นเสี้ยนจุกแน่น และร้าวไปที่ขากรรไกร คอ หรือแขน?"],
                'B7_1_1' => ['frame' => '7.1.1', 'type' => 'S', 'q' => "นานครั้งละ 2-3 นาที (ไม่เกิน 15 นาที) หรือนั่งพักแล้วดีขึ้น?"],
                'B7_2'   => ['frame' => '7.2',   'type' => 'S', 'q' => "ปวดแสบเวลาหิวหรือหลังกินข้าวอิ่ม? หรือ ปวดตอนดึก?"],
                'B7_3'   => ['frame' => '7.3',   'type' => 'S', 'q' => "แสบตรงยอดอก? แสบร้าวจากยอดอกขึ้นไปถึงคอหอย? หรือ เรอเปรี้ยวขึ้นคอหอย?"],
                'B7_4'   => ['frame' => '7.4',   'type' => 'S', 'q' => "มีลมในท้อง? หรือ ท้องอืดท้องเฟ้อ?"],
                'B8'     => ['frame' => '8',     'type' => 'S', 'q' => 'ปวดเสียวอยู่แถบหนึ่งตรงบริเวณที่เคยเป็นงูสวัด?'],
                'B9'     => ['frame' => '9',     'type' => 'S', 'q' => 'ปวดแสบร้อนอยู่แถบหนึ่ง?'],
                'B10'    => ['frame' => '10',    'type' => 'T', 'q' => "• ยาแก้ปวด พาราเซตามอล (ย1.2)\n• ยาแก้ท้องอืดท้องเฟ้อ (ย13)/ยาลดกรด (ย14.1) ถ้าจุกแน่นท้อง\n• ไดอะซีแพม (ย17.1) ถ้าเครียด/กังวล\n• ประคบด้วยกระเป๋าน้ำร้อน\n⊕ ถ้าปวดติดต่อกันนานเกิน 6 ชั่วโมง/กดเจ็บ/ซีด/ถ่ายดำ/ลูกนั่งจะเป็นลม/อาเจียนมาก/เป็น ๆ หาย ๆ เรื้อรัง"],
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
                'B1'     => [['refer_diagram_46_frame_1', null], [null, 'B2']],
                'B2'     => [['refer_diagram_45_frame_2', null], [null, 'B3']],
                'B3'     => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'   => [['appendicitis_acute', null], [null, 'B3_2']],
                'B3_2'   => [['perforated_peptic_ulcer', null], [null, 'B3_3']],
                'B3_3'   => [['abdominal_trauma_hemorrhage', null], [null, 'B3_4']],
                'B3_4'   => [['peritonitis_pancreatitis', null], [null, 'B3_5']],
                'B3_5'   => [['gastrointestinal_obstruction', null], [null, 'B3_6']],
                'B3_6'   => [['gastric_rupture_infant', null], ['aortic_dissection_severe_other', null]],
                'B4'     => [['refer_diagram_47_11_54_55', null], [null, 'B5']],
                'B5'     => [['refer_diagram_44_frame_1', null], [null, 'B6']],
                'B6'     => [[null, 'B6_1'], [null, 'B7']],
                'B6_1'   => [['intestinal_obstruction_colic', null], [null, 'B6_2']],
                'B6_2'   => [['gallstone_cholecystitis', null], [null, 'B6_3']],
                'B6_3'   => [['ureteral_stone', null], ['antispasmodic_general_colic', null]],
                'B7'     => [[null, 'B7_1'], [null, 'B8']],
                'B7_1'   => [[null, 'B7_1_1'], [null, 'B7_2']],
                'B7_1_1' => [['angina_pectoris_transient', null], ['myocardial_infarction_urgent', null]],
                'B7_2'   => [['dyspepsia_gastritis_peptic_ulcer', null], [null, 'B7_3']],
                'B7_3'   => [['gerd_reflux_esophagitis', null], [null, 'B7_4']],
                'B7_4'   => [['dyspepsia_flatulence', null], [null, 'B8']],
                'B8'     => [['postherpetic_neuralgia', null], [null, 'B9']],
                'B9'     => [['herpes_zoster_early', null], [null, 'B10']],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // กรอบที่ 10 (Terminal Text Box)
            $addChoice('B10', 'การดูแลรักษาตามกรอบที่ 10', null, 'unspecified_abdominal_pain_care', 1);

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'refer_diagram_46_frame_1' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 46 ปวดท้องน้อยในผู้หญิงวัยเจริญพันธุ์ กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00046'],
                ],
                'refer_diagram_45_frame_2' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 45 ปวดท้องแบบเป็น ๆ หาย ๆ กรอบที่ 2
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00045'],
                ],
                'appendicitis_acute' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ไส้ติ่งอักเสบ (46)
• งดน้ำและอาหาร
• ให้น้ำเกลือ
⊕ ด่วน
NOTE,
                    'refs'       => ['46'],
                    'diagrams'   => [],
                ],
                'perforated_peptic_ulcer' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
กระเพาะอาหารทะลุ/แผลเพ็ปติกทะลุ (52)
• งดน้ำและอาหาร
• ให้น้ำเกลือ
⊕ ด่วน
NOTE,
                    'refs'       => ['52'],
                    'diagrams'   => [],
                ],
                'abdominal_trauma_hemorrhage' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ตับ/ม้าม/ไตฉีกขาด หรือเลือดตกใน (56)
• งดน้ำและอาหาร
• ให้น้ำเกลือ
⊕ ด่วน
NOTE,
                    'refs'       => ['56'],
                    'diagrams'   => [],
                ],
                'peritonitis_pancreatitis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
เยื่อบุช่องท้องอักเสบ (47)/ตับอ่อนอักเสบ (48)
• งดน้ำและอาหาร
• ให้น้ำเกลือ
⊕ ด่วน
NOTE,
                    'refs'       => ['47', '48'],
                    'diagrams'   => [],
                ],
                'gastrointestinal_obstruction' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
กระเพาะลำไส้อุดกั้น (54)
• งดน้ำและอาหาร
• ให้น้ำเกลือ
⊕ ด่วน
NOTE,
                    'refs'       => ['54'],
                    'diagrams'   => [],
                ],
                'gastric_rupture_infant' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
กระเพาะอาหารแตก (53)
• งดน้ำและอาหาร
• ให้น้ำเกลือ
⊕ ด่วน
NOTE,
                    'refs'       => ['53'],
                    'diagrams'   => [],
                ],
                'aortic_dissection_severe_other' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นภาวะเลือดเซาะผนังหลอดเลือดแดงใหญ่/หลอดเลือดแดงใหญ่โป่งพองแตก (92.1)/สาเหตุร้ายแรงอื่น ๆ
• งดน้ำและอาหาร
• ให้น้ำเกลือ
NOTE,
                    'refs'       => ['92.1'],
                    'diagrams'   => [],
                ],
                'refer_diagram_47_11_54_55' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 47 ท้องเดิน กรอบที่ 1
ดูแผนภูมิที่ 11 ดีซ่าน กรอบที่ 3
ดูแผนภูมิที่ 54 ขัดเบา กรอบที่ 1.1
ดูแผนภูมิที่ 55 ปัสสาวะเป็นเลือด กรอบที่ 1.1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00047', '00011', '00054', '00055'],
                ],
                'refer_diagram_44_frame_1' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 44 ปวดท้องร่วมกับไข้ กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00044'],
                ],
                'intestinal_obstruction_colic' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
กระเพาะหรือลำไส้อุดกั้น (54)
• งดน้ำและอาหาร
• ให้น้ำเกลือ
⊕ ด่วน
NOTE,
                    'refs'       => ['54'],
                    'diagrams'   => [],
                ],
                'gallstone_cholecystitis' => [
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
                'antispasmodic_general_colic' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• แอนติสปาสโมดิก (ย20)
⊕ ถ้าปวดนานเกิน 6 ชั่วโมง หรือปวดซ้ำ ๆ บ่อยครั้ง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'angina_pectoris_transient' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
หัวใจขาดเลือดชั่วขณะ (96)
• หลีกเลี่ยงสาเหตุกระตุ้น
• อมยาขยายหลอดเลือดหัวใจเวลาเจ็บ
⊕ ภายใน 3 วัน
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
• ยาลดกรด (ย14.1)
• รานิทิดีน (ย14.3)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือถ่ายดำ/ซีด/กลืนลำบาก/อาเจียนมาก/น้ำหนักลด/คลำได้ก้อนในท้อง/ตับโต/อายุเกิน 40 ปี/กำเริบซ้ำ
NOTE,
                    'refs'       => ['49', '50', '51'],
                    'diagrams'   => [],
                ],
                'gerd_reflux_esophagitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคกรดไหลย้อน (49.1)
• ยาลดกรด (ย14.1)
• รานิทิดีน (ย14.3)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือถ่ายดำ/ซีด/กลืนลำบาก/อาเจียนมาก/น้ำหนักลด/คลำได้ก้อนในท้อง/ตับโต/อายุเกิน 40 ปี/กำเริบซ้ำ
NOTE,
                    'refs'       => ['49.1'],
                    'diagrams'   => [],
                ],
                'dyspepsia_flatulence' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อาหารไม่ย่อย (49)
• ยาแก้ท้องอืดท้องเฟ้อ (ย13)/ยาลดกรด (ย14.1)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือถ่ายดำ/ซีด/กลืนลำบาก/อาเจียนมาก/น้ำหนักลด/คลำได้ก้อนในท้อง/ตับโต/เป็น ๆ หาย ๆ บ่อย
NOTE,
                    'refs'       => ['49'],
                    'diagrams'   => [],
                ],
                'postherpetic_neuralgia' => [
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
                'herpes_zoster_early' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
งูสวัด (188) ระยะแรกเริ่ม
• ยาแก้ปวด (ย1)
• รักษาแบบงูสวัด เมื่อมีตุ่มขึ้นเป็นแนวยาว
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือปวดรุนแรง
NOTE,
                    'refs'       => ['188'],
                    'diagrams'   => [],
                ],
                'unspecified_abdominal_pain_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ปวด พาราเซตามอล (ย1.2)
• ยาแก้ท้องอืดท้องเฟ้อ (ย13)/ยาลดกรด (ย14.1) ถ้าจุกแน่นท้อง
• ไดอะซีแพม (ย17.1) ถ้าเครียด/กังวล
• ประคบด้วยกระเป๋าน้ำร้อน
⊕ ถ้าปวดติดต่อกันนานเกิน 6 ชั่วโมง/กดเจ็บ/ซีด/ถ่ายดำ/ลูกนั่งจะเป็นลม/อาเจียนมาก/เป็น ๆ หาย ๆ เรื้อรัง
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
                    'medical_reference' => 'แผนภูมิที่ 43',
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

        $this->command->info('สร้างแผนภูมิที่ 43 (ปวดท้อง - ABDOMINAL PAIN) กรอบ 1-10 สำเร็จ');
    }
}
