<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram23EyePainSeeder extends Seeder
{
    private const DIAGRAM_ID = '00023';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.2', '1.3', '1.3.1', '1.4',
                '2', '3', '4', '4.1', '5', '6', '6.1', '6.2', '6.3', '7'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 23 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 23
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ปวดตา/เจ็บตา (EYE PAIN)',
                'diagram_name_en' => 'Eye Pain',
                'description' => 'มีอาการปวดหรือเจ็บบริเวณรอบๆ ตา หรือภายในนัยน์ตา สาเหตุที่พบบ่อย ปวดเมื่อยกล้ามเนื้อตาจากการเพ่งดูนานไป สายตาผิดปกติ (178) ไมเกรน (71) ไซนัสอักเสบ (26) สิ่งแปลกปลอมเข้าตา (186) กุ้งยิง (176) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 7',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 23
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'M', 'q' => "มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้\n- ปวดตารุนแรง?\n- ตาเพิ่งมัวลงอย่างกะทันหัน?\n- กระจกตาขุ่น?\n- รูม่านตา 2 ข้างไม่เท่ากัน?"],
                'B1_1'    => ['frame' => '1.1',   'type' => 'S', 'q' => 'ได้รับบาดเจ็บ?'],
                'B1_2'    => ['frame' => '1.2',   'type' => 'S', 'q' => 'รูม่านตาข้างที่ปวดโตกว่าข้างที่ไม่ปวด?'],
                'B1_3'    => ['frame' => '1.3',   'type' => 'S', 'q' => 'รูม่านตาข้างที่ปวดเล็กกว่าข้างที่ไม่ปวด?'],
                'B1_3_1'  => ['frame' => '1.3.1', 'type' => 'S', 'q' => 'ปวดขมับข้างเดียวแบบเป็นๆ หายๆ แต่ละครั้งนาน 15 นาที ถึง 4 ชั่วโมง?'],
                'B1_4'    => ['frame' => '1.4',   'type' => 'S', 'q' => 'ตาแดง น้ำตาไหล กลัวแสง และตาพร่ามัว?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'สิ่งแปลกปลอมเข้าตา?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'ขอบตาบนบวมแดง มีแผลเปื่อยหรือมีสะเก็ดสีขาว?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'มีตุ่มผิวขึ้นที่เปลือกตา?'],
                'B4_1'    => ['frame' => '4.1',   'type' => 'S', 'q' => 'ขึ้นที่หัวตา?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'มีขี้ตา? หรือ ตาแดง?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => 'ปวดรอบกระบอกตา?'],
                'B6_1'    => ['frame' => '6.1',   'type' => 'S', 'q' => 'กดเจ็บตรงหัวคิ้วหรือใต้ตา? หรือ มีน้ำมูกหรือเสมหะสีเหลืองหรือเขียว?'],
                'B6_2'    => ['frame' => '6.2',   'type' => 'S', 'q' => 'ปวดศีรษะซีกเดียว หรือแต่ละครั้งปวดนาน 4-72 ชั่วโมง และมีสาเหตุกระตุ้น?'],
                'B6_3'    => ['frame' => '6.3',   'type' => 'S', 'q' => 'ปวดเมื่อยเวลาใช้สายตาเพ่งดูนานไป?'],
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
                'B1'     => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'   => [['severe_eye_injury', null], [null, 'B1_2']],
                'B1_2'   => [['acute_glaucoma', null], [null, 'B1_3']],
                'B1_3'   => [[null, 'B1_3_1'], [null, 'B1_4']],
                'B1_3_1' => [['cluster_headache', null], ['uveitis', null]],
                'B1_4'   => [['keratitis_corneal_ulcer', null], ['optic_neuritis_or_other', null]],
                'B2'     => [['foreign_body_in_eye', null], [null, 'B3']],
                'B3'     => [['blepharitis', null], [null, 'B4']],
                'B4'     => [[null, 'B4_1'], [null, 'B5']],
                'B4_1'   => [['dacryocystitis', null], ['hordeolum', null]],
                'B5'     => [['conjunctivitis_diagram25', null], [null, 'B6']],
                'B6'     => [[null, 'B6_1'], ['unclear_eye_pain_care', null]],
                'B6_1'   => [['sinusitis', null], [null, 'B6_2']],
                'B6_2'   => [['migraine', null], [null, 'B6_3']],
                'B6_3'   => [['eye_strain', null], ['unclear_eye_pain_care', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'severe_eye_injury' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ตาได้รับบาดเจ็บรุนแรง/เลือดออกในช่องลูกตาหน้า (185)
⊕ ด่วน
NOTE,
                    'refs'       => ['185'],
                    'diagrams'   => [],
                ],
                'acute_glaucoma' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ต้อหินชนิดเฉียบพลัน (181)
⊕ ด่วน
NOTE,
                    'refs'       => ['181'],
                    'diagrams'   => [],
                ],
                'cluster_headache' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปวดศีรษะคลัสเตอร์ (71.1)
• ฉีดไดไฮโดรเออร์โกตามีน เข้ากล้าม
• กินยาป้องกัน
⊕ ถ้าปวดรุนแรง หรือพบว่าปวดเป็นครั้งแรก
NOTE,
                    'refs'       => ['71.1'],
                    'diagrams'   => [],
                ],
                'uveitis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ม่านตาอักเสบ (183)
