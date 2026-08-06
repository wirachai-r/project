<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram8PalenessAnemiaSeeder extends Seeder
{
    private const DIAGRAM_ID = '00008';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1',
                '2',
                '3',
                '4',
                '5',
                '6',
                '7',
                '8',
                '9',
                '9.1',
                '9.2',
                '9.3',
                '10'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()
            ) {
                throw new RuntimeException(
                    'แผนภูมิที่ 8 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 8
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ซีด/โลหิตจาง (PALENESS/ANEMIA)',
                'diagram_name_en' => 'Paleness / Anemia',
                'description' => 'หน้า เปลือกตา ริมฝีปาก ลิ้น ฝ่ามือ และเล็บ ซีดขาวพร้อมกันทุกส่วน อาจมีอาการเบื่ออาหาร อ่อนเพลีย หน้ามืดวิงเวียน สาเหตุที่พบบ่อย: โลหิตจางจากภาวะขาดธาตุเหล็ก (100), หญิงตั้งครรภ์, โรคพยาธิปากขอ (233), แผลเพ็ปติก (51), วัณโรค (14), ภาวะไตวายเรื้อรัง (134), ริดสีดวงทวาร (58) / ถ้าอาการไม่ชัดเจน และไม่ได้มีอาการซีดเหลืองมาตั้งแต่เกิด ให้การดูแลรักษาดังกรอบที่ (11)',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 8
            $boxes = [
                'B1'     => ['frame' => '1',   'type' => 'S', 'q' => 'บวม หายใจหอบ และนอนราบไม่ได้?'],
                'B2'     => ['frame' => '2',   'type' => 'S', 'q' => 'มีไข้?'],
                'B3'     => [
                    'frame' => '3',
                    'type'  => 'S',
                    'q'     => 'มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้
- มีบาดแผลเลือดออกหรือได้รับบาดเจ็บ?
- ไอเป็นเลือด?
- อาเจียนเป็นเลือด?
- ปัสสาวะเป็นเลือด?
- ถ่ายเป็นเลือด? หรือริดสีดวง?
- ถ่ายดำ?
- เลือดออกทางช่องคลอด?'
                ],
                'B4'     => ['frame' => '4',   'type' => 'S', 'q' => 'มีประวัติเลือดออกแล้วหยุดยาก เป็นๆหายๆมาตั้งแต่เด็ก?'],
                'B5'     => ['frame' => '5',   'type' => 'S', 'q' => 'ประวัติเป็นโรคไต ความดันโลหิตสูง หรือเบาหวานมานาน?'],
                'B6'     => ['frame' => '6',   'type' => 'S', 'q' => 'มีจุดแดงรูปแมงมุมที่หน้าอก/ต้นแขน? หรือ ฝ่ามือแดง?'],
                'B7'     => ['frame' => '7',   'type' => 'S', 'q' => 'มีจุดแดงหรือจ้ำเขียวตามผิวหนัง?'],
                'B8'     => ['frame' => '8',   'type' => 'S', 'q' => 'หน้าตาแปลก? ซีดเหลืองมาตั้งแต่เด็ก? หรือ ม้ามโต?'],
                'B9'     => ['frame' => '9',   'type' => 'S', 'q' => 'ปวดท้อง?'],
                'B9_1'   => ['frame' => '9.1', 'type' => 'S', 'q' => 'แท้งบุตร?'],
                'B9_2'   => ['frame' => '9.2', 'type' => 'S', 'q' => 'ประวัติขาดประจำเดือน? หรือ มีเลือดออกทางช่องคลอดกะปริดกะปรอย?'],
                'B9_3'   => ['frame' => '9.3', 'type' => 'S', 'q' => 'ทำงานในโรงงานแบตเตอรี่ ทำสี หรือมีอาชีพที่เกี่ยวข้องกับสารตะกั่ว?'],
                'B10'    => [
                    'frame' => '10',
                    'type'  => 'S',
                    'q'     => 'พบในหญิงตั้งครรภ์ เด็กเล็ก วัยรุ่น ผู้สูงอายุ คนยากจน ในคนที่เบื่ออาหาร หรืออดกินเนื้อสัตว์มานาน? หรือ ตรวจพบเล็บอ่อนและแบน หรือเล็บเงยเป็นรูปช้อน?'
                ],
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
                'B1'    => [['heart_failure', null], [null, 'B2']],
                'B2'    => [['refer_diagram_9', null], [null, 'B3']],
                'B3'    => [['blood_loss_anemia', null], [null, 'B4']],
                'B4'    => [['hemophilia', null], [null, 'B5']],
                'B5'    => [['chronic_renal_failure', null], [null, 'B6']],
                'B6'    => [['cirrhosis', null], [null, 'B7']],
                'B7'    => [['bleeding_disorder_leukemia', null], [null, 'B8']],
                'B8'    => [['thalassemia', null], [null, 'B9']],
                'B9'    => [[null, 'B9_1'], [null, 'B10']],
                'B9_1'  => [['abortion', null], [null, 'B9_2']],
                'B9_2'  => [['ectopic_pregnancy', null], [null, 'B9_3']],
                'B9_3'  => [['lead_poisoning', null], ['unexplained_abdominal_pain_anemia', null]],
                'B10'   => [['iron_deficiency_anemia', null], ['general_anemia_care', null]],
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
                    'note'       => 'ภาวะหัวใจวาย (98)
⊕ ด่วน',
                    'refs'       => ['98'],
                    'diagrams'   => [],
                ],
                'refer_diagram_9' => [
                    'urgency'    => 'R',
                    'time_frame' => null,
                    'note'       => 'ดูแผนภูมิที่ 9 ซีดร่วมกับมีไข้ กรอบที่ (1)',
                    'refs'       => [],
                    'diagrams'   => ['00009'],
                ],
                'blood_loss_anemia' => [
                    'urgency'    => 'R',
                    'time_frame' => null,
                    'note'       => 'ซีดจากการเสียเลือด
1. ถ้ามีภาวะช็อก (ชีพจรเบาเร็ว ความดันเลือดตก) ให้การปฐมพยาบาล ให้น้ำเกลือ แล้วส่งโรงพยาบาลด่วน
2. ถ้าเสียเลือดไม่มากให้ทำการห้ามเลือด ให้ยาบำรุงโลหิต (ย24.11) และรักษาโรคที่เป็นสาเหตุ (ดูแผนภูมิตามอาการที่พบร่วม)',
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'hemophilia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => 'ฮีโมฟีเลีย (104) (พบในผู้ชายเป็นส่วนมาก)',
                    'refs'       => ['104'],
                    'diagrams'   => [],
                ],
                'chronic_renal_failure' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => '⊕ ภายใน 1 สัปดาห์ อาจเป็นภาวะไตวายเรื้อรัง (134)/อื่นๆ',
                    'refs'       => ['134'],
                    'diagrams'   => [],
                ],
                'cirrhosis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => 'ตับแข็ง (44)
• ชันสูตรเพิ่มเติม
• รักษาตามอาการ
⊕ ถ้าปวดท้อง/ดีซ่าน/น้ำหนักลด/ท้องบวม',
                    'refs'       => ['44'],
                    'diagrams'   => [],
                ],
                'bleeding_disorder_leukemia' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => '⊕ ภายใน 3 วัน อาจเป็นโรคเลือด (103, 104, 106)/ไตวาย (134)/ตับแข็ง (44)/อื่นๆ',
                    'refs'       => ['103', '104', '106', '134', '44'],
                    'diagrams'   => [],
                ],
                'thalassemia' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => 'ทาลาสซีเมีย (105)
