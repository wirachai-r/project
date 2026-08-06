<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram38CoughSeeder extends Seeder
{
    private const DIAGRAM_ID = '00038';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '3.1', '3.1.1', '4', '5',
                '6', '6.1', '6.2', '6.3', '7', '7.1',
                '7.1.1', '7.1.2', '7.1.3', '7.1.4', '8'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 38 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 38
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ไอ (COUGH)',
                'diagram_name_en' => 'Cough',
                'description' => 'มีอาการไอแบบแห้ง ๆ (ไม่มีเสมหะ) หรือไอแบบมีเสมหะก็ได้ สาเหตุที่พบบ่อย : ไข้หวัด (1) หวัดจากการแพ้ (25) หลอดลมอักเสบ (15) ถ้าอาการไม่ชัดเจน 1. ถ้าไอมีเสมหะ ให้การดูแลรักษาดังกรอบที่ 7.1.4 2. ถ้าไอแห้ง ๆ ให้การดูแลรักษาดังกรอบที่ 8',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 38
            $boxes = [
                'B1'     => ['frame' => '1',     'type' => 'S', 'q' => 'หอบ?'],
                'B2'     => ['frame' => '2',     'type' => 'S', 'q' => 'น้ำหนักลดฮวบ?'],
                'B3'     => ['frame' => '3',     'type' => 'S', 'q' => 'ไอเป็นเลือด?'],
                'B3_1'   => ['frame' => '3.1',   'type' => 'S', 'q' => 'สุขภาพทั่วไปแข็งแรงดี?'],
                'B3_1_1' => ['frame' => '3.1.1', 'type' => 'S', 'q' => 'ไอเรื้อรัง?'],
                'B4'     => ['frame' => '4',     'type' => 'S', 'q' => 'มีไข้?'],
                'B5'     => ['frame' => '5',     'type' => 'S', 'q' => "ไอเสียงก้องและหายใจเข้ามีเสียง\nดังอี้ด (stridor) ตอนกลางคืน ใน\nเด็กอายุ 6 เดือนถึง 3 ปี?"],
                'B6'     => ['frame' => '6',     'type' => 'S', 'q' => 'ไอนานกว่า 2 สัปดาห์?'],
                'B6_1'   => ['frame' => '6.1',   'type' => 'S', 'q' => "คัดจมูก คันคอ หรือจาม\nบ่อย เวลาถูกอากาศเย็น\nฝุ่น หรือขนสัตว์?"],
                'B6_2'   => ['frame' => '6.2',   'type' => 'S', 'q' => "ไอติดต่อกันเป็นชุด ในเด็กที่มี\nประวัติอยู่ใกล้ชิดกับเด็กที่เป็น\nไอกรน?"],
                'B6_3'   => ['frame' => '6.3',   'type' => 'S', 'q' => "ไอเฉพาะช่วงหลังตื่นนอนหรือหลัง\nกินอาหาร? และ มีอาการจุกแน่น\nหรือแสบตรงลิ้นปี่หรือเรอเปรี้ยว\nเป็นๆหายๆ เรื้อรัง?"],
                'B7'     => ['frame' => '7',     'type' => 'S', 'q' => 'มีเสมหะ?'],
                'B7_1'   => ['frame' => '7.1',   'type' => 'S', 'q' => "เหนื่อยง่าย? หรือ เรื้อรัง\nเป็นแรมนาน/แรมปี?"],
                'B7_1_1' => ['frame' => '7.1.1', 'type' => 'S', 'q' => "เสมหะข้นและ\nมีกลิ่นเหม็น?\nหรือ นิ้วปุ้ม?"],
                'B7_1_2' => ['frame' => '7.1.2', 'type' => 'S', 'q' => "ปอดเคาะโปร่ง และใช้เครื่อง\nฟังปอดพบเสียงหายใจค่อย?"],
                'B7_1_3' => ['frame' => '7.1.3', 'type' => 'S', 'q' => "มีประวัติสูบบุหรี่จัด?\nไอมีเสมหะสีเหลือง\nหรือเขียว?\nหรือ ใช้เครื่องฟังปอด\nมีเสียงอี้ด (rhonchi)?"],
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
                'B2'     => [['tb_lung_cancer_aids_melioidosis', null], [null, 'B3']],
                'B3'     => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'   => [[null, 'B3_1_1'], ['tb_lung_cancer_severe', null]],
                'B3_1_1' => [['bronchiectasis_chronic_bronchitis', null], ['acute_bronchitis_hemoptysis', null]],
                'B4'     => [['refer_diagram_2', null], [null, 'B5']],
                'B5'     => [['spasmodic_croup', null], [null, 'B6']],
                'B6'     => [[null, 'B6_1'], [null, 'B7']],
                'B6_1'   => [['allergic_rhinitis', null], [null, 'B6_2']],
                'B6_2'   => [['pertussis', null], [null, 'B6_3']],
                'B6_3'   => [['gerd', null], [null, 'B7']],
                'B7'     => [[null, 'B7_1'], ['dry_cough_general_care', null]],
                'B7_1'   => [[null, 'B7_1_1'], ['acute_bronchitis_sputum', null]],
                'B7_1_1' => [['bronchiectasis', null], [null, 'B7_1_2']],
                'B7_1_2' => [['emphysema', null], [null, 'B7_1_3']],
                'B7_1_3' => [['chronic_bronchitis', null], ['productive_cough_general_care', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

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
                'tb_lung_cancer_aids_melioidosis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์
อาจเป็นวัณโรคปอด (14)/มะเร็งปอด (237.6)/เอดส์ (238)/เมลิออยโดซิส (229.2)/สาเหตุที่ร้ายแรงอื่น ๆ
NOTE,
                    'refs'       => ['14', '237.6', '238', '229.2'],
                    'diagrams'   => [],
                ],
                'tb_lung_cancer_severe' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์
อาจเป็นวัณโรคปอด (14)/มะเร็งปอด (237.6)/สาเหตุที่ร้ายแรงอื่น ๆ
NOTE,
                    'refs'       => ['14', '237.6'],
                    'diagrams'   => [],
                ],
                'bronchiectasis_chronic_bronchitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หลอดลมพอง (17)/หลอดลมอักเสบเรื้อรัง (16)
• อะม็อกซีซิลลิน (ย4.2) หรือ อีริโทรไมซิน (ย4.4)
• ยาลดไข้ (ย1)
• ดื่มน้ำอุ่นมาก ๆ
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือหอบ/น้ำหนักลด/ไข้เกิน 7 วัน
ถ้าดีขึ้นกินยาต่ออีก 7 วัน
NOTE,
                    'refs'       => ['17', '16'],
                    'diagrams'   => [],
                ],
                'acute_bronchitis_hemoptysis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หลอดลมอักเสบ (15)
• อะม็อกซีซิลลิน (ย4.2) หรือ อีริโทรไมซิน (ย4.4)
• ยาลดไข้ (ย1)
• ดื่มน้ำอุ่นมาก ๆ
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือหอบ/น้ำหนักลด/ไข้เกิน 7 วัน
ถ้าดีขึ้นกินยาต่ออีก 7 วัน
NOTE,
                    'refs'       => ['15'],
                    'diagrams'   => [],
                ],
                'refer_diagram_2' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 2 ไข้ร่วมกับมีน้ำมูกหรือไอ กรอบที่ 5
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00002'],
                ],
                'spasmodic_croup' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สปาสโมดิกครู้ป (11)
