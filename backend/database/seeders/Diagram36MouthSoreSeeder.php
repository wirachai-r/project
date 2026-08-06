<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram36MouthSoreSeeder extends Seeder
{
    private const DIAGRAM_ID = '00036';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '2.1', '2.1.1', '2.1.1.1', '2.1.2', '2.1.3', '2.1.4', '2.1.5',
                '2.2', '2.3', '2.4', '3', '4', '4.1', '5', '5.1'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 36 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 36
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ปากเจ็บ/แผลที่ปาก/ลิ้นเป็นฝ้าขาว (MOUTH SORE)',
                'diagram_name_en' => 'Mouth Sore',
                'description' => 'เจ็บหรือเป็นแผลที่ปาก ลิ้น เหงือก เพดานปาก หรือกระพุ้งแก้ม หรือมีอาการลิ้นเป็นฝ้าขาว สาเหตุที่พบบ่อย : แผลแอ็ฟทัส (59.1) โรคเชื้อราในช่องปาก (59.5) เริมที่ริมฝีปาก (187) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาตามอาการ',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 36
            $boxes = [
                'B1'      => ['frame' => '1',       'type' => 'S', 'q' => "เหงือกบวมแดง เป็นหนอง\nหรือมีเลือดไหล?"],
                'B2'      => ['frame' => '2',       'type' => 'S', 'q' => 'เป็นแผลเปื่อยในช่องปาก?'],
                'B2_1'    => ['frame' => '2.1',     'type' => 'S', 'q' => 'มีไข้?'],
                'B2_1_1'  => ['frame' => '2.1.1',   'type' => 'S', 'q' => "มีผื่น/ตุ่มน้ำใส\nกระจายตามตัว\nทั่วไป?"],
                'B2_1_1_1' => ['frame' => '2.1.1.1', 'type' => 'S', 'q' => "ตาแดง\nตาแฉะ?"],
                'B2_1_2'  => ['frame' => '2.1.2',   'type' => 'S', 'q' => "ในเด็กเล็กมีตุ่มน้ำใสขึ้น\nเฉพาะที่มือเท้า ฝ่ามือ\nฝ่าเท้า?"],
                'B2_1_3'  => ['frame' => '2.1.3',   'type' => 'S', 'q' => "ในเด็กเล็กมีแผลเปื่อย\nหลายแห่งที่เยื่อบุริมฝีปาก\nเหงือก ลิ้น เพดานปาก\nและ เหงือกบวมแดง?"],
                'B2_1_4'  => ['frame' => '2.1.4',   'type' => 'S', 'q' => "ในเด็กเล็กมีแผลเปื่อย\nหลายแห่งที่เพดานอ่อน\nลิ้น ลิ้นไก่ ผนังคอหอย\nและทอนซิล?"],
                'B2_2'    => ['frame' => '2.2',     'type' => 'S', 'q' => 'เป็นแผลอยู่นานกว่า 3 สัปดาห์?'],
                'B2_3'    => ['frame' => '2.3',     'type' => 'S', 'q' => "ขึ้นแผลเดียวที่เหงือก หรือ\nเพดานปาก? และ มีประวัติเป็น\nเริมในช่องปากมาก่อน หรือพบ\nร่วมกับไข้/ไข้หวัด?"],
                'B2_4'    => ['frame' => '2.4',     'type' => 'S', 'q' => "แผลตื้นพื้นตรงกลางมีสีเหลือง\nมีวงสีแดงอยู่รอบๆ ขึ้นแผลเดียว\nหรือหลายแผลที่เยื่อบุริมฝีปาก\nกระพุ้งแก้ม ลิ้น ใต้ลิ้น ผนัง\nคอหอย หรือเพดานอ่อน? และ\nเป็นๆหายๆ มาแต่วัยรุ่นหรือ\nวัยหนุ่มสาว?"],
                'B3'      => ['frame' => '3',       'type' => 'S', 'q' => "เป็นตุ่มน้ำเล็กๆ ขึ้นเป็นกลุ่ม\nที่ริมฝีปาก (ด้านนอก)?"],
                'B4'      => ['frame' => '4',       'type' => 'S', 'q' => "เป็นแผลเปื่อยที่มุมปาก\n(ปากนกกระจอก)?"],
                'B4_1'    => ['frame' => '4.1',     'type' => 'S', 'q' => "พบในเด็ก? ผู้ที่เบื่อ\nอาหาร/ขาดอาหาร?\nหรือ กินวิตามินบี 2\nหรือบีรวมแล้วดีขึ้น?"],
                'B5'      => ['frame' => '5',       'type' => 'S', 'q' => "ลิ้นหรือเยื่อบุช่องปากเป็นฝ้าขาว\nคล้ายคราบนม เมื่อเช็ดออกเห็น\nพื้นสีแดง หรือมีเลือดซึม?"],
                'B5_1'    => ['frame' => '5.1',     'type' => 'S', 'q' => "มีไข้เรื้อรัง?\nท้องเดินเรื้อรัง?\nหรือ น้ำหนักลดฮวบ?"],
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
                'B1'       => [['gingivitis', null], [null, 'B2']],
                'B2'       => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'     => [[null, 'B2_1_1'], [null, 'B2_2']],
                'B2_1_1'   => [[null, 'B2_1_1_1'], [null, 'B2_1_2']],
                'B2_1_1_1' => [['stevens_johnson_syndrome', null], ['chickenpox', null]],
                'B2_1_2'   => [['hand_foot_mouth_disease', null], [null, 'B2_1_3']],
                'B2_1_3'   => [['acute_herpetic_gingivostomatitis', null], [null, 'B2_1_4']],
                'B2_1_4'   => [['herpangina', null], ['symptomatic_oral_ulcer_care', null]],
                'B2_2'     => [['oral_cancer_syphilis_severe', null], [null, 'B2_3']],
                'B2_3'     => [['oral_herpes', null], [null, 'B2_4']],
                'B2_4'     => [['aphthous_ulcer', null], ['unspecified_oral_ulcer_care', null]],
                'B3'       => [['labial_herpes', null], [null, 'B4']],
                'B4'       => [[null, 'B4_1'], [null, 'B5']],
                'B4_1'     => [['angular_cheilitis', null], ['candidal_cheilitis', null]],
                'B5'       => [[null, 'B5_1'], ['symptomatic_thrush_care', null]],
                'B5_1'     => [['aids', null], ['oral_candidiasis', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'gingivitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เหงือกอักเสบ (61)
• ยาแก้ปวดลดไข้ (ย1)
• ถ้าเหงือกเป็นหนองให้เพนิซิลลินวี (ย4.1)/อีริโทรไมซิน (ย4.4)/ดอกซีไซคลีน (ย4.5.1)
• ปรึกษาทันตแพทย์
NOTE,
                    'refs'       => ['61'],
                    'diagrams'   => [],
                ],
                'stevens_johnson_syndrome' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
กลุ่มอาการสตีเวนส์จอห์นสัน (207.1)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['207.1'],
                    'diagrams'   => [],
                ],
                'chickenpox' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อีสุกอีใส (6)
• ให้การรักษาดังกรอบที่ 2.1.5
• อายุมากกว่า 12 ปี ให้ อะไซโคลเวียร์ (ย4.17)
NOTE,
                    'refs'       => ['6'],
                    'diagrams'   => [],
                ],
                'hand_foot_mouth_disease' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคมือ-เท้า-ปาก (229.1)
• ให้การรักษาดังกรอบที่ 2.1.5
NOTE,
                    'refs'       => ['229.1'],
                    'diagrams'   => [],
                ],
                'acute_herpetic_gingivostomatitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เริมในช่องปากชนิดเฉียบพลัน (59.3)
