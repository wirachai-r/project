<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram25RedEyeSeeder extends Seeder
{
    private const DIAGRAM_ID = '00025';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            // รายการกรอบทั้งหมดในแผนภูมิที่ 25
            $frameNumbers = [
                '1', '1.1', '1.2', '2', '2.1', '2.1.1', '2.1.2', '2.1.3',
                '2.2', '3', '3.1', '3.2', '4', '5', '6', '6.1', '6.2',
                '6.3', '6.4', '7', '8'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 25 ถูก seed แล้ว กรุณารัน Rollback หรือลบข้อมูลเดิมก่อน'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 25
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'เคืองตา/คันตา/ตาแฉะ/ตาแดง (RED EYE)',
                'diagram_name_en' => 'Red Eye',
                'description' => 'มีอาการเคืองตา คันตา ตาแดง หรือมีตาแฉะ อาจพบร่วมกันหรือแยกกันโดดๆ ก็ได้ สาเหตุที่พบบ่อย สิ่งแปลกปลอมเข้าตา (186) เยื่อตาขาวอักเสบจากการแพ้ (173) เยื่อตาขาวอักเสบจากเชื้อแบคทีเรีย (171) เยื่อตาขาวอักเสบจากเชื้อไวรัส (172) ต้อเนื้อ (179) ถ้ามีอาการเคืองตาโดยไม่มีอาการชัดเจน ให้การดูแลรักษาดังกรอบที่ 8',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 25
            $boxes = [
                'B1'     => ['frame' => '1',     'type' => 'S', 'q' => 'หนังตาบวม?'],
                'B1_1'   => ['frame' => '1.1',   'type' => 'S', 'q' => 'ขอบตาบวมแดง มีแผลเปื่อยหรือมีสะเก็ดสีขาว?'],
                'B1_2'   => ['frame' => '1.2',   'type' => 'S', 'q' => 'คัน?'],
                'B2'     => ['frame' => '2',     'type' => 'S', 'q' => 'มีขี้ตา (eye discharge)? หรือ ตาแดง (red eye)?'],
                'B2_1'   => ['frame' => '2.1',   'type' => 'S', 'q' => 'ขี้ตาเฉอะ? หรือ ขี้ตาสีเหลืองหรือเขียว?'],
                'B2_1_1' => ['frame' => '2.1.1', 'type' => 'S', 'q' => 'พบในทารกแรกเกิด?'],
                'B2_1_2' => ['frame' => '2.1.2', 'type' => 'S', 'q' => 'มีตุ่มน้ำพองตามผิวหนังและปากเปื่อย?'],
                'B2_1_3' => ['frame' => '2.1.3', 'type' => 'S', 'q' => 'ตากระจกดำเป็นแผลหรือมีฝ้าขาว? หรือ เจ็บตามาก ไม่สู้แสง ตาพร่ามัว?'],
                'B2_2'   => ['frame' => '2.2',   'type' => 'S', 'q' => 'ต่อมน้ำเหลืองหน้าหูโต? หรือ พบระบาด?'],
                'B3'     => ['frame' => '3',     'type' => 'S', 'q' => 'คันตา?'],
                'B3_1'   => ['frame' => '3.1',   'type' => 'S', 'q' => 'พบหลังกินยา/อาหารทะเล/ถูกฝุ่น/สัมผัสสารเคมี (เช่น เครื่องสำอาง)? หรือ มีประวัติโรคภูมิแพ้?'],
                'B3_2'   => ['frame' => '3.2',   'type' => 'S', 'q' => 'พบตุ่มเล็กๆ สีเหลืองที่เยื่อบุเปลือกตาด้านในเป็นนานเป็นแรมเดือน?'],
                'B4'     => ['frame' => '4',     'type' => 'S', 'q' => 'มีเยื่อเหลืองๆ แดงๆ ยื่นตาดำ/ทางหัวตา?'],
                'B5'     => ['frame' => '5',     'type' => 'S', 'q' => 'เศษเหล็ก/ฝุ่นเข้าตา?'],
                'B6'     => ['frame' => '6',     'type' => 'S', 'q' => 'ตาขาวแดงเป็นปื้นหรือห้อเลือด?'],
                'B6_1'   => ['frame' => '6.1',   'type' => 'S', 'q' => 'ได้รับอุบัติเหตุที่ตา?'],
                'B6_2'   => ['frame' => '6.2',   'type' => 'S', 'q' => 'เกิดหลังจากไอ หรือจามรุนแรง?'],
                'B6_3'   => ['frame' => '6.3',   'type' => 'S', 'q' => 'มีจุดแดง จ้ำเขียวตามตัว? หรือมีเลือดออกที่อื่น?'],
                'B6_4'   => ['frame' => '6.4',   'type' => 'S', 'q' => 'เคืองตา? ต่อมน้ำเหลืองหน้าหูโต? หรือ พบระบาด?'],
                'B7'     => ['frame' => '7',     'type' => 'S', 'q' => 'เคืองตา?'],
            ];

            // รันสร้าง box_id ต่อเนื่อง
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

            // Entry Point ของแผนภูมิ
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'entry_box_id' => $boxes['B1']['id'],
            ]);

            // 3. ตัวเลือกตอบ (Choices) และเส้นทางเชื่อมต่อ
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

            // โครงสร้าง Decision Tree แบบ Binary
            $binary = [
                'B1'     => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'   => [['blepharitis_176_1', null], [null, 'B1_2']],
                'B1_2'   => [['allergic_eyelid_swelling', null], ['eyelid_swelling_not_resolved_1_week', null]],
                'B2'     => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'   => [[null, 'B2_1_1'], [null, 'B2_2']],
                'B2_1_1' => [['neonatal_gonococcal_conjunctivitis_174', null], [null, 'B2_1_2']],
                'B2_1_2' => [['stevens_johnson_207_1', null], [null, 'B2_1_3']],
                'B2_1_3' => [['corneal_ulcer_182', null], ['bacterial_conjunctivitis_171', null]],
                'B2_2'   => [['viral_conjunctivitis_172_2_2', null], [null, 'B3_1']],
                'B3'     => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'   => [['allergic_conjunctivitis_173', null], [null, 'B3_2']],
                'B3_2'   => [['trachoma_175', null], ['eye_itch_observation', null]],
                'B4'     => [['pterygium_179', null], [null, 'B5']],
                'B5'     => [['foreign_body_eye_186', null], [null, 'B6']],
                'B6'     => [[null, 'B6_1'], [null, 'B7']],
                'B6_1'   => [['subconjunctival_hemorrhage_trauma_184', null], [null, 'B6_2']],
                'B6_2'   => [['subconjunctival_hemorrhage_cough_184', null], [null, 'B6_3']],
                'B6_3'   => [['bleeding_disorder_or_severe_infection', null], [null, 'B6_4']],
                'B6_4'   => [['viral_conjunctivitis_172_6_4', null], ['subconjunctival_hemorrhage_observation', null]],
                'B7'     => [['unspecified_eye_irritation_frame8', null], ['recurrent_eye_irritation_check', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กฎการวินิจฉัย การรักษา และคำแนะนำ
            $rules = [
                'blepharitis_176_1' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หนังตาอักเสบ (176.1)
• ประคบด้วยน้ำอุ่นจัด ๆ
• ยาป้ายตาปฏิชีวนะ (ย25.9)
• ถ้าเป็นมากกินไดคล็อกซาซิลลิน (ย4.3) หรือ อิริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 7 วัน หรือเป็น ๆ หาย ๆ บ่อย
NOTE,
                    'refs'       => ['176.1'],
                    'diagrams'   => [],
                ],
                'allergic_eyelid_swelling' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
บวมจากการแพ้ (เช่น แพ้ยา อาหาร เครื่องสำอาง แมลง เป็นต้น)
• หยุดใช้สิ่งที่แพ้
• ยาแก้แพ้ (ย7)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือถูกผึ้ง/ต่อต่อย
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'eyelid_swelling_not_resolved_1_week' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ถ้าไม่หายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'neonatal_gonococcal_conjunctivitis_174' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
อาจเป็นตาอักเสบจากเชื้อหนองใน (174)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['174'],
                    'diagrams'   => [],
                ],
                'stevens_johnson_207_1' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
กลุ่มอาการสตีเวนส์จอห์นสัน (207.1)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['207.1'],
                    'diagrams'   => [],
                ],
                'corneal_ulcer_182' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
กระจกตาอักเสบ/แผลกระจกตา (182)
• ยาป้ายตาเจนตาไมซิน/โทบราไมซิน (ย25.9)
⊕ ด่วน
NOTE,
                    'refs'       => ['182'],
                    'diagrams'   => [],
                ],
                'bacterial_conjunctivitis_171' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เยื่อตาขาวอักเสบจากเชื้อแบคทีเรีย (171)
