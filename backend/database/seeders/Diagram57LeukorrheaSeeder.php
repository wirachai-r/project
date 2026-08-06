<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram57LeukorrheaSeeder extends Seeder
{
    private const DIAGRAM_ID = '00057';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '2', '3', '3.1', '3.2', '3.3',
                '3.4', '3.5', '3.5.1', '3.5.2', '4', '5'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 57 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 57
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ตกขาว (LEUKORRHEA)/คันในช่องคลอด',
                'diagram_name_en' => 'Leukorrhea / Vaginal Itching',
                'description' => 'ตกขาว หมายถึงอาการที่มีของเหลว (ที่ไม่ใช่เลือด) ไหลออกมาจากช่องคลอดผู้หญิง อาจมีลักษณะใส ไม่มีกลิ่น หรือขาวข้นเหมือนแป้งเปียก หรือมีสีเหลืองหรือสีเขียว อาจมีกลิ่นเหม็นหรือมีอาการคัน คันในช่องคลอด หมายถึงมีอาการคันในช่องคลอด หรือปากช่องคลอด ซึ่งอาจมีตกขาวร่วมด้วยหรือไม่ก็ได้ สาเหตุที่พบบ่อย ตกขาวธรรมดา (148) ช่องคลอดอักเสบจากเชื้อรา (149.1) ช่องคลอดอักเสบจากเชื้อทริโคโมแนส (149.2) โรคพยาธิเส้นด้าย (231) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 5',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes)
            $boxes = [
                'B1'        => ['frame' => '1',       'type' => 'S', 'q' => 'ตกขาวมีลักษณะเป็นสีเหลือง หรือเขียว และมีกลิ่นเหม็น?'],
                'B1_1'      => ['frame' => '1.1',     'type' => 'S', 'q' => 'มีไข้?'],
                'B2'        => ['frame' => '2',       'type' => 'S', 'q' => 'มีเลือดออกเวลาร่วมเพศ?'],
                'B3'        => ['frame' => '3',       'type' => 'S', 'q' => 'คันในช่องคลอด?'],
                'B3_1'      => ['frame' => '3.1',     'type' => 'S', 'q' => 'มีอาการดื่มน้ำบ่อย และปัสสาวะบ่อย? น้ำหนักลด? หรือ ตรวจพบน้ำตาลในปัสสาวะ?'],
                'B3_2'      => ['frame' => '3.2',     'type' => 'S', 'q' => 'ตกขาวมีสีเหลืองหรือสีเขียว เป็นฟองๆ จำนวนมากและมีกลิ่นเหม็น?'],
                'B3_3'      => ['frame' => '3.3',     'type' => 'S', 'q' => 'ตกขาวจำนวนเล็กน้อย มีลักษณะข้นขาวคล้ายแป้งเปียก ในผู้หญิงตั้งครรภ์ กินยาคุม หรือยาปฏิชีวนะ?'],
                'B3_4'      => ['frame' => '3.4',     'type' => 'S', 'q' => 'พบในผู้หญิงวัยหมดประจำเดือน?'],
                'B3_5'      => ['frame' => '3.5',     'type' => 'S', 'q' => 'มีอาการตกขาว?'],
                'B3_5_1'    => ['frame' => '3.5.1',   'type' => 'S', 'q' => 'มีอาการคันตามผิวหนังภายนอกร่วมด้วย?'],
                'B3_5_2'    => ['frame' => '3.5.2',   'type' => 'S', 'q' => 'คันกันตอนกลางคืน?'],
                'B4'        => ['frame' => '4',       'type' => 'S', 'q' => 'ตกขาวลักษณะเป็นมูกใส หรือแป้งเปียก ไม่มีกลิ่น?'],
                'B5'        => ['frame' => '5',       'type' => 'S', 'q' => 'รักษาด้วย ยาเหน็บช่องคลอดชนิดครอบจักรวาล เช่น ไกนีคอน (Gynecon) ไกโนวา (Gynova) เช้าเม็ดเย็นเม็ด?'],
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
                'B1'      => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'    => [['pelvic_endometritis_severe', null], ['gonorrhea_adnexitis_endometritis', null]],
                'B2'      => [['cervical_cancer', null], [null, 'B3']],
                'B3'      => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'    => [['diabetes_mellitus', null], [null, 'B3_2']],
                'B3_2'    => [['vaginal_trichomoniasis', null], [null, 'B3_3']],
                'B3_3'    => [['vaginal_candidiasis', null], [null, 'B3_4']],
                'B3_4'    => [['atrophic_vaginitis', null], [null, 'B3_5']],
                'B3_5'    => [[null, 'B4'], [null, 'B3_5_1']],
                'B3_5_1'  => [['external_skin_pruritus', null], [null, 'B3_5_2']],
                'B3_5_2'  => [['enterobiasis', null], ['vulvar_pruritus_nonspecific', null]],
                'B4'      => [['normal_leukorrhea', null], [null, 'B5']],
                'B5'      => [['empirical_broad_spectrum_treatment', null], [null, null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนด Diagnosis Rules
            $rules = [
                'pelvic_endometritis_severe' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจเป็นปีกมดลูกอักเสบ/เยื่อบุมดลูกอักเสบ (147) ระยะรุนแรง
NOTE,
                    'refs'       => ['147'],
                    'diagrams'   => [],
                ],
                'gonorrhea_adnexitis_endometritis' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน อาจเป็นหนองใน (208)/ปีกมดลูกอักเสบ/เยื่อบุมดลูกอักเสบ (147)
NOTE,
                    'refs'       => ['208', '147'],
                    'diagrams'   => [],
                ],
                'cervical_cancer' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งปากมดลูก (237.3)
NOTE,
                    'refs'       => ['237.3'],
                    'diagrams'   => [],
                ],
                'diabetes_mellitus' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เบาหวาน (117)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['117'],
                    'diagrams'   => [],
                ],
                'vaginal_trichomoniasis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ช่องคลอดอักเสบจากเชื้อทริโคโมแนส (149.2)
