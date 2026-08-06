<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram61SkinDiseasesSeeder extends Seeder
{
    private const DIAGRAM_ID = '00061';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '2', '3', '4', '5', '5.1', '5.2', '5.3',
                '6', '7', '8', '9', '10', '11', '11.1', '11.2', '11.3'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 61 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 61
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'โรคผิวหนัง (SKIN DISEASES)',
                'diagram_name_en' => 'Skin Diseases',
                'description' => 'มีผื่น ตุ่ม จุดแดงจ้ำเขียว หรือเป็นก้อนขึ้นตามผิวหนัง อาจมีอาการปวดหรือคันร่วมด้วยหรือไม่ก็ได้ สาเหตุที่พบบ่อย หิด (195) เหา (196) กลาก (190) เกลื้อน (191) เริม (187) งูสวัด (188) ลมพิษ (198) ผื่นแพ้ (199) ฝี (192.1) พุพอง (192.2) หูด (189) คีลอยด์ (206) สิว (204) ฝ้า (205) ถ้าอาการไม่ชัดเจน ควรปรึกษาแพทย์',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes)
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'มีผื่น หรือตุ่มขึ้น?'],
                'B1_1'    => ['frame' => '1.1',   'type' => 'S', 'q' => 'คัน?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'มีอาการคันโดยไม่มีผื่นหรือตุ่มขึ้นให้เห็น?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'มีจุดแดงหรือจ้ำเขียวขึ้น ดึงหนังให้ตึงไม่จางหาย?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'ปวดบวมแดงร้อนตามผิวหนัง? เป็นฝี? พุพอง? หรือ แผลอักเสบ?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'แผลเรื้อรังเกิน 3 สัปดาห์?'],
                'B5_1'    => ['frame' => '5.1',   'type' => 'S', 'q' => 'ไม่ปวด และโตขึ้นเร็ว? หรือ มีเลือดออก?'],
                'B5_2'    => ['frame' => '5.2',   'type' => 'S', 'q' => 'มีอาการกระหายน้ำบ่อย? ปัสสาวะบ่อยและมาก? หรือ ตรวจพบน้าตาลในปัสสาวะ?'],
                'B5_3'    => ['frame' => '5.3',   'type' => 'S', 'q' => 'พบในบริเวณที่เป็นหลอดเลือดขอด?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => 'รอยแผลเป็นปูนนูน?'],
                'B7'      => ['frame' => '7',     'type' => 'S', 'q' => 'หูด? หูดข้าวสุก? หรือ หงอนไก่?'],
                'B8'      => ['frame' => '8',     'type' => 'S', 'q' => 'มีก้อนบวม?'],
                'B9'      => ['frame' => '9',     'type' => 'S', 'q' => 'สิวขึ้น?'],
                'B10'     => ['frame' => '10',    'type' => 'S', 'q' => 'ฝ้าขึ้นที่หน้า?'],
                'B11'     => ['frame' => '11',    'type' => 'S', 'q' => 'เล็บผิดปกติ?'],
                'B11_1'   => ['frame' => '11.1',  'type' => 'S', 'q' => 'เล็บผุกร่อน?'],
                'B11_2'   => ['frame' => '11.2',  'type' => 'S', 'q' => 'ขอบเล็บบวมแดง? หรือ เล็บเสียรูป มีร่องขวางขรุขระ?'],
                'B11_3'   => ['frame' => '11.3',  'type' => 'S', 'q' => 'กัดแทะเล็บ?'],
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
                'B1_1'   => [['see_diagram64_itchy_rash', null], ['see_diagram63_nonitchy_rash', null]],
                'B2'     => [['see_diagram62_itching', null], [null, 'B3']],
                'B3'     => [['see_diagram10_red_purple_spots', null], [null, 'B4']],
                'B4'     => [['skin_infection_abscess_erysipelas', null], [null, 'B5']],
                'B5'     => [[null, 'B5_1'], [null, 'B6']],
                'B5_1'   => [['skin_cancer_1w', null], [null, 'B5_2']],
                'B5_2'   => [['diabetic_ulcer_3d', null], [null, 'B5_3']],
                'B5_3'   => [['varicose_ulcer_1w', null], ['chronic_ulcer_care_1w', null]],
                'B6'     => [['keloid_hypertrophic_scar', null], [null, 'B7']],
                'B7'     => [['warts_molluscum_condyloma', null], [null, 'B8']],
                'B8'     => [['see_diagram14_swelling_mass', null], [null, 'B9']],
                'B9'     => [['acne_vulgaris', null], [null, 'B10']],
                'B10'    => [['chloasma_melasma', null], [null, 'B11']],
                'B11'    => [[null, 'B11_1'], ['examine_other_abnormalities', null]],
                'B11_1'  => [['tinea_unguius_nail_fungus', null], [null, 'B11_2']],
                'B11_2'  => [['candidal_onychia_paronychia', null], [null, 'B11_3']],
                'B11_3'  => [['nail_biting_stress', null], ['examine_other_abnormalities', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนด Diagnosis Rules
            $rules = [
                'see_diagram64_itchy_rash' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 64 ผื่น/ตุ่ม/วงด่าง ร่วมกับมีอาการคัน กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00064'],
                ],
                'see_diagram63_nonitchy_rash' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 63 ผื่น/ตุ่ม/วงด่าง กรอบที่ 3
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00063'],
                ],
                'see_diagram62_itching' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 62 คัน กรอบที่ 2
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00062'],
                ],
                'see_diagram10_red_purple_spots' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 10 จุดแดง-จ้ำเขียว กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00010'],
                ],
                'skin_infection_abscess_erysipelas' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