• ยาป้ายตา/หยอดตาที่เข้ายาปฏิชีวนะ (ย25.9, ย25.10)
• ถ้าหนังตาบวม กินไดคล็อกซาซิลลิน (ย4.3) หรืออิริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['171'],
                    'diagrams'   => [],
                ],
                'viral_conjunctivitis_172_2_2' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เยื่อตาขาวอักเสบจากเชื้อไวรัส (172)
• ยาป้ายตา/หยอดตาที่เข้ายาปฏิชีวนะ (ย25.9, ย25.10)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['172'],
                    'diagrams'   => [],
                ],
                'allergic_conjunctivitis_173' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เยื่อตาขาวอักเสบจากการแพ้ (173)
• ยาแก้แพ้ (ย7)
• ยาหยอดตาลดการอักเสบ (ย25.14)/ยาหยอดตาที่เข้าสเตียรอยด์ (ย25.11)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['173'],
                    'diagrams'   => [],
                ],
                'trachoma_175' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ริดสีดวงตา (175)
• ดอกซีไซคลีน (ย4.5.1)/อิริโทรไมซิน (ย4.4)
• ยาป้ายตาเตตราไซคลีน/อิริโทรไมซิน (ย25.9)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['175'],
                    'diagrams'   => [],
                ],
                'eye_itch_observation' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• สังเกตอาการ