• ชันสูตรเพิ่มเติม
• เมโทรนิดาโซล (ย4.8)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['149.2'],
                    'diagrams'   => [],
                ],
                'vaginal_candidiasis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ช่องคลอดอักเสบจากเชื้อรา (149.1)
• ชันสูตรเพิ่มเติม
• ยาเหน็บช่องคลอด นิสแตติน หรือกิน คีโตโคนาโซล (ย4.9)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['149.1'],
                    'diagrams'   => [],
                ],
                'atrophic_vaginitis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
ช่องคลอดอักเสบในหญิงวัยหมดประจำเดือน (129)
⊕ ภายใน 2 สัปดาห์
NOTE,
                    'refs'       => ['129'],
                    'diagrams'   => [],
                ],
                'external_skin_pruritus' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
รักษาตามอาการ
• ยาแก้แพ้ (ย7)
• ถ้ามีผื่นคันยาสเตียรอยด์ (ย25.6)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'enterobiasis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคพยาธิเส้นด้าย (231)
• ยาถ่ายพยาธิ (ย6)
NOTE,
                    'refs'       => ['231'],
                    'diagrams'   => [],
                ],
                'vulvar_pruritus_nonspecific' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
• กินยาแก้แพ้ (ย7)
• ทาครีมสเตียรอยด์ (ย25.6) ถ้ามีผื่นคันรอบๆ ปากช่องคลอด
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'normal_leukorrhea' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
ตกขาวธรรมดา (148)
⊕ ถ้าเป็นติดต่อกันนานเกิน 2 สัปดาห์
(ในเด็กผู้หญิงอาจเกิดจากการใส่กางเกงในใยสังเคราะห์ที่ออบอ้าวไป ควรเปลี่ยนเป็นกางเกงผ้าฝ้าย)
NOTE,
                    'refs'       => ['148'],
                    'diagrams'   => [],
                ],
                'empirical_broad_spectrum_treatment' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
• ยาเหน็บช่องคลอดชนิดครอบจักรวาล เช่น ไกนีคอน (Gynecon) ไกโนวา (Gynova) เช้าเม็ดเย็นเม็ด
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือเป็นๆ หายๆ เรื้อรัง
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
                    'medical_reference' => 'แผนภูมิที่ 57',
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

        $this->command->info('สร้างแผนภูมิที่ 57 (ตกขาว/คันในช่องคลอด) สำเร็จ');
    }
}
