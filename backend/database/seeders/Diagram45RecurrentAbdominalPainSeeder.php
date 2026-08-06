<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram45RecurrentAbdominalPainSeeder extends Seeder
{
    private const DIAGRAM_ID = '00045';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3',
                '4', '4.1', '4.2', '4.3', '4.4',
                '5', '5.1', '5.2', '5.3',
                '6', '6.1', '6.2',
                '7', '8', '9'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 45 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 45
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ปวดท้องแบบเป็น ๆ หาย ๆ',
                'diagram_name_en' => 'Recurrent Abdominal Pain',
                'description' => 'มีอาการปวดท้องเป็นครั้งคราว หรือเป็นๆ หายๆ หรือเป็นเรื้อรัง หลายวันหรือเป็นสัปดาห์ หรือนานเป็นแรมเดือนแรมปี สาเหตุที่พบบ่อย อาหารไม่ย่อย (49) โรคกรดไหลย้อน (49.1) กระเพาะอาหารอักเสบ (50) แผลเพ็ปติก (51) ปวดประจำเดือน (150) นิ่วท่อไต (139) นิ่วถุงน้ำดี (40) ถ้าเป็นเรื้อรังในเด็ก อาจมีสาเหตุจากโรคพยาธิไส้เดือน (230) เด็กไม่อยากไปโรงเรียน (90) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 9 ถ้าเป็นเรื้อรังในเด็กให้ยาถ่ายพยาธิไส้เดือน (ย6) ถ้ามีอาการปวดท้องน้อยที่พบในผู้หญิงวัยเจริญพันธุ์ ดูแผนภูมิที่ 46',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 45
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => 'ปวดรุนแรงหรือปวดไม่เหมือนที่เคยเป็น? หรือ มีไข้?'],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => "มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้\n☐ เบื่ออาหาร?\n☐ น้ำหนักลดซูบ?\n☐ มีก้อนแข็งผิวขรุขระในช่องท้อง?\n☐ ถ่ายเป็นมูกเลือดนานกว่า 2 สัปดาห์?\n☐ มีอาการท้องผูกสลับกับท้องเสียนานกว่า 1 เดือน?\n☐ มีเลือดออกทางช่องคลอดกะปริบกะปรอย?"],
                'B3'   => ['frame' => '3',   'type' => 'S', 'q' => "ท้องเดิน? ถ่ายเป็นมูกเลือด? ขัดเบา? ซีด? หรือ ดีซ่าน?"],
                'B4'   => ['frame' => '4',   'type' => 'S', 'q' => 'ปวดตรงใต้ลิ้นปี่ หรือยอดอก?'],
                'B4_1' => ['frame' => '4.1', 'type' => 'S', 'q' => "ปวดเค้นหรือจุกแน่น และร้าวไปที่ขากรรไกร นานครั้งละ 2-3 นาที (ไม่เกิน 15 นาที)?"],
                'B4_2' => ['frame' => '4.2', 'type' => 'S', 'q' => "ปวดแสบเวลาหิว หรือหลังกินข้าวอิ่ม? หรือ ปวดตอนดึก?"],
                'B4_3' => ['frame' => '4.3', 'type' => 'S', 'q' => "แสบตรงยอดอก? แสบร้าวจากยอดอกขึ้นไปถึงคอหอย? หรือ เรอเปรี้ยวขึ้นคอหอย?"],
                'B4_4' => ['frame' => '4.4', 'type' => 'S', 'q' => 'มีลมในท้อง?'],
                'B5'   => ['frame' => '5',   'type' => 'S', 'q' => 'ปวดบิด เป็นพักๆ?'],
                'B5_1' => ['frame' => '5.1', 'type' => 'S', 'q' => 'บริเวณใต้ชายโครงขวา หลังกินอาหารมันๆ?'],
                'B5_2' => ['frame' => '5.2', 'type' => 'S', 'q' => 'ปวดที่ท้องน้อยหรือสีข้าง และร้าวไปที่อัณฑะหรือช่องคลอดข้างเดียวกัน?'],
                'B5_3' => ['frame' => '5.3', 'type' => 'S', 'q' => 'ปวดท้องน้อยเวลามีประจำเดือน?'],
                'B6'   => ['frame' => '6',   'type' => 'S', 'q' => 'ในเด็ก?'],
                'B6_1' => ['frame' => '6.1', 'type' => 'S', 'q' => "เด็กไม่อยากไปโรงเรียน หรือเรียนหนัก (เด็กที่เพิ่งเข้าเรียนใน 1-2 ปีแรก)?"],
                'B6_2' => ['frame' => '6.2', 'type' => 'S', 'q' => "ถ่ายหรืออาเจียนเป็นตัวไส้เดือน? หรือ สงสัยเป็นพยาธิ?"],
                'B7'   => ['frame' => '7',   'type' => 'S', 'q' => "ในคนที่ทำงานเกี่ยวกับตะกั่ว (เช่น โรงงานแบตเตอรี่/ทำสี) หรือ เด็กเล่นสีหรือสัมผัสสารตะกั่ว?"],
                'B8'   => ['frame' => '8',   'type' => 'S', 'q' => "มีความเครียด วิตกกังวล ซึมเศร้า หรือนอนไม่หลับ?"],
                'B9'   => ['frame' => '9',   'type' => 'T', 'q' => "• ยาแก้ปวด พาราเซตามอล (ย1.2)\n• ยาระบาย (ย16) ถ้าท้องผูก\n⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือปวดรุนแรง/น้ำหนักลดซูบ/ซีด/ดีซ่าน/ถ่ายดำ"],
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
                'B1'   => [['refer_diagram_43_44', null], [null, 'B2']],
                'B2'   => [['suspected_abdominal_cancer', null], [null, 'B3']],
                'B3'   => [['refer_diagram_48_49_54_8_11', null], [null, 'B4']],
                'B4'   => [[null, 'B4_1'], [null, 'B5']],
                'B4_1' => [['angina_pectoris_transient', null], [null, 'B4_2']],
                'B4_2' => [['peptic_ulcer_dyspepsia', null], [null, 'B4_3']],
                'B4_3' => [['gerd_reflux_dyspepsia', null], [null, 'B4_4']],
                'B4_4' => [['dyspepsia_flatulence_recurrent', null], [null, 'B5']],
                'B5'   => [[null, 'B5_1'], [null, 'B6']],
                'B5_1' => [['gallstone_cholecystitis', null], [null, 'B5_2']],
                'B5_2' => [['ureteral_stone', null], [null, 'B5_3']],
                'B5_3' => [['dysmenorrhea', null], ['antispasmodic_general_colic', null]],
                'B6'   => [[null, 'B6_1'], [null, 'B7']],
                'B6_1' => [['school_refusal_pediatric', null], [null, 'B6_2']],
                'B6_2' => [['ascariasis_worm_infection', null], [null, 'B7']],
                'B7'   => [['lead_poisoning', null], [null, 'B8']],
                'B8'   => [['ibs_anxiety_depression', null], [null, 'B9']],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // กรอบที่ 9 (Terminal Text Box)
            $addChoice('B9', 'การดูแลรักษาตามกรอบที่ 9', null, 'unspecified_recurrent_abdominal_pain_care', 1);

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'refer_diagram_43_44' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 43 ปวดท้อง กรอบที่ 3
ดูแผนภูมิที่ 44 ปวดท้องร่วมกับมีไข้ กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00043', '00044'],
                ],
                'suspected_abdominal_cancer' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งกระเพาะอาหาร (237.11)/มะเร็งลำไส้ใหญ่ (237.13)/มะเร็งตับ (45)/มะเร็งรังไข่ (237.5)/มะเร็งปากมดลูก (237.3)/มะเร็งตับอ่อน (237.14)