• ถ้าคันมากกินยาแก้แพ้ (ย7)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'pterygium_179' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ต้อเนื้อ (179)
• ถ้าเคืองแดง ใช้ยาหยอดตาลดการอักเสบ (ย25.14)
• แนะนำไปลอกที่โรงพยาบาลเมื่อต้อเนื้องอกเข้าไปในตาดำ
NOTE,
                    'refs'       => ['179'],
                    'diagrams'   => [],
                ],
                'foreign_body_eye_186' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สิ่งแปลกปลอมเข้าตา (186)
• ล้างตา/เขี่ยสิ่งแปลกปลอมออก
• ยาป้ายตา/หยอดตาที่เข้ายาปฏิชีวนะ (ย25.9, ย25.10)
⊕ ถ้าไม่หายเคือง หรือเศษผงฝังในตาดำหรือตาขาว
NOTE,
                    'refs'       => ['186'],
                    'diagrams'   => [],
                ],
                'subconjunctival_hemorrhage_trauma_184' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เลือดออกใต้ตาขาว (184)
• สังเกตอาการ จะค่อยๆ หายไปภายใน 2 สัปดาห์
⊕ ถ้าปวดตามาก หรือตาพร่ามัว
NOTE,
                    'refs'       => ['184'],
                    'diagrams'   => [],
                ],
                'subconjunctival_hemorrhage_cough_184' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เลือดออกใต้ตาขาว (184) เนื่องจากไอหรือจาม ดู โรคไอกรน (13)/หลอดลมอักเสบ (15)
NOTE,
                    'refs'       => ['184', '13', '15'],
                    'diagrams'   => [],
                ],
                'bleeding_disorder_or_severe_infection' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจเป็นโรคเลือด (ดู "โรคที่ 103, 106") หรือโรคติดเชื้อรุนแรง (ดู "โรคที่ 227, 228")
NOTE,
                    'refs'       => ['103', '106', '227', '228'],
                    'diagrams'   => [],
                ],
                'viral_conjunctivitis_172_6_4' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เยื่อตาขาวอักเสบจากเชื้อไวรัส (172)
• ยาป้ายตา/หยอดตาที่เข้ายาปฏิชีวนะ (ย25.9, ย25.10)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['172'],
                    'diagrams'   => [],
                ],
                'subconjunctival_hemorrhage_observation' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือเป็นๆ หายๆ บ่อย
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'unspecified_eye_irritation_frame8' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เคืองตาโดยไม่มีสาเหตุร้ายแรง
• สวมแว่นตาดำเวลาออกนอกบ้าน
• งดว่ายน้ำในสระว่ายน้ำ
• หลีกเลี่ยงสิ่งระคายเคือง (เช่น ควัน ฝุ่น)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือเคืองตามาก
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'recurrent_eye_irritation_check' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
⊕ ถ้ามีอาการเป็นๆ หายๆ บ่อย
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
            ];

            // 5. บันทึก Diagnosis Rules และ Relations
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
                    'medical_reference' => 'แผนภูมิที่ 25',
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

                // Rule Conditions
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

                // Rule Diseases (เชื่อมรหัสโรค)
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

                // Rule Next Diagrams (เชื่อมแผนภูมิถัดไป)
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

        $this->command->info('สร้างแผนภูมิที่ 25 (เคืองตา/คันตา/ตาแฉะ/ตาแดง) กรอบ 1-8 สำเร็จ');
    }
}
