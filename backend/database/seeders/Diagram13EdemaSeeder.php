<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram13EdemaSeeder extends Seeder
{
    private const DIAGRAM_ID = '00013';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '2.1', '2.2', '3', '3.1', '3.2',
                '4', '4.1', '4.2', '5', '6', '7', '8',
                '9', '10', '11', '11.1', '12'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 13 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 13
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'บวมทั่วไป (EDEMA/SWELLING)',
                'diagram_name_en' => 'Edema / Swelling',
                'description' => 'มีอาการเท้าบวมทั้ง 2 ข้าง ใช้นิ้วกดจะมีรอยบุ๋มอยู่นานกว่าจะหาย บางรายอาจมีอาการหน้าบวม หนังตาบวม และท้องบวม (ท้องมาน) ร่วมด้วย ผู้ป่วยอาจรู้สึกตึงตามปลายมือปลายเท้า แหวนคับ น้ำหนักขึ้น สาเหตุที่พบบ่อย การยืนหรือห้อยเท้านาน ๆ หญิงตั้งครรภ์ (154) ครรภ์เป็นพิษ (155) บวมจากยา ตับแข็ง (44) โรคไตเนโฟรติก (135) หน่วยไตอักเสบเฉียบพลัน (136) หัวใจวาย (98) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 12 ขาหรือเท้าบวมข้างเดียว ดูแผนภูมิที่ 14 ขาบวมข้างเดียว',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 13
            $boxes = [
                'B1'     => ['frame' => '1',    'type' => 'S', 'q' => 'หอบเหนื่อย? นอนราบไม่ได้? หรือ ใช้เครื่องฟังปอดมีเสียงกรอบแกรบ (crepitation)?'],
                'B2'     => ['frame' => '2',    'type' => 'S', 'q' => 'หน้าบวม? หรือ หนังตาบวม?'],
                'B2_1'   => ['frame' => '2.1',  'type' => 'S', 'q' => 'ปัสสาวะสีแดงเหมือนน้ำล้างเนื้อ?'],
                'B2_2'   => ['frame' => '2.2',  'type' => 'S', 'q' => 'ปัสสาวะใส และตรวจพบสารไข่ขาว 3+ ถึง 4+?'],
                'B3'     => ['frame' => '3',    'type' => 'S', 'q' => 'ดีซ่าน? หรือ ท้องบวม?'],
                'B3_1'   => ['frame' => '3.1',  'type' => 'S', 'q' => 'มีก้อนแข็งผิวขรุขระที่ใต้ชายโครงขวา? หรือ อ่อนเพลียผอมแห้ง?'],
                'B3_2'   => ['frame' => '3.2',  'type' => 'S', 'q' => 'มีจุดแดงรูปแมงมุม? ฝ่ามือแดง? หรือ มีประวัติดื่มแอลกอฮอล์จัด?'],
                'B4'     => ['frame' => '4',    'type' => 'S', 'q' => 'พบในหญิงตั้งครรภ์?'],
                'B4_1'   => ['frame' => '4.1',  'type' => 'S', 'q' => 'ความดันโลหิตช่วงบน >= 140 หรือช่วงล่าง >= 90 มม.ปรอท? หรือ ตรวจพบสารไข่ขาวในปัสสาวะ?'],
                'B4_2'   => ['frame' => '4.2',  'type' => 'S', 'q' => 'อาการทั่วไปแข็งแรงดี?'],
                'B5'     => ['frame' => '5',    'type' => 'S', 'q' => 'พบในหญิงหลังคลอด มีอาการชาปลายมือปลายเท้า หรือเท้าไม่มีแรงลูกเดิน?'],
                'B6'     => ['frame' => '6',    'type' => 'S', 'q' => 'พบในทารก มีอาการร้องเสียงแหบ หรือหอบตัวเขียว?'],
                'B7'     => ['frame' => '7',    'type' => 'S', 'q' => 'พบในเด็กอายุมากกว่า 1 ปี ซีด? หรือ ผมออกสีน้ำตาลแดง?'],
                'B8'     => ['frame' => '8',    'type' => 'S', 'q' => 'มีประวัติกินยาชุด ยาลูกกลอน ยาสเตียรอยด์ ยาแก้ปวดข้อ หรือยาอื่นๆ?'],
                'B9'     => ['frame' => '9',    'type' => 'S', 'q' => 'มีประวัติเป็นเบาหวาน โรคไต หรือความดันโลหิตสูงมานาน?'],
                'B10'    => ['frame' => '10',   'type' => 'S', 'q' => 'พบในผู้หญิงก่อนหรือขณะมีประจำเดือน?'],
                'B11'    => ['frame' => '11',   'type' => 'S', 'q' => 'บวมเวลายืนหรือห้อยเท้านานๆ และหลังตื่นนอนจะหายบวม?'],
                'B11_1'  => ['frame' => '11.1', 'type' => 'S', 'q' => 'มีหลอดเลือดขอดที่ขา?'],
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
                'B1'     => [['heart_failure', null], [null, 'B2']],
                'B2'     => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'   => [['acute_glomerulonephritis', null], [null, 'B2_2']],
                'B2_2'   => [['nephrotic_syndrome', null], [null, 'B3']],
                'B3'     => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'   => [['liver_pancreatic_bowel_cancer', null], [null, 'B3_2']],
                'B3_2'   => [['cirrhosis', null], ['unexplained_edema_3_2', null]],
                'B4'     => [[null, 'B4_1'], [null, 'B5']],
                'B4_1'   => [['preeclampsia', null], [null, 'B4_2']],
                'B4_2'   => [['pregnancy_edema', null], ['unexplained_edema_4_2', null]],
                'B5'     => [['postpartum_beriberi', null], [null, 'B6']],
                'B6'     => [['infantile_beriberi', null], [null, 'B7']],
                'B7'     => [['kwashiorkor', null], [null, 'B8']],
                'B8'     => [['drug_induced_edema', null], [null, 'B9']],
                'B9'     => [['chronic_renal_failure', null], [null, 'B10']],
                'B10'    => [['premenstrual_edema', null], [null, 'B11']],
                'B11'    => [[null, 'B11_1'], ['edema_care_frame12', null]],
                'B11_1'  => [['varicose_veins', null], ['prolonged_standing_edema', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'heart_failure' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะหัวใจวาย (98)
⊕ ด่วน
  ฉีดยาขับปัสสาวะก่อนส่ง
NOTE,
                    'refs'       => ['98'],
                    'diagrams'   => [],
                ],
                'acute_glomerulonephritis' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
หน่วยไตอักเสบเฉียบพลัน (136)
⊕ ภายใน 3 วัน
NOTE,
                    'refs'       => ['136'],
                    'diagrams'   => [],
                ],
                'nephrotic_syndrome' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
โรคไตเนโฟรติก (135)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['135'],
                    'diagrams'   => [],
                ],
                'liver_pancreatic_bowel_cancer' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งตับ (45)/มะเร็งตับอ่อน (237.14)/มะเร็งลำไส้เล็ก (237.12)/อื่นๆ
NOTE,
                    'refs'       => ['45', '237.14', '237.12'],
                    'diagrams'   => [],
                ],
                'cirrhosis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ตับแข็ง (44)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['44'],
                    'diagrams'   => [],
                ],
                'unexplained_edema_3_2' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'preeclampsia' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