• รักษาตามอาการ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือหายใจหอบ/สงสัยสำลักสิ่งแปลกปลอม
NOTE,
                    'refs'       => ['11'],
                    'diagrams'   => [],
                ],
                'allergic_rhinitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หวัดภูมิแพ้ (25)
• ยาแก้แพ้ (ย7)
• หลีกเลี่ยงสิ่งที่แพ้
• ออกกำลังกายเป็นประจำ
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือหายใจมีเสียงวี้ด/หอบเหนื่อย
NOTE,
                    'refs'       => ['25'],
                    'diagrams'   => [],
                ],
                'pertussis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไอกรน (13)
• รักษาตามอาการ
NOTE,
                    'refs'       => ['13'],
                    'diagrams'   => [],
                ],
                'gerd' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคกรดไหลย้อน (49.1)
• ยาต้านกรด (ย14.1)
• รานิทิดีน (ย14.3)
• หลีกเลี่ยงสิ่งกระตุ้น
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['49.1'],
                    'diagrams'   => [],
                ],
                'acute_bronchitis_sputum' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หลอดลมอักเสบ (15)
• ให้การรักษาดังกรอบที่ 7.1.4
NOTE,
                    'refs'       => ['15'],
                    'diagrams'   => [],
                ],
                'bronchiectasis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หลอดลมพอง (17)
• งดบุหรี่ แอลกอฮอล์
• ดื่มน้ำอุ่นมาก ๆ
• อะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.4)
• ยายายหลอดลม (ย10) ถ้าหอบเหนื่อยหรือมีเสียงวี้ด
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือน้ำหนักลด
NOTE,
                    'refs'       => ['17'],
                    'diagrams'   => [],
                ],
                'emphysema' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ถุงลมปอดโป่งพอง (16)
• ให้การรักษาดังกรอบที่ 7.1.4
NOTE,
                    'refs'       => ['16'],
                    'diagrams'   => [],
                ],
                'chronic_bronchitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หลอดลมอักเสบเรื้อรัง (16)
• ให้การรักษาดังกรอบที่ 7.1.4
NOTE,
                    'refs'       => ['16'],
                    'diagrams'   => [],
                ],
                'productive_cough_general_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• พักผ่อน งดบุหรี่ แอลกอฮอล์ น้ำแข็ง น้ำเย็น ของทอด ของมันๆ
• ดื่มน้ำอุ่นมาก ๆ
• อะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.4) ถ้าเสมหะเหลืองหรือเขียว ถ้าเสมหะขาวไม่ต้องให้
• ยายายหลอดลม (ย10) ถ้าหอบเหนื่อย หรือมีเสียงวี้ด
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือน้ำหนักลด
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'dry_cough_general_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• พักผ่อน งดบุหรี่ แอลกอฮอล์ น้ำแข็ง น้ำเย็น ของทอด ของมันๆ
• ดื่มน้ำอุ่นมาก ๆ
• ยาแก้ไอ (ย9) หรือจิบน้ํามึ้งผสมมะนาว
• ยาแก้แพ้ (ย7) ถ้าคัดจมูก/คันคอ
• ผู้ที่กินลดความดันโลหิต กลุ่มยาต้านเอซ (ย22.4) เช่น อีนาลาพริล แคปโทพริล อาจมีผลข้างเคียงทำให้เกิดอาการไอเรื้อรังได้ ซึ่งไม่มีอันตราย หากรำคาญควรปรึกษาแพทย์เพื่อเปลี่ยนยาใหม่
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือน้ำหนักลด
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
                    'medical_reference' => 'แผนภูมิที่ 38',
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

        $this->command->info('สร้างแผนภูมิที่ 38 (ไอ - COUGH) กรอบ 1-8 สำเร็จ');
    }
}