ฝี/พุพอง/แผลอักเสบ/เนื้อเยื่อใต้ผิวหนังชั้นลึกอักเสบ/ไฟลามทุ่ง (192.1-192.5)
• ไดคล็อกซาซิลลิน (ย4.3) หรืออีริโทรไมซิน (ย4.4) หรือไซโพรฟลอกซาซิน (ย4.11.2)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือเป็นเบาหวาน (117)/สงสัยเป็นเมลิออยโดซิส (229.2)/เป็นหลังกินหอยนางรมดิบหรือหลังเล่นน้ำทะเล
NOTE,
                    'refs'       => ['192.1', '192.2', '192.3', '192.4', '192.5'],
                    'diagrams'   => [],
                ],
                'skin_cancer_1w' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งผิวหนัง (237.1)
NOTE,
                    'refs'       => ['237.1'],
                    'diagrams'   => [],
                ],
                'diabetic_ulcer_3d' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
แผลเรื้อรังในผู้ป่วยเบาหวาน (117)
⊕ ภายใน 3 วัน พร้อมกับให้ไดคล็อกซาซิลลิน (ย4.3)
NOTE,
                    'refs'       => ['117'],
                    'diagrams'   => [],
                ],
                'varicose_ulcer_1w' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
แผลเรื้อรังจากหลอดเลือดขอดแตก (99)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['99'],
                    'diagrams'   => [],
                ],
                'chronic_ulcer_care_1w' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
• ชะแผลด้วยไฮโดรเจนเพอร์ออกไซด์
• ใส่น้ำผึ้ง
• บำรุงอาหารโปรตีน
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ อาจเป็นมะเร็งผิวหนัง (237.1)/เมลิออยโดซิส (229.2)
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'keloid_hypertrophic_scar' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
แผลเป็นยักษ์/คีลอยด์ (206)
• ถ้าคันทาครีมสเตียรอยด์ (ย25.6)
⊕ ถ้าโตมาก
NOTE,
                    'refs'       => ['206'],
                    'diagrams'   => [],
                ],
                'warts_molluscum_condyloma' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูด/หูดข้าวสุก (189)/หงอนไก่ (189.1)
• จี้หรือตัดออก
NOTE,
                    'refs'       => ['189', '189.1'],
                    'diagrams'   => [],
                ],
                'see_diagram14_swelling_mass' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 14 บวมเฉพาะที่/มีก้อน กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00014'],
                ],
                'acne_vulgaris' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
สิว (204)
• ยารักษาสิว
⊕ ถ้าไม่ทุเลาใน 2 สัปดาห์ หรือเรื้อรัง ถ้าพบในผู้หญิงที่หน้ามัน มีหนวดขึ้นผิดปกติ ประจำเดือนขาด หรือมีบุตรยาก อาจเป็นกลุ่มอาการถุงน้ำรังไข่ชนิดหลายถุง (153.2)
NOTE,
                    'refs'       => ['204'],
                    'diagrams'   => [],
                ],
                'chloasma_melasma' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ฝ้า (205)
• ยารักษาฝ้า
NOTE,
                    'refs'       => ['205'],
                    'diagrams'   => [],
                ],
                'tinea_unguius_nail_fungus' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
โรคกลากที่เล็บ (190)
• กริซีโอฟุลวิน (ย4.10)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือเป็นๆ หายๆ เรื้อรัง อาจเป็นโซริอาซิส (203.2)/อื่น ๆ
NOTE,
                    'refs'       => ['190'],
                    'diagrams'   => [],
                ],
                'candidal_onychia_paronychia' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
โรคเชื้อราแคนดิดา (191.1)
• คีโตโคนาโซล (ย4.5)
• ทาครีมรักษาโรคเชื้อรา (ย25.2)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือเป็นๆ หายๆ เรื้อรัง อาจเป็นโซริอาซิส (203.2)/อื่น ๆ
NOTE,
                    'refs'       => ['191.1'],
                    'diagrams'   => [],
                ],
                'nail_biting_stress' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เกิดจากการกัดแทะ พบในเด็ก หรือผู้ที่มีความเครียด
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'examine_other_abnormalities' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ถ้ามีอาการผิดปกติอื่น ๆ ตรวจดูอาการเพิ่มเติม
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
                    'medical_reference' => 'แผนภูมิที่ 61',
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

        $this->command->info('สร้างแผนภูมิที่ 61 (โรคผิวหนัง) สำเร็จ');
    }
}