⊕ ภายใน 1 สัปดาห์',
                    'refs'       => ['105'],
                    'diagrams'   => [],
                ],
                'abortion' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => 'แท้งบุตร (156)
⊕ ด่วน',
                    'refs'       => ['156'],
                    'diagrams'   => [],
                ],
                'ectopic_pregnancy' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => 'ครรภ์นอกมดลูก (157)
⊕ ด่วน',
                    'refs'       => ['157'],
                    'diagrams'   => [],
                ],
                'lead_poisoning' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => '⊕ ภายใน 3 วัน อาจเป็นตะกั่วเป็นพิษ (220)/อื่นๆ',
                    'refs'       => ['220'],
                    'diagrams'   => [],
                ],
                'unexplained_abdominal_pain_anemia' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => '⊕ ภายใน 3 วัน เพื่อตรวจหาสาเหตุ',
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'iron_deficiency_anemia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => 'โลหิตจางจากภาวะขาดธาตุเหล็ก (100)
• ยาบำรุงโลหิต (ย24.11)
• กินอาหารโปรตีนให้มากขึ้น
• ถ้าดีขึ้นกินยาต่ออีกประมาณ 6 เดือน
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์',
                    'refs'       => ['100'],
                    'diagrams'   => [],
                ],
                'general_anemia_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => '• ยาบำรุงโลหิต (ย24.11)
• ถ้าสงสัยเป็นโรคพยาธิปากขอ (233) หรือตรวจพบไข่พยาธิปากขอในอุจจาระ ให้ยาถ่ายพยาธิปากขอ (ย6)
• ถ้าดีขึ้นใน 2 สัปดาห์ กินยาบำรุงโลหิตต่ออีกประมาณ 6 เดือน
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ หรือมีอาการน้ำหนักลด/บวม/หอบ/มีเลือดออก/ความดันโลหิตสูง (ดู "โรคที่ 100 โลหิตจางจากภาวะขาดธาตุเหล็ก" ประกอบ)',
                    'refs'       => ['100', '233'],
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
                    'time_frame' => $rule['time_frame'] ?? null,
                    'time_frame_en' => null,
                    'note' => $rule['note'],
                    'note_en' => null,
                    'medical_reference' => 'แผนภูมิที่ 8',
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

        $this->command->info('สร้างแผนภูมิที่ 8 (ซีด/โลหิตจาง - PALENESS/ANEMIA) กรอบ 1-11 สำเร็จ');
    }
}
