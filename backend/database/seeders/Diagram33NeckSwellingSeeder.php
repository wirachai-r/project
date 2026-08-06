<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram33NeckSwellingSeeder extends Seeder
{
    private const DIAGRAM_ID = '00033';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '4.1', '4.2', '4.3', '4.4',
                '5', '6', '6.1'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 33 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 33
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'คางบวม/คอบวม (NECK SWELLING)',
                'diagram_name_en' => 'Neck Swelling',
                'description' => 'มีอาการบวมหรือมีก้อนที่คาง ใต้คาง หรือข้างคอ สาเหตุที่พบบ่อย : คางทูม (7) ต่อมน้ำเหลืองอักเสบ (194) ทอนซิลอักเสบ (8) เหงือกอักเสบ (61) ถ้าอาการไม่ชัดเจน และก้อนเล็กกว่า 1 ซม. ให้รักษาตามอาการ แต่ถ้าก้อนโตขึ้นหรือเบื่ออาหาร น้ำหนักลด ควรปรึกษาแพทย์ ถ้ามีอาการเจ็บคอร่วมด้วย - ดูแผนภูมิที่ 35 ประกอบ',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 33
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => "หายใจลำบาก? ไอเสียงก้อง?\nหรือ พบหลังถูกผึ้ง/ต่อต่อย?"],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => 'น้ำหนักลดฮวบ?'],
                'B3'   => ['frame' => '3',   'type' => 'S', 'q' => 'ฟันผุ หรือ เหงือกบวม?'],
                'B4'   => ['frame' => '4',   'type' => 'S', 'q' => 'มีไข้?'],
                'B4_1' => ['frame' => '4.1', 'type' => 'S', 'q' => "มีไข้เกิน 7 วัน? หรือ\nมีก้อนบวมพร้อมกัน\nมากกว่า 2 แห่ง?"],
                'B4_2' => ['frame' => '4.2', 'type' => 'S', 'q' => 'ทอนซิลบวมแดง/เป็นหนอง?'],
                'B4_3' => ['frame' => '4.3', 'type' => 'S', 'q' => "ต่อมน้ำเหลืองใต้คาง\nหรือข้างคอโตและเจ็บ?"],
                'B4_4' => ['frame' => '4.4', 'type' => 'S', 'q' => "บวมที่ใต้หูข้างเดียวหรือ 2 ข้าง?\nหรือ มีประวัติสัมผัสผู้ป่วยคางทูม?"],
                'B5'   => ['frame' => '5',   'type' => 'S', 'q' => 'ก้อนบวมนั้น ปวดและเจ็บ?'],
                'B6'   => ['frame' => '6',   'type' => 'S', 'q' => "ก้อนโตมากกว่า 1 ซม.? หรือ\nโตพร้อมกันในบริเวณต่างกัน\nมากกว่า 2 แห่ง?"],
                'B6_1' => ['frame' => '6.1', 'type' => 'S', 'q' => 'ก้อนของต่อมไทรอยด์?'],
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
                'B1'   => [['airway_obstruction_or_allergy', null], [null, 'B2']],
                'B2'   => [['malignancy_tb_severe', null], [null, 'B3']],
                'B3'   => [['gingivitis_ludwig_angina', null], [null, 'B4']],
                'B4'   => [[null, 'B4_1'], [null, 'B5']],
                'B4_1' => [['lymphoma_fever_multiple', null], [null, 'B4_2']],
                'B4_2' => [['tonsillitis', null], [null, 'B4_3']],
                'B4_3' => [['lymphadenitis_febrile', null], [null, 'B4_4']],
                'B4_4' => [['mumps', null], ['unspecified_febrile_neck_swelling', null]],
                'B5'   => [['lymphadenitis_abscess', null], [null, 'B6']],
                'B6'   => [[null, 'B6_1'], ['chronic_lymphadenitis', null]],
                'B6_1' => [['refer_diagram_14', null], ['severe_neck_mass_causes', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'airway_obstruction_or_allergy' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน
อาจเป็นคอดิฟ (10)/แอนแทรกซ์ (229.3)/เนื้องอกประซึนอน (mediastinal tumor)/ลุดวิกแองไจนา (ดู "โรคที่ 60")/ผึ้งหรือต่อต่อย (222)
NOTE,
                    'refs'       => ['10', '229.3', '60', '222'],
                    'diagrams'   => [],
                ],
                'malignancy_tb_severe' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์
อาจมีสาเหตุร้ายแรง เช่น มะเร็งกล่องเสียง (237.7)/มะเร็งโพรงหลังจมูก (237.8) วัณโรค (14)
NOTE,
                    'refs'       => ['237.7', '237.8', '14'],
                    'diagrams'   => [],
                ],
                'gingivitis_ludwig_angina' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เหงือกอักเสบ (61)/ลุดวิกแองไจนา (ดู "โรคที่ 60")
