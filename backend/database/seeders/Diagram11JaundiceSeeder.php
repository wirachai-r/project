<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram11JaundiceSeeder extends Seeder
{
    private const DIAGRAM_ID = '00011';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '2.1', '3', '3.1', '3.1.1', '3.1.2', '3.2', '3.2.1', '3.2.2', '3.2.3', '3.3',
                '4', '4.1', '4.2', '4.3', '4.4', '5', '6', '7'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 11 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 11
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ดีซ่าน/ตาเหลือง (JAUNDICE)',
                'diagram_name_en' => 'Jaundice',
                'description' => 'มีอาการตาเหลือง ตัวเหลือง ปัสสาวะเหลืองเหมือนขมิ้น (ใส่หลอดแก้วเขย่าจะเห็นฟองสีเหลืองๆ) อาจมีอาการคันตามตัว สาเหตุที่พบบ่อย ตับอักเสบจากไวรัส (38) ตับแข็ง (44) ถุงน้ำดีอักเสบ (40) เล็ปโตสไปโรซิส (227) มาลาเรีย (224) ไทฟอยด์ (37) ถ้าอาการไม่ชัดเจน ควรส่งไปรักษาที่โรงพยาบาล ภายใน 1 สัปดาห์ ดีซ่านในทารกแรกเกิด ดูแผนภูมิที่ 12',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 11
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'ไม่ค่อยรู้สึกตัว? ช็อก? หรือ อาเจียนเป็นเลือด?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'ซีดเหลือง เป็นๆ หายๆ อยู่ประจำ?'],
                'B2_1'    => ['frame' => '2.1',   'type' => 'S', 'q' => 'ม้ามโต? หรือ หน้าตาแปลกมาตั้งแต่เด็ก?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'มีไข้?'],
                'B3_1'    => ['frame' => '3.1',   'type' => 'S', 'q' => 'กดเจ็บบริเวณชายโครงขวา?'],
                'B3_1_1'  => ['frame' => '3.1.1', 'type' => 'S', 'q' => 'กดเจ็บเพียงจุดเล็กๆ จุดเดียว? หรือ มีประวัติถ่ายเป็นมูกเลือด?'],
                'B3_1_2'  => ['frame' => '3.1.2', 'type' => 'S', 'q' => 'จับไข้หนาวสั่นมาก?'],
                'B3_2'    => ['frame' => '3.2',   'type' => 'S', 'q' => 'มีไข้เกิน 7 วัน? จับไข้หนาวสั่นมาก? หรือ มีจุดแดงจ้ำเขียว?'],
                'B3_2_1'  => ['frame' => '3.2.1', 'type' => 'S', 'q' => 'อยู่หรือกลับจากดงมาลาเรียในระยะหลายเดือนที่ผ่านมา?'],
                'B3_2_2'  => ['frame' => '3.2.2', 'type' => 'S', 'q' => 'ตับโต บีบน่องรู้สึกปวดมาก และมีอาชีพที่ต้องย่ำน้ำ?'],
                'B3_2_3'  => ['frame' => '3.2.3', 'type' => 'S', 'q' => 'มีไข้สูงตลอดเวลา และม้ามโต? หรือ อยู่ในละแวกที่มีการระบาดของไทฟอยด์?'],
                'B3_3'    => ['frame' => '3.3',   'type' => 'S', 'q' => 'หน้าตาแปลก? ซีดเหลืองมาตั้งแต่เด็ก? หรือ ม้ามโต?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'น้ำหนักลดฮวบ? หรือ ท้องบวม?'],
                'B4_1'    => ['frame' => '4.1',   'type' => 'S', 'q' => 'คลำได้ก้อนแข็งผิวขรุขระที่ใต้ชายโครงขวา?'],
                'B4_2'    => ['frame' => '4.2',   'type' => 'S', 'q' => 'ตาเหลืองจัดและอุจจาระสีซีดขาวกว่าปกติ?'],
                'B4_3'    => ['frame' => '4.3',   'type' => 'S', 'q' => 'มีจุดแดงรูปแมงมุม? ฝ่ามือแดง? หรือ มีประวัติดื่มแอลกอฮอล์จัด?'],
                'B4_4'    => ['frame' => '4.4',   'type' => 'S', 'q' => 'พบในคนอีสาน อายุมากกว่า 40 ปี?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'มีจุดแดงรูปแมงมุม? ฝ่ามือแดง? หรือมีประวัติดื่มแอลกอฮอล์จัด?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => 'มีประวัติกินยาก่อนมีอาการ?'],
                'B7'      => ['frame' => '7',     'type' => 'S', 'q' => 'ตับโตผิวเรียบนุ่ม? หรือ มีอาการคล้ายไข้หวัดนำมาก่อน 2-14 วัน?'],
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
                'B1'     => [['severe_liver_failure', null], [null, 'B2']],
                'B1'     => [['severe_liver_failure', null], [null, 'B2']],
                'B2'     => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'   => [['thalassemia', null], ['hemolytic_anemia_24h', null]],
                'B3'     => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'   => [[null, 'B3_1_1'], [null, 'B3_2']],
                'B3_1_1' => [['amebic_liver_abscess', null], [null, 'B3_1_2']],
                'B3_1_2' => [['cholangitis_24h', null], ['cholecystitis_melioidosis_24h', null]],
                'B3_2'   => [[null, 'B3_2_1'], [null, 'B3_3']],
                'B3_2_1' => [['malaria_24h', null], [null, 'B3_2_2']],
                'B3_2_2' => [['leptospirosis_24h', null], [null, 'B3_2_3']],
                'B3_2_3' => [['typhoid_24h', null], ['septicemia_melioidosis_24h', null]],
                'B3_3'   => [['thalassemia_24h', null], ['viral_hepatitis_3d', null]],
                'B4'     => [[null, 'B4_1'], [null, 'B5']],
                'B4_1'   => [['liver_cancer', null], [null, 'B4_2']],
                'B4_2'   => [['pancreatic_or_bileduct_cancer', null], [null, 'B4_3']],
                'B4_3'   => [['severe_cirrhosis', null], [null, 'B4_4']],
                'B4_4'   => [['opisthorchiasis_melioidosis', null], ['unexplained_jaundice_1wk', null]],
                'B5'     => [['cirrhosis', null], [null, 'B6']],
                'B6'     => [['drug_induced_jaundice', null], [null, 'B7']],
                'B7'     => [['viral_hepatitis', null], ['unexplained_jaundice_investigate', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'severe_liver_failure' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => '⊕ ด่วน อาจเป็นตับแข็ง (44) ระยะรุนแรง/ตับวายจากพิษเห็ด (219.5)/สาเหตุร้ายแรงอื่นๆ',
                    'refs'       => ['44', '219.5'],
                    'diagrams'   => [],
                ],
                'thalassemia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ทาลาสซีเมีย (105)
• ชันสูตรเพิ่มเติม
• ให้กินกรดโฟลิก
• ห้ามให้ยาบำรุงโลหิต
⊕ ถ้ามีไข้/ซีดมาก หรือโรคติดเชื้ออื่นๆ
NOTE,
                    'refs'       => ['105'],
                    'diagrams'   => [],
                ],
                'hemolytic_anemia_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => 'โลหิตจางจากเม็ดเลือดแดงแตก (101)
⊕ ภายใน 24 ชั่วโมง',
                    'refs'       => ['101'],
                    'diagrams'   => [],
                ],
                'amebic_liver_abscess' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => 'ฝีตับอะมีบา (39)
⊕ ภายใน 24 ชั่วโมง',
                    'refs'       => ['39'],
                    'diagrams'   => [],
                ],
                'cholangitis_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => 'ท่อน้ำดีอักเสบ (41)
⊕ ภายใน 24 ชั่วโมง',
                    'refs'       => ['41'],
                    'diagrams'   => [],
                ],
                'cholecystitis_melioidosis_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => 'ถุงน้ำดีอักเสบ (40)/เมลิออยโดซิส (229.2)