• ให้การรักษาดังกรอบที่ 2.1.5
• อะไซโคลเวียร์ (ย4.17)
NOTE,
                    'refs'       => ['59.3'],
                    'diagrams'   => [],
                ],
                'herpangina' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เฮอร์แปงไจนา (59.3)
• ให้การรักษาดังกรอบที่ 2.1.5
NOTE,
                    'refs'       => ['59.3'],
                    'diagrams'   => [],
                ],
                'symptomatic_oral_ulcer_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ (กรอบที่ 2.1.5)
• พาราเซตามอล (ย1.2)
• ถ้าเจ็บแผลมาก ให้อมน้ำแข็ง/น้ำเย็น/ไอศกรีม
• ป้อนน้ำและนมบ่อย ๆ
• บ้วนปากด้วยน้ำเกลือ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือไม่ค่อยรู้สึกตัว/ชัก/หอบ/กินไม่ได้/ขาดน้ำ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'oral_cancer_syphilis_severe' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์
อาจเป็นมะเร็งช่องปาก (59.6)/ซิฟิลิส (211)/สาเหตุร้ายแรงอื่น ๆ
NOTE,
                    'refs'       => ['59.6', '211'],
                    'diagrams'   => [],
                ],
                'oral_herpes' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เริมในช่องปาก (187)
• พาราเซตามอล (ย1.2)
• อะไซโคลเวียร์ (ย4.17)
NOTE,
                    'refs'       => ['187'],
                    'diagrams'   => [],
                ],
                'aphthous_ulcer' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
แผลแอ็ฟทัส (59.1)
• ยาแก้ปวด (ย1)
• ทาด้วยครีมไตรแอมซิโนโลน (ย25.6)
⊕ ถ้าไม่ดีขึ้นใน 2-3 สัปดาห์
NOTE,
                    'refs'       => ['59.1'],
                    'diagrams'   => [],
                ],
                'unspecified_oral_ulcer_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ
⊕ ถ้าไม่ดีขึ้นใน 3 สัปดาห์ หรือสงสัยเป็นซิฟิลิส (แผลที่ริมฝีปากขอบแผลเรียบและแข็ง ไม่เจ็บ เป็นแผลเดียว)
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'labial_herpes' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เริมที่ริมฝีปาก (187)
• รักษาตามอาการ
• อะไซโคลเวียร์ (ย4.17)
NOTE,
                    'refs'       => ['187'],
                    'diagrams'   => [],
                ],
                'angular_cheilitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปากนกกระจอก (59.4)
• วิตามินบี 2 (ย24.5)/บีรวม (ย24.8)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['59.4'],
                    'diagrams'   => [],
                ],
                'candidal_cheilitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปากเปื่อยจากเชื้อรา (59.5)
• ทาครีมรักษาโรคเชื้อรา (ย25.2)
⊕ ถ้าไม่ดีขึ้น หรือเป็นๆหายๆบ่อย/น้ำหนักลด
NOTE,
                    'refs'       => ['59.5'],
                    'diagrams'   => [],
                ],
                'symptomatic_thrush_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'aids' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
เอดส์ (238)
⊕ ภายใน 3 วัน
NOTE,
                    'refs'       => ['238'],
                    'diagrams'   => [],
                ],
                'oral_candidiasis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคเชื้อราในช่องปาก (59.5)
• ป้ายด้วยเจนเชียนไวโอเลต (ย25.7)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือเป็นๆหายๆบ่อย
NOTE,
                    'refs'       => ['59.5'],
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
                    'medical_reference' => 'แผนภูมิที่ 36',
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

        $this->command->info('สร้างแผนภูมิที่ 36 (ปากเจ็บ/แผลที่ปาก/ลิ้นเป็นฝ้าขาว - MOUTHS ORE) กรอบ 1-5.1 สำเร็จ');
    }
}