ครรภ์เป็นพิษ (155)
⊕ ภายใน 3 วัน
⊕ ด่วน ถ้าชัก
NOTE,
                    'refs'       => ['155'],
                    'diagrams'   => [],
                ],
                'pregnancy_edema' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
บวมจากการตั้งครรภ์ (154)
• ไม่มีอันตรายร้ายแรง
• ไม่ต้องให้ยารักษา
• อย่ายืนมากเดินมาก
NOTE,
                    'refs'       => ['154'],
                    'diagrams'   => [],
                ],
                'unexplained_edema_4_2' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'postpartum_beriberi' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคเหน็บชา (132)
• วิตามินบี 1
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['132'],
                    'diagrams'   => [],
                ],
                'infantile_beriberi' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
โรคเหน็บชาในทารก (132)
⊕ ด่วน
  ฉีดวิตามินบี 1 ก่อนส่ง
NOTE,
                    'refs'       => ['132'],
                    'diagrams'   => [],
                ],
                'kwashiorkor' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
โรคขาดอาหารชนิดควัชชิออร์กอร์ (130)
⊕ ภายใน 3 วัน
NOTE,
                    'refs'       => ['130'],
                    'diagrams'   => [],
                ],
                'drug_induced_edema' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
บวมจากยา*
• หยุดยาที่กิน แต่ถ้ากินยาชุด ยาลูกกลอน หรือสเตียรอยด์ติดต่อกันมานาน ห้ามหยุดยาทันที ควรลดยาลงทีละน้อย
• ตรวจหาสาเหตุของโรคที่เป็นอยู่เดิม และให้การรักษาตามสาเหตุที่พบ
* ยาที่ทำให้เกิดอาการบวม เช่น สเตียรอยด์ (ย12) ยาต้านอักเสบที่ไม่ใช่สเตียรอยด์ (ย2) ยาลดความดันกลุ่มยาต้านแคลเซียม (ย22.3) เอสโทรเจน (ยาเม็ดคุมกำเนิด) ยารักษาเบาหวานกลุ่มกลิทาโซน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'chronic_renal_failure' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ภาวะไตวายเรื้อรัง (134)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['134'],
                    'diagrams'   => [],
                ],
                'premenstrual_edema' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
บวมที่พบร่วมกับประจำเดือน
• ลดอาหารเค็มจัด
• ยาขับปัสสาวะ (ย21)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'varicose_veins' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หลอดเลือดขอดที่ขา (99)
• รักษาตามอาการ
• ใช้ผ้าพันแผลชนิดยืดพันขา
NOTE,
                    'refs'       => ['99'],
                    'diagrams'   => [],
                ],
                'prolonged_standing_edema' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
บวมจากการยืนหรือห้อยเท้านานๆ
• หลีกเลี่ยงการยืน หรือนั่งห้อยเท้านานๆ
⊕ ถ้าไม่หายบวมใน 1 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'edema_care_frame12' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาขับปัสสาวะ (ย21)
• ลดอาหารเค็มจัด
⊕ ถ้าไม่หายบวมใน 1 สัปดาห์ หรือมีไข้/ซีด/จุดแดงจ้ำเขียว/ความดันโลหิตสูง
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
                    'medical_reference' => 'แผนภูมิที่ 13',
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

        $this->command->info('สร้างแผนภูมิที่ 13 (บวมทั่วไป - EDEMA/SWELLING) กรอบ 1-12 สำเร็จ');
    }
}