⊕ ภายใน 24 ชั่วโมง',
                    'refs'       => ['40', '229.2'],
                    'diagrams'   => [],
                ],
                'malaria_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => 'มาลาเรีย (224)
⊕ ภายใน 24 ชั่วโมง',
                    'refs'       => ['224'],
                    'diagrams'   => [],
                ],
                'leptospirosis_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => 'เล็ปโตสไปโรซิส (227)
⊕ ภายใน 24 ชั่วโมง',
                    'refs'       => ['227'],
                    'diagrams'   => [],
                ],
                'typhoid_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => 'ไทฟอยด์ (37)
⊕ ภายใน 24 ชั่วโมง',
                    'refs'       => ['37'],
                    'diagrams'   => [],
                ],
                'septicemia_melioidosis_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => '⊕ ภายใน 24 ชั่วโมง อาจเป็นโลหิตเป็นพิษ (228)/เมลิออยโดซิส (229.2)/สาเหตุร้ายแรงอื่นๆ',
                    'refs'       => ['228', '229.2'],
                    'diagrams'   => [],
                ],
                'thalassemia_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => 'ทาลาสซีเมีย (105)
⊕ ภายใน 24 ชั่วโมง',
                    'refs'       => ['105'],
                    'diagrams'   => [],
                ],
                'viral_hepatitis_3d' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => '⊕ ภายใน 3 วัน อาจเป็นตับอักเสบจากไวรัส (38)/อื่นๆ',
                    'refs'       => ['38'],
                    'diagrams'   => [],
                ],
                'liver_cancer' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => 'มะเร็งตับ (45)