NOTE,
                    'refs'       => ['237.11', '237.13', '45', '237.5', '237.3', '237.14'],
                    'diagrams'   => [],
                ],
                'refer_diagram_48_49_54_8_11' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 48 ท้องเดินเรื้อรัง กรอบที่ 1
ดูแผนภูมิที่ 49 ถ่ายเป็นมูกเลือด กรอบที่ 1
ดูแผนภูมิที่ 54 ขัดเบา กรอบที่ 1.1
ดูแผนภูมิที่ 8 ซีด กรอบที่ 9.1
ดูแผนภูมิที่ 11 ดีซ่าน กรอบที่ 4
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00048', '00049', '00054', '00008', '00011'],
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
                'peptic_ulcer_dyspepsia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
แผลเพ็ปติก (51)/อาหารไม่ย่อย (49)
• ยาลดกรด (ย14.1)
• รานิทิดีน (ย14.3)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือถ่ายดำ/ซีด/กลืนลำบาก/อาเจียนมาก/น้ำหนักลด/คลำได้ก้อนในท้อง/ตับโต/อายุเกิน 40 ปี
NOTE,
                    'refs'       => ['51', '49'],
                    'diagrams'   => [],
                ],
                'gerd_reflux_dyspepsia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคกรดไหลย้อน (49.1)
