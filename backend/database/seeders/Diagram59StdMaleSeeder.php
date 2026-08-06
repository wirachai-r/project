<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram59StdMaleSeeder extends Seeder
{
    private const DIAGRAM_ID = '00059';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.2', '1.3', '2', '2.1', '2.1.1',
                '2.2', '2.3', '3', '3.1', '3.2', '4', '4.1', '5'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 59 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 59
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'กามโรคในผู้ชาย (STD IN MALE)',
                'diagram_name_en' => 'Sexually Transmitted Diseases in Male',
                'description' => 'มีหนองไหลจากท่อปัสสาวะ เป็นแผลหรือตุ่มในบริเวณอวัยวะเพศ ไข่ดันบวม หรือมีความผิดปกติในบริเวณอวัยวะเพศที่ชวนสงสัยว่าเป็นกามโรค สาเหตุที่พบบ่อย หนองใน (208) หนองในเทียม (209) แผลริมอ่อน (210) เริม (187) ฝีมะม่วง (212) หิด (195) ซิฟิลิส (211) ถ้าอาการไม่ชัดเจน และมีประวัติการเที่ยวหรือเพศสัมพันธ์เสรี ควรปรึกษาแพทย์ หมายเหตุ ผู้ที่เป็นกามโรคควรเจาะเลือดตรวจวีดีอาร์เอล (VDRL) และเชื้อเอชไอวีทุกราย',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes)
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'มีหนองไหลออกจากท่อปัสสาวะ?'],
                'B1_1'    => ['frame' => '1.1',   'type' => 'S', 'q' => 'หลังติดเชื้อ 2-10 วัน และมีหนองออกมามาก?'],
                'B1_2'    => ['frame' => '1.2',   'type' => 'S', 'q' => 'หลังติดเชื้อ 1-4 สัปดาห์ และหนองออกไม่มาก?'],
                'B1_3'    => ['frame' => '1.3',   'type' => 'S', 'q' => 'มีประวัติการเที่ยวหรือเพศสัมพันธ์เสรี?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'เป็นแผลที่อวัยวะเพศ?'],
                'B2_1'    => ['frame' => '2.1',   'type' => 'S', 'q' => 'มีแผลเดียวหรือ 2 แผลชนกัน?'],
                'B2_1_1'  => ['frame' => '2.1.1', 'type' => 'S', 'q' => 'แผลขอบแข็ง ไม่เจ็บ และพบหลังติดเชื้อ 10-90 วัน?'],
                'B2_2'    => ['frame' => '2.2',   'type' => 'S', 'q' => 'แผลริมอ่อน ขอบไม่ชัด เลือดออกง่ายและเจ็บ มีหลายแผล และพบหลังติดเชื้อ 2-7 วัน?'],
                'B2_3'    => ['frame' => '2.3',   'type' => 'S', 'q' => 'แผลถลอกเกิดขึ้นทันที?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'ขึ้นเป็นตุ่มเล็กๆ?'],
                'B3_1'    => ['frame' => '3.1',   'type' => 'S', 'q' => 'ตุ่มคันมากและลามได้? หรือ พบตุ่มคันที่ง่ามมือ ง่ามเท้าร่วมด้วย?'],
                'B3_2'    => ['frame' => '3.2',   'type' => 'S', 'q' => 'ตุ่มใสเล็กๆ อยู่เป็นกลุ่ม เกิดหลังติดเชื้อ 4-7 วัน? หรือ เป็นๆ หายๆ เรื้อรังโดยไม่มีประวัติการเที่ยวครั้งใหม่?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'ต่อมน้ำเหลืองที่ขาหนีบโต (ไข่ดันบวม)?'],
                'B4_1'    => ['frame' => '4.1',   'type' => 'S', 'q' => 'มีประวัติการเที่ยวหรือเพศสัมพันธ์เสรี?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'มีประวัติการเที่ยวหรือเพศสัมพันธ์เสรี?'],
            ];

            // สร้าง box_id ถัดไปอัตโนมัติ
            $nextBoxId = ((int) DB::table('question_boxes')->max('box_id')) + 1;
            foreach ($boxes as &$box) {
                $box['id'] = str_pad((string) $nextBoxId++, 10, '0', STR_PAD_LEFT);
            }
            unset($box);

            // Insert คำถามลง DB
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

            // ตั้งค่า Entry Box
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'entry_box_id' => $boxes['B1']['id'],
            ]);

            // 3. กำหนด Choice และ Flow
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

            // โครงสร้างการตัดสินใจแบบ Binary
            $binary = [
                'B1'     => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'   => [['gonorrhea', null], [null, 'B1_2']],
                'B1_2'   => [['chlamydia_nongonococcal', null], [null, 'B1_3']],
                'B1_3'   => [['std_risk_discharge_eval', null], ['urethritis_other_causes', null]],
                'B2'     => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'   => [[null, 'B2_1_1'], [null, 'B2_2']],
                'B2_1_1' => [['syphilis', null], [null, 'B2_2']],
                'B2_2'   => [['chancroid', null], [null, 'B2_3']],
                'B2_3'   => [['penile_abrasion', null], ['ulcer_eval_1w', null]],
                'B3'     => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'   => [['scabies', null], [null, 'B3_2']],
                'B3_2'   => [['herpes_simplex_genital', null], ['symptomatic_treatment_papule', null]],
                'B4'     => [[null, 'B4_1'], [null, 'B5']],
                'B4_1'   => [['lymphogranuloma_venereum', null], ['groin_mass_diagram14', null]],
                'B5'     => [['std_risk_follow_up', null], ['observation_and_reassess', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนด Diagnosis Rules
            $rules = [
                'gonorrhea' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
หนองใน (208)
• ชันสูตรเพิ่มเติม
• ยาปฏิชีวนะรักษาหนองใน
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['208'],
                    'diagrams'   => [],
                ],
                'chlamydia_nongonococcal' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
หนองในเทียม (209)
• ชันสูตรเพิ่มเติม
• ยาปฏิชีวนะรักษาหนองในเทียม
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['209'],
                    'diagrams'   => [],
                ],
                'std_risk_discharge_eval' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ชันสูตรเพิ่มเติม
• ให้การรักษาตามสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'urethritis_other_causes' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ท่อปัสสาวะอักเสบจากสาเหตุอื่น
• อะม็อกซีซิลลิน (ย4.2) ครั้งละ 500 มก. วันละ 3-4 ครั้ง หรือโคไตรม็อกซาโซล (ย4.7) ครั้งละ 2 เม็ด วันละ 2 ครั้ง
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'syphilis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ซิฟิลิส (211)
• ชันสูตรเพิ่มเติม
• ยาปฏิชีวนะรักษาซิฟิลิส
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['211'],
                    'diagrams'   => [],
                ],
                'chancroid' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
แผลริมอ่อน (210)
• ชันสูตรเพิ่มเติม
• ยาปฏิชีวนะรักษาแผลริมอ่อน
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['210'],
                    'diagrams'   => [],
                ],
                'penile_abrasion' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
แผลถลอก
• ชะแผลด้วยย่าฆ่าเชื้อ
• เตตราไซคลีน (ย4.5)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'ulcer_eval_1w' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'scabies' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
หิด (195)
• ทาเบนซิลเบนโซเอต (ย25.4)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['195'],
                    'diagrams'   => [],
                ],
                'herpes_simplex_genital' => [
                    'urgency'    => 'P',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เริมที่อวัยวะเพศ (187)
• ชะด้วยทิงเจอร์ใส่แผลสดหรือโพวิโดนไอโอดีน
• อะไซโคลเวียร์ (ย4.17)
⊕ ถ้าเป็นรุนแรง
NOTE,
                    'refs'       => ['187'],
                    'diagrams'   => [],
                ],
                'symptomatic_treatment_papule' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
• รักษาตามอาการ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'lymphogranuloma_venereum' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ฝีมะม่วง (212)
• ชันสูตรเพิ่มเติม
• ยาปฏิชีวนะรักษาฝีมะม่วง
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['212'],
                    'diagrams'   => [],
                ],
                'groin_mass_diagram14' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 14 บวมเฉพาะที่/มีก้อน กรอบที่ 10.2
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00014'],
                ],
                'std_risk_follow_up' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สังเกตอาการ ถ้ามีความผิดปกติให้ทบทวนแผนภูมินี้ซ้ำ ถ้าไม่แน่ใจควรปรึกษาแพทย์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'observation_and_reassess' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• สังเกตอาการ
• ถ้ามีอาการผิดปกติ ควรตรวจดูอาการเพิ่มเติม
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
            ];

            // 5. Insert Rules, Conditions, Diseases, Next Diagrams
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
                    'medical_reference' => 'แผนภูมิที่ 59',
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

                // Insert Conditions
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

                // Insert Rule Diseases
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

                // Insert Rule Next Diagrams
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

        $this->command->info('สร้างแผนภูมิที่ 59 (กามโรคในผู้ชาย) สำเร็จ');
    }
}