⊕ ภายใน 1 สัปดาห์',
                    'refs'       => ['45'],
                    'diagrams'   => [],
                ],
                'pancreatic_or_bileduct_cancer' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => 'มะเร็งตับอ่อน (237.14)/มะเร็งลำไส้เล็ก (237.12)
⊕ ภายใน 1 สัปดาห์',
                    'refs'       => ['237.14', '237.12'],
                    'diagrams'   => [],
                ],
                'severe_cirrhosis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => 'ตับแข็ง (44) ระยะรุนแรง
⊕ ภายใน 1 สัปดาห์',
                    'refs'       => ['44'],
                    'diagrams'   => [],
                ],
                'opisthorchiasis_melioidosis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => 'โรคพยาธิใบไม้ตับ (45)/เมลิออยโดซิส (229.2)
⊕ ภายใน 1 สัปดาห์',
                    'refs'       => ['45', '229.2'],
                    'diagrams'   => [],
                ],
                'unexplained_jaundice_1wk' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => '⊕ ภายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ',
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'cirrhosis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => 'ตับแข็ง (44)
⊕ ภายใน 1 สัปดาห์',
                    'refs'       => ['44'],
                    'diagrams'   => [],
                ],
                'drug_induced_jaundice' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากยา*
• ให้หยุดยา และแนะนำให้กลับไปพบแพทย์ที่รักษาอยู่เดิม
* ยาที่ทำให้เกิดอาการดีซ่าน/ตับอักเสบ เช่น พาราเซตามอล (ย1.2) เตตราไซคลีน (ย4.5) อีริโทรไมซิน (ย4.4) ไอเอ็นเอช (ย4.13) ไรแฟมพิซิน (ย4.14) คีโตโคนาโซล (ย4.9) เอสโทรเจน (ยาเม็ดคุมกำเนิด) คลอร์โพรมาซีน ไทโอยูราซิล เมทิมาโซล แดปโซน เอทีที อัลโลพูรินอล เมทิลโดพา เวราพามิล เป็นต้น
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'viral_hepatitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ตับอักเสบจากไวรัส (38)
• ชันสูตรเพิ่มเติม
• รักษาตามอาการ
  - พักผ่อน ห้ามทำงานหนัก ควรหยุดเรียน หรือหยุดทำงาน
  - ดื่มน้ำมากๆ
  - ถ้าเบื่ออาหาร กินอาหารหวาน ๆ และอาหารพวกโปรตีน
  - ห้ามดื่มแอลกอฮอล์
  - ควรแยกใช้ถ้วย ชาม จาน ต่างหากจากคนอื่น
  - ควรถ่ายลงส้วม และล้างมือให้สะอาดหลังถ่าย
⊕ ถ้าปวดท้องมาก อาเจียนมาก กินไม่ได้ บวม น้ำหนักตัวลดฮวบ ซีดมาก หรือดีซ่านไม่จางลงใน 1 สัปดาห์
ถ้าดีขึ้นควรพักผ่อนต่ออีก 2-4 สัปดาห์
NOTE,
                    'refs'       => ['38'],
                    'diagrams'   => [],
                ],
                'unexplained_jaundice_investigate' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => '⊕ ภายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ',
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
                    'medical_reference' => 'แผนภูมิที่ 11',
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

        $this->command->info('สร้างแผนภูมิที่ 11 (ดีซ่าน/ตาเหลือง - JAUNDICE) กรอบ 1-7 สำเร็จ');
    }
}