อาหารไม่ย่อย (49)
• ยาแก้ท้องอืดท้องเฟ้อ (ย13)/ยาลดกรด (ย14.1)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือถ่ายดำ/ซีด/กลืนลำบาก/อาเจียนมาก/น้ำหนักลด/คลำได้ก้อนในท้อง/ตับโต/อายุเกิน 40 ปี
NOTE,
                    'refs'       => ['49.1', '49'],
                    'diagrams'   => [],
                ],
                'dyspepsia_flatulence_recurrent' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ท้องอืดท้องเฟ้อ (ย13)/ยาลดกรด (ย14.1)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือถ่ายดำ/ซีด/กลืนลำบาก/อาเจียนมาก/น้ำหนักลด/คลำได้ก้อนในท้อง/ตับโต/อายุเกิน 40 ปี
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'gallstone_cholecystitis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
นิ่วถุงน้ำดี (40)
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
                'dysmenorrhea' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปวดประจำเดือน (150)
• ยาแก้ปวด (ย1) หรือ แอนติสปาสโมดิก (ย20)
⊕ ถ้าปวดรุนแรงหรือประจำเดือนผิดปกติ หรือเริ่มปวดครั้งแรกเมื่ออายุมากกว่า 25 ปี
NOTE,
                    'refs'       => ['150'],
                    'diagrams'   => [],
                ],
                'antispasmodic_general_colic' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• แอนติสปาสโมดิก (ย20)
⊕ ถ้าไม่ดีขึ้นใน 6 ชั่วโมง หรือปวดซ้ำๆ บ่อยครั้ง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'school_refusal_pediatric' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เด็กไม่อยากไปโรงเรียน (90)
• ให้ความมั่นใจแก่พ่อแม่เด็กว่าไม่ใช่สาเหตุร้ายแรง และควรให้เด็กไปโรงเรียนตามปกติ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['90'],
                    'diagrams'   => [],
                ],
                'ascariasis_worm_infection' => [
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
                'lead_poisoning' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นตะกั่วเป็นพิษ (220)
NOTE,
                    'refs'       => ['220'],
                    'diagrams'   => [],
                ],
                'ibs_anxiety_depression' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคลำไส้แปรปรวน (33)/โรควิตกกังวล/โรคกังวลทั่วไป (88)/โรคอารมณ์แปรปรวน/โรคซึมเศร้า (88.2)
• ยาทางจิตประสาท (ย17)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือน้ำหนักลด
NOTE,
                    'refs'       => ['33', '88', '88.2'],
                    'diagrams'   => [],
                ],
                'unspecified_recurrent_abdominal_pain_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ปวด พาราเซตามอล (ย1.2)
• ยาระบาย (ย16) ถ้าท้องผูก
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือปวดรุนแรง/น้ำหนักลดซูบ/ซีด/ดีซ่าน/ถ่ายดำ
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
                    'medical_reference' => 'แผนภูมิที่ 45',
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

        $this->command->info('สร้างแผนภูมิที่ 45 (ปวดท้องแบบเป็น ๆ หาย ๆ) กรอบ 1-9 สำเร็จ');
    }
}
