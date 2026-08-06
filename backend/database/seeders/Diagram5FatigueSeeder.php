<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram5FatigueSeeder extends Seeder
{
    private const DIAGRAM_ID = '00005';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = ['1', '2', '3', '4', '5', '5.1', '6', '7', '8', '9', '10', '11'];
            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 5 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 5
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'อ่อนเพลีย',
                'diagram_name_en' => 'Fatigue / Tiredness',
                'description' => 'มีความรู้สึกอ่อนเพลีย เหนื่อยง่าย ไม่กระปรี้กระเปร่าหรือดูซึมผิดปกติ',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 5
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => 'แขนขาอ่อนแรง? หรือ อัมพาต?'],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => 'น้ำหนักลด? หรือ น้ำหนักเพิ่ม?'],
                'B3'   => ['frame' => '3',   'type' => 'M', 'q' => 'มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้?', 'min' => 1],
                'B4'   => ['frame' => '4',   'type' => 'S', 'q' => 'ความดันโลหิตช่วงบน ≥ 140 มม.ปรอท หรือช่วงล่าง ≥ 90 มม.ปรอท?'],
                'B5'   => ['frame' => '5',   'type' => 'S', 'q' => 'ปวดเสียดชายโครงข้างขวา? หรือ ตับโต?'],
                'B5_1' => ['frame' => '5.1', 'type' => 'S', 'q' => 'มีจุดแดงรูปแมงมุมที่หน้าอก/ต้นแขน? หรือ ฝ่ามือแดง?'],
                'B6'   => ['frame' => '6',   'type' => 'S', 'q' => 'ประวัติขาดประจำเดือน ในหญิงอายุ 15-45 ปี?'],
                'B7'   => ['frame' => '7',   'type' => 'S', 'q' => 'เพิ่งหายจากไข้หวัด ไข้หวัดใหญ่ หรือการเจ็บป่วยอื่นๆ?'],
                'B8'   => ['frame' => '8',   'type' => 'S', 'q' => 'นอนไม่พอ? พักผ่อนไม่พอ? หรือ ตรากตรำงานหนัก?'],
                'B9'   => ['frame' => '9',   'type' => 'S', 'q' => 'กลางวันง่วงนอนง่าย? และกลางคืนนอนกรนมาก?'],
                'B10'  => ['frame' => '10',  'type' => 'S', 'q' => 'คิดมาก กังวลใจ เสียใจ หรือกลุ้มใจ? นอนไม่หลับ? หรือ มีอารมณ์ซึมเศร้า?'],
                'B11'  => ['frame' => '11',  'type' => 'S', 'q' => 'ออกร้อนซู่ซ่าตามผิวกาย หรือ เหงื่อออกตอนกลางคืน ในหญิงวัยหมดประจำเดือน (40-55 ปี)?'],
            ];

            $nextBoxId = ((int) DB::table('question_boxes')->max('box_id')) + 1;
            foreach ($boxes as &$box) {
                $box['id'] = str_pad((string) $nextBoxId++, 10, '0', STR_PAD_LEFT);
            }
            unset($box);

            foreach ($boxes as $box) {
                DB::table('question_boxes')->updateOrInsert(['box_id' => $box['id']], [
                    'frame_number' => $box['frame'],
                    'question_text' => $box['q'],
                    'question_text_en' => null,
                    'question_type' => $box['type'],
                    'min_required' => $box['min'] ?? null,
                    'detail' => $box['detail'] ?? null,
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);
            }

            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'entry_box_id' => $boxes['B1']['id'],
            ]);

            // 3. กำหนดตัวเลือก (Choices)
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

            $binary = [
                'B1'   => [['refer_paralysis', null], [null, 'B2']],
                'B2'   => [['refer_weight', null], [null, 'B3']],
                'B4'   => [['hypertension', null], [null, 'B5']],
                'B5'   => [[null, 'B5_1'], [null, 'B6']],
                'B5_1' => [['cirrhosis', null], ['liver_other', null]],
                'B6'   => [['pregnancy', null], [null, 'B7']],
                'B7'   => [['recovery', null], [null, 'B8']],
                'B8'   => [['insufficient_rest', null], [null, 'B9']],
                'B9'   => [['sleep_apnea', null], [null, 'B10']],
                'B10'  => [['mental_health', null], [null, 'B11']],
                'B11'  => [['menopause', null], ['self_care', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่', $no[1], $no[0], 2);
            }

            // Checklist สำหรับกรอบที่ 3
            $checklist = ['ไข้', 'ซีด', 'ดีซ่าน', 'ใจสั่น', 'เหงื่อออกมาก', 'หอบ', 'บวม', 'เจ็บหน้าอก', 'ปวดท้อง', 'ท้องเดิน', 'ปวดศีรษะ', 'เวียนศีรษะ', 'อาเจียน'];
            foreach ($checklist as $index => $label) {
                $addChoice('B3', $label, null, 'associated_symptoms', $index + 1);
            }
            DB::table('question_boxes')->where('box_id', $boxes['B3']['id'])->update([
                'yes_next_box_id' => null,
                'no_next_box_id' => $boxes['B4']['id'],
            ]);

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'refer_paralysis' => [
                    'urgency'    => 'Y',
                    'time_frame' => null,
                    'note'       => 'ดูแผนภูมิที่ 19 อัมพาต',
                    'refs'       => [],
                    'diagrams'   => ['00019'],
                ],
                'refer_weight' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'ดูแผนภูมิที่ 6 น้ำหนักลด กรอบที่ (1)',
                        'ดูแผนภูมิที่ 7 น้ำหนักมากหรืออ้วน กรอบที่ (3.1)',
                    ]),
                    'refs'       => [],
                    'diagrams'   => ['00006', '00007'],
                ],
                'associated_symptoms' => [
                    'urgency'    => 'Y',
                    'time_frame' => null,
                    'note'       => 'ดูแผนภูมิตามอาการที่พบร่วม',
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'hypertension' => [
                    'urgency'    => 'Y',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'ความดันโลหิตสูง (92)',
                        '• ชันสูตรเพิ่มเติม',
                        '• ยาลดความดัน (ย22)',
                        '⊕ ถ้าควบคุมความดันไม่ได้หรือสงสัยเป็นความดันโลหิตสูงชนิดทุติยภูมิ/มีภาวะแทรกซ้อน',
                    ]),
                    'refs'       => ['92'],
                    'diagrams'   => [],
                ],
                'cirrhosis' => [
                    'urgency'    => 'Y',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'ตับแข็ง (44)',
                        '• ชันสูตรเพิ่มเติม',
                        '• รักษาตามอาการ',
                        '• ห้ามดื่มแอลกอฮอล์',
                        '⊕ ถ้ามีอาการปวดท้อง/ดีซ่าน/น้ำหนักลด/ท้องบวม',
                    ]),
                    'refs'       => ['44'],
                    'diagrams'   => [],
                ],
                'liver_other' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => '⊕ ภายใน 1 สัปดาห์ อาจเป็นตับอักเสบ (38)/มะเร็งตับ (45)/อื่นๆ',
                    'refs'       => ['38', '45'],
                    'diagrams'   => [],
                ],
                'pregnancy' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'ตั้งครรภ์/แพ้ท้อง (154)',
                        '• ตรวจปัสสาวะ',
                        '• แนะนำฝากครรภ์',
                        '• ระวังการใช้ยา',
                    ]),
                    'refs'       => ['154'],
                    'diagrams'   => [],
                ],
                'recovery' => [
                    'urgency'    => 'G',
                    'time_frame' => 'ภายใน 1-2 สัปดาห์',
                    'note'       => implode("\n", [
                        'ระยะฟื้นไข้จากการเจ็บป่วยต่างๆ',
                        '• บำรุงด้วยอาหารที่มีประโยชน์',
                        '• นอนหลับพักผ่อนให้เพียงพอ',
                        '⊕ ถ้าไม่หายเพลียใน 1-2 สัปดาห์',
                    ]),
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'insufficient_rest' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => 'ร่างกายพักผ่อนไม่พอ',
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'sleep_apnea' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 เดือน',
                    'note'       => implode("\n", [
                        'ภาวะหยุดหายใจขณะหลับ (31.1)',
                        '⊕ ภายใน 1 เดือน/ความดันโลหิตสูง',
                    ]),
                    'refs'       => ['31.1'],
                    'diagrams'   => [],
                ],
                'mental_health' => [
                    'urgency'    => 'G',
                    'time_frame' => 'ภายใน 1-2 สัปดาห์',
                    'note'       => implode("\n", [
                        'โรควิตกกังวล/โรคกังวลทั่วไป (88)/โรคอารมณ์แปรปรวน/โรคซึมเศร้า (88.2)',
                        '• ยาทางจิตประสาท (ย17)',
                        '• รักษาตามอาการ',
                        '⊕ ถ้าไม่หายเพลียใน 1-2 สัปดาห์',
                    ]),
                    'refs'       => ['88', '88.2'],
                    'diagrams'   => [],
                ],
                'menopause' => [
                    'urgency'    => 'G',
                    'time_frame' => 'ภายใน 1-2 สัปดาห์',
                    'note'       => implode("\n", [
                        'โรคของหญิงวัยหมดประจำเดือน (129)',
                        '• รักษาตามอาการ',
                        '⊕ ถ้าไม่หายเพลียใน 1-2 สัปดาห์',
                    ]),
                    'refs'       => ['129'],
                    'diagrams'   => [],
                ],
                'self_care' => [
                    'urgency'    => 'G',
                    'time_frame' => 'ภายใน 1-2 สัปดาห์',
                    'note'       => implode("\n", [
                        'ดูแลตนเองเบื้องต้น',
                        '• พักผ่อนให้มากขึ้น',
                        '• หาเวลาทำงานอดิเรกที่ใจรัก',
                        '• ออกกำลังกายเพิ่มขึ้นทีละน้อย',
                        '⊕ ถ้าไม่หายเพลียใน 1-2 สัปดาห์ หรือมีอาการเปลี่ยนแปลงที่ไม่ดี เช่น น้ำหนักลด ซีด ดีซ่าน บวม เป็นต้น',
                    ]),
                    'refs'       => [],
                    'diagrams'   => [],
                ],
            ];

            // 5. บันทึก Diagnosis Rules และข้อมูลที่เกี่ยวข้องลง DB
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
                    'medical_reference' => 'แผนภูมิที่ 5',
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

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

        $this->command->info('สร้างแผนภูมิที่ 5 (อ่อนเพลีย) กรอบ 1-11 สำเร็จ');
    }
}
