<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram34DysphagiaSeeder extends Seeder
{
    private const DIAGRAM_ID = '00034';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '4.1',
                '5', '6', '7'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 34 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 34
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'กลืนลำบาก (DYSPHAGIA)',
                'diagram_name_en' => 'Dysphagia',
                'description' => 'รู้สึกจุกหรือจุกในคอหอยเวลากลืนอาหาร หรือรู้สึกกลืนอาหารลงไปในหลอดอาหารลำบาก สาเหตุที่พบบ่อย : ก้างปลาหรือกระดูกติดคอ (215) โรควิตกกังวล/โรควิตกกังวลทั่วไป (88) แผลเปื่อยในปาก (59) โรคเชื้อราในช่องปาก (52.5) ถ้าอาการไม่ชัดเจน ให้ยาทางจิตประสาท (ย17) ถ้าไม่ดีขึ้นใน 2 สัปดาห์ควรปรึกษาแพทย์',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 34
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => "มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้\n☐ น้ำหนักลดฮวบ?\n☐ มีก้อนโตที่ข้างคอหรือไหปลาร้า?\n☐ มีอาการสะอึกหรือเสียงแหบนานเกิน 3 สัปดาห์?\n☐ อาการกลืนลำบากค่อย ๆ เป็นมากขึ้น?"],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => 'มีอาการกลัวน้ำ ในผู้ป่วยที่ถูกสุนัขหรือแมวกัดหรือข่วน?'],
                'B3'   => ['frame' => '3',   'type' => 'S', 'q' => 'ขากรรไกรแข็ง? หรือ ชักเวลาสัมผัสถูก หรือถูกแสงสว่าง หรือเสียงดัง ๆ?'],
                'B4'   => ['frame' => '4',   'type' => 'S', 'q' => 'ลิ้นแข็ง (พูดจาอ้อแอ้)? แขนขาเป็นอัมพาต? หรือ หนังตาตก?'],
                'B4_1' => ['frame' => '4.1', 'type' => 'S', 'q' => 'เป็นหลังกินอาหารบรรจุปิ๊บ กระป๋อง ขวดแก้ว หรือภาชนะปิดมิดชิด?'],
                'B5'   => ['frame' => '5',   'type' => 'S', 'q' => 'คอหอยโตมาก?'],
                'B6'   => ['frame' => '6',   'type' => 'S', 'q' => 'คางบวมหรือคอบวม? เจ็บคอ? หรือ มีแผลเปื่อยในปาก/ปากอักเสบ หรือลิ้นเป็นฝ้าขาว?'],
                'B7'   => ['frame' => '7',   'type' => 'S', 'q' => 'รู้สึกจุกที่คอหอย แต่ยังกลืนอาหารและน้ำได้เป็นปกติ? และ มีความวิตกกังวล หรือมีอารมณ์ซึมเศร้า?'],
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
                'B1'   => [['esophageal_cancer_severe', null], [null, 'B2']],
                'B2'   => [['rabies', null], [null, 'B3']],
                'B3'   => [['tetanus', null], [null, 'B4']],
                'B4'   => [[null, 'B4_1'], [null, 'B5']],
                'B4_1' => [['botulism', null], ['refer_diagram_19', null]],
                'B5'   => [['goiter_compressing_esophagus', null], [null, 'B6']],
                'B6'   => [['refer_diagram_33_35_36', null], [null, 'B7']],
                'B7'   => [['anxiety_depression', null], ['myasthenia_gravis_severe', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'esophageal_cancer_severe' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์
อาจเป็นมะเร็งหลอดอาหาร (237.10)/สาเหตุร้ายแรงอื่น ๆ
NOTE,
                    'refs'       => ['237.10'],
                    'diagrams'   => [],
                ],
                'rabies' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
พิษสุนัขบ้า (61)
⊕ ด่วน
NOTE,
                    'refs'       => ['61'],
                    'diagrams'   => [],
                ],
                'tetanus' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
บาดทะยัก (67)
ถ้าชักให้ไดอะซีแพม (ย17.1)
⊕ ด่วน
NOTE,
                    'refs'       => ['67'],
                    'diagrams'   => [],
                ],
                'botulism' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
โบทูลิซึม (67.1)
⊕ ด่วน
NOTE,
                    'refs'       => ['67.1'],
                    'diagrams'   => [],
                ],
                'refer_diagram_19' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 19 อัมพาต/แขนขาไม่มีแรง/หนังตาตก กรอบที่ 2
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00019'],
                ],
                'goiter_compressing_esophagus' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ต่อมไทรอยด์โตกดหลอดอาหาร
ดูแผนภูมิที่ 14 กรอบที่ 7.1 ประกอบ
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00014'],
                ],
                'refer_diagram_33_35_36' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 33 คางบวม/คอบวม กรอบที่ 1
35 เจ็บคอ กรอบที่ 1
36 ปากเจ็บ/แผลที่ปาก/ลิ้นเป็นฝ้าขาว กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00033', '00035', '00036'],
                ],
                'anxiety_depression' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรควิตกกังวล/โรควิตกกังวลทั่วไป (88)/โรคอารมณ์แปรปรวน/โรคซึมเศร้า (88.2)
• ยาทางจิตประสาท (ย17)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['88', '88.2'],
                    'diagrams'   => [],
                ],
                'myasthenia_gravis_severe' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์
อาจเป็นไมแอสทีเนียเกรวิส (79)/สาเหตุร้ายแรงอื่น ๆ
NOTE,
                    'refs'       => ['79'],
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
                    'medical_reference' => 'แผนภูมิที่ 34',
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

        $this->command->info('สร้างแผนภูมิที่ 34 (กลืนลำบาก - DYSPHAGIA) กรอบ 1-7 สำเร็จ');
    }
}