• เพนิซิลลินวี (ย4.1) หรือดอกซีไซคลีน (ย4.5.1) หรืออีริโทรไมซิน (ย4.4)
• ยาแก้ปวดลดไข้ (ย1)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์/หายใจลำบาก/คางบวม 2 ข้าง
NOTE,
                    'refs'       => ['61', '60'],
                    'diagrams'   => [],
                ],
                'lymphoma_fever_multiple' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์
อาจมีสาเหตุร้ายแรง เช่น มะเร็งต่อมน้ำเหลือง (106.1)
NOTE,
                    'refs'       => ['106.1'],
                    'diagrams'   => [],
                ],
                'tonsillitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ทอนซิลอักเสบ (8)
• ยาลดไข้ (ย1)
• เพนิซิลลินวี (ย4.1) หรือ อะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน
NOTE,
                    'refs'       => ['8'],
                    'diagrams'   => [],
                ],
                'lymphadenitis_febrile' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ต่อมน้ำเหลืองอักเสบ (194)
• ไดคล็อกซาซิลลิน (ย4.3) หรืออีริโทรไมซิน (ย4.4)
• ยาลดไข้ (ย1)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน
NOTE,
                    'refs'       => ['194'],
                    'diagrams'   => [],
                ],
                'mumps' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
คางทูม (7)
• ยาลดไข้ (ย1)
• ประคบด้วยน้ำอุ่นจัด ๆ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือเป็นหนอง/สงสัยเป็นเมลิออยโดซิส (229.2)
NOTE,
                    'refs'       => ['7', '229.2'],
                    'diagrams'   => [],
                ],
                'unspecified_febrile_neck_swelling' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาลดไข้ (ย1)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'lymphadenitis_abscess' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ต่อมน้ำเหลืองอักเสบ (194)/ฝี (192.1)
• ไดคล็อกซาซิลลิน (ย4.3) หรืออีริโทรไมซิน (ย4.4)
• ยาแก้ปวด (ย1)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน
NOTE,
                    'refs'       => ['194', '192.1'],
                    'diagrams'   => [],
                ],
                'refer_diagram_14' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 14 บวมเฉพาะที่/มีก้อน กรอบที่ 7.1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00014'],
                ],
                'severe_neck_mass_causes' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจมีสาเหตุร้ายแรง เช่น มะเร็งโพรงหลังจมูก (237.8)/มะเร็งต่อมน้ำเหลือง (106.1)/เอดส์ (238) เป็นต้น
NOTE,
                    'refs'       => ['237.8', '106.1', '238'],
                    'diagrams'   => [],
                ],
                'chronic_lymphadenitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อาจเป็นต่อมน้ำเหลืองอักเสบเรื้อรัง (194)
• สังเกตดูอาการ
⊕ ถ้าก้อนโตขึ้น หรือเบื่ออาหาร/น้ำหนักลด/มีไข้เกิน 7 วัน/คัดจมูกเรื้อรัง/หูอื้อเรื้อรัง/เลือดกำเดาไหลบ่อย
NOTE,
                    'refs'       => ['194'],
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
                    'medical_reference' => 'แผนภูมิที่ 33',
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

        $this->command->info('สร้างแผนภูมิที่ 33 (คางบวม/คอบวม - NECK SWELLING) กรอบ 1-6.1 สำเร็จ');
    }
}