⊕ ด่วน
NOTE,
                    'refs'       => ['183'],
                    'diagrams'   => [],
                ],
                'keratitis_corneal_ulcer' => [
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
                'optic_neuritis_or_other' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุอื่น เช่น ประสาทตาอักเสบ (optic neuritis)
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'foreign_body_in_eye' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สิ่งแปลกปลอมเข้าตา (186)
• ล้างตา/เขี่ยเอาสิ่งแปลกปลอมออก
• ยาป้ายตา/หยอดตาที่เข้ายาปฏิชีวนะ (ย25.9, ย25.10)
⊕ ถ้าไม่หายปวด หรือเศษผงฝังในตาดำหรือตาขาว
NOTE,
                    'refs'       => ['186'],
                    'diagrams'   => [],
                ],
                'blepharitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หนังตาอักเสบ (176.1)
NOTE,
                    'refs'       => ['176.1'],
                    'diagrams'   => [],
                ],
                'dacryocystitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ถุงน้ำตาอักเสบ (177)
• ประคบด้วยน้ำอุ่นจัดๆ
• ยาป้ายตาปฏิชีวนะ (ย25.9)
• ถ้าเป็นมากกินคลอกซาซิลลิน (ย4.3) หรือ อิริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 7 วัน หรือเป็นๆ หายๆ บ่อย
NOTE,
                    'refs'       => ['177'],
                    'diagrams'   => [],
                ],
                'hordeolum' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กุ้งยิง (176)
• ประคบด้วยน้ำอุ่นจัดๆ
• ยาป้ายตาปฏิชีวนะ (ย25.9)
• ถ้าเป็นมากกินคลอกซาซิลลิน (ย4.3) หรือ อิริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 7 วัน หรือเป็นๆ หายๆ บ่อย
NOTE,
                    'refs'       => ['176'],
                    'diagrams'   => [],
                ],
                'conjunctivitis_diagram25' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 25 เคืองตา/คันตา/ตาแดง/ตาแฉะ กรอบที่ 2.1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00025'],
                ],
                'sinusitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไซนัสอักเสบ (26)
• ยาแก้ปวด (ย1)
• อะโมกซีซิลลิน (ย4.2) หรือโคไตรม็อกซาโซล (ย4.7) หรืออิริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['26'],
                    'diagrams'   => [],
                ],
                'migraine' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไมเกรน (71)
• ยาแก้ปวด (ย1)
• นอนพัก
• พยายามหลีกเลี่ยงสิ่งกระตุ้นให้ปวด
⊕ ถ้าเป็นการปวดครั้งแรกในคนอายุมากกว่า 40 ปี/ ความดันโลหิตสูง/ ปวดนานเกิน 3 วัน/ ปวดแรงหรือถี่ขึ้นกว่าเดิม
NOTE,
                    'refs'       => ['71'],
                    'diagrams'   => [],
                ],
                'eye_strain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปวดเมื่อยกล้ามเนื้อตา (eye strain)
• พักสายตา
• ถ้าไม่หายกินยาแก้ปวด (ย1)
⊕ ถ้าสงสัยสายตายาว/เอียง (178)
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'unclear_eye_pain_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ปวด (ย1)
⊕ ถ้าไม่ดีขึ้นภายใน 1 สัปดาห์ หรือสงสัยสายตาผิดปกติ หรือตาเข (178)
NOTE,
                    'refs'       => ['178'],
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
                    'medical_reference' => 'แผนภูมิที่ 23',
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

        $this->command->info('สร้างแผนภูมิที่ 23 (ปวดตา/เจ็บตา - EYE PAIN) กรอบ 1-7 สำเร็จ');
    }
}
