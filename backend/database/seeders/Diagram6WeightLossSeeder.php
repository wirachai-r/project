<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram6WeightLossSeeder extends Seeder
{
    private const DIAGRAM_ID = '00006';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.2', '1.3', '2', '3', '4', '5', '6', '7', '8', '9'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 6 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 6
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'น้ำหนักลด (WEIGHT LOSS)',
                'diagram_name_en' => 'Weight Loss',
                'description' => 'น้ำหนักลดลงอย่างน้อยร้อยละ 5 ของน้ำหนักเดิมภายในระยะเวลา 10 สัปดาห์หรือน้อยกว่า โดยไม่ได้ตั้งใจจะลดน้ำหนัก ผู้ป่วยอาจสังเกตว่าเสื้อผ้าที่เคยสวมใช้อยู่เกิดหลวมบริเวณคอ แขน ขา และเอว แก้มตอบ หรือมีคนอื่นทักว่าผอม',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 6
            $boxes = [
                'B1'     => ['frame' => '1',   'type' => 'S', 'q' => 'กินได้ตามปกติ? หรือ กินจุมากกว่าปกติ?'],
                'B1_1'   => [
                    'frame' => '1.1',
                    'type'  => 'S',
                    'q'     => implode("\n", [
                        'มีอาการอย่างน้อย 2 อย่างดังต่อไปนี้',
                        '- เหนื่อยง่าย?',
                        '- ขี้ร้อน (เหงื่อออกมาก)?',
                        '- มือสั่น?',
                        '- คอพอก?',
                        '- ตาโปน?',
                        '- ชีพจร > 120 ครั้ง/นาที?'
                    ])
                ],
                'B1_2'   => ['frame' => '1.2', 'type' => 'S', 'q' => 'กระหายน้ำบ่อย ปัสสาวะบ่อยและมาก? หรือ ตรวจพบน้ำตาลในปัสสาวะหรือน้ำตาลในเลือดสูง?'],
                'B1_3'   => ['frame' => '1.3', 'type' => 'S', 'q' => 'ระยะใกล้ๆนี้ ทำงาน หรือออกกำลังมากกว่ปกติ?'],
                'B2'     => ['frame' => '2',   'type' => 'S', 'q' => 'มีไข้?'],
                'B3'     => ['frame' => '3',   'type' => 'S', 'q' => 'พบในผู้หญิงที่แต่งงานแล้วและประจำเดือนขาด? หรือ สงสัยตั้งครรภ์?'],
                'B4'     => ['frame' => '4',   'type' => 'S', 'q' => 'มีก้อนแข็งผิวขรุขระ ในช่องท้องตรงบริเวณใต้ชายโครงขวา?'],
                'B5'     => [
                    'frame' => '5',
                    'type'  => 'S',
                    'q'     => implode("\n", [
                        'กลืนลำบาก? เสียงแหบเรื้อรัง?',
                        'ไอเรื้อรัง? ไอเป็นเลือด?',
                        'จุกแน่นท้องเรื้อรัง? คลื่นไส้ อาเจียนบ่อย? ตาเหลือง (ดีซ่าน)? ท้องเดินเรื้อรัง?',
                        'ถ่ายเป็นเลือด? เลือดออกทางช่องคลอด? มีก้อนแข็งที่ข้างคอ/ไหปลาร้า/รักแร้/เต้านม? หรือ คลำได้ก้อนในท้อง?'
                    ])
                ],
                'B6'     => ['frame' => '6',   'type' => 'S', 'q' => 'มีจุดแดงรูปแมงมุมที่หน้าอก/ต้นแขน? หรือ ฝ่ามือแดง?'],
                'B7'     => ['frame' => '7',   'type' => 'S', 'q' => 'อ่อนเพลีย หน้ามืด และผิวหนังตามตัว ปากดำ/ส้นดำ?'],
                'B8'     => ['frame' => '8',   'type' => 'S', 'q' => 'ในผู้หญิงที่มีประวัติตกเลือด หรือเป็นลมขณะคลอดบุตร แล้วค่อยๆ ซูบผอมลง และประจำเดือนไม่มา?'],
                'B9'     => ['frame' => '9',   'type' => 'S', 'q' => 'มีเรื่องคิดมาก กังวลใจ เสียใจ หรือกลุ้มใจ? นอนไม่หลับ? หรือ มีอารมณ์ซึมเศร้า?'],
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
                'B1'    => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'  => [['hyperthyroidism', null], [null, 'B1_2']],
                'B1_2'  => [['diabetes', null], [null, 'B1_3']],
                'B1_3'  => [['increased_energy_expenditure', null], [null, 'B2']],
                'B2'    => [['fever_causes', null], [null, 'B3']],
                'B3'    => [['morning_sickness', null], [null, 'B4']],
                'B4'    => [['liver_cancer', null], [null, 'B5']],
                'B5'    => [['chronic_symptoms_cancer_others', null], [null, 'B6']],
                'B6'    => [['hepatomegaly', null], [null, 'B7']],
                'B7'    => [['addisons_disease', null], [null, 'B8']],
                'B8'    => [['sheehans_syndrome', null], [null, 'B9']],
                'B9'    => [['anxiety_depression', null], ['unexplained_weight_loss', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'hyperthyroidism' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => "ภาวะต่อมไทรอยด์ทำงานเกิน/คอพอกเป็นพิษ (121)",
                    'refs'       => ['121'],
                    'diagrams'   => [],
                ],
                'diabetes' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => "เบาหวาน (117)",
                    'refs'       => ['117'],
                    'diagrams'   => [],
                ],
                'increased_energy_expenditure' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'ร่างกายใช้พลังงานมากขึ้น',
                        '• หาทางลดภาระงาน',
                        '• กินอาหารให้มากขึ้น',
                        '⊕ ถ้าน้ำหนักลดต่อไปอีก หรือรู้สึกอ่อนเพลีย เหนื่อยง่าย'
                    ]),
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'fever_causes' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => implode("\n", [
                        'อาจเป็นวัณโรคปอด (14)/เอดส์ (238)/บรูเซลโลซิส (229.4)/เมลิออยโดซิส (229.2)/เยื่อบุหัวใจอักเสบ (95)/มะเร็ง (237)/สาเหตุร้ายแรงอื่นๆ',
                        '(ดูแผนภูมิที่ 1 ไข้ ประกอบ)'
                    ]),
                    'refs'       => ['14', '238', '229.4', '229.2', '95', '237'],
                    'diagrams'   => ['00001'],
                ],
                'morning_sickness' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'แพ้ท้อง (154)',
                        '• ตรวจปัสสาวะ',
                        '• แนะนำฝากครรภ์',
                        '• ระวังการใช้ยา',
                        '• งดดื่มแอลกอฮอล์ งดบุหรี่'
                    ]),
                    'refs'       => ['154'],
                    'diagrams'   => [],
                ],
                'liver_cancer' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => "อาจเป็นมะเร็งตับ (45)",
                    'refs'       => ['45'],
                    'diagrams'   => [],
                ],
                'chronic_symptoms_cancer_others' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => "อาจเป็นมะเร็ง (237)/เมลิออยโดซิส (229.2)/หรือสาเหตุอื่นๆ",
                    'refs'       => ['237', '229.2'],
                    'diagrams'   => [],
                ],
                'hepatomegaly' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => "ตับแข็ง (44)",
                    'refs'       => ['44'],
                    'diagrams'   => [],
                ],
                'addisons_disease' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => "โรคแอดดิสัน (126)",
                    'refs'       => ['126'],
                    'diagrams'   => [],
                ],
                'sheehans_syndrome' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => "โรคชีแฮน (127)",
                    'refs'       => ['127'],
                    'diagrams'   => [],
                ],
                'anxiety_depression' => [
                    'urgency'    => 'G',
                    'time_frame' => 'ถ้าไม่ดีขึ้นใน 1 สัปดาห์',
                    'note'       => implode("\n", [
                        'วิตกกังวล/โรคกังวลทั่วไป (88)/โรคอารมณ์แปรปรวน/โรคซึมเศร้า (88.2)',
                        '• ยาทางจิตประสาท (ยา17)'
                    ]),
                    'refs'       => ['88', '88.2'],
                    'diagrams'   => [],
                ],
                'unexplained_weight_loss' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => "เพื่อตรวจหาสาเหตุ",
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
                    'urgency_level'     => $rule['urgency'],
                    'time_frame'        => $rule['time_frame'],
                    'time_frame_en'     => null,
                    'note'              => $rule['note'],
                    'note_en'           => null,
                    'medical_reference' => 'แผนภูมิที่ 6',
                    'status'            => '1',
                    'diagram_id'        => self::DIAGRAM_ID,
                    'updated_at'        => $now,
                    'created_at'        => $now,
                ]);

                // สร้างเงื่อนไข (Rule Conditions)
                DB::table('rule_conditions')->where('rule_id', $ruleId)->delete();
                foreach ($terminalChoices[$key] ?? [] as $conditionIndex => $condition) {
                    DB::table('rule_conditions')->insert([
                        'condition_id'   => str_pad((string) $conditionNumber++, 10, '0', STR_PAD_LEFT),
                        'rule_id'        => $ruleId,
                        'box_id'         => $condition['box_id'],
                        'choice_id'      => $condition['choice_id'],
                        'logic_operator' => $conditionIndex === 0 ? 'AND' : 'OR',
                        'status'         => '1',
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]);
                }

                // ผูกรหัสโรค (Rule Diseases)
                DB::table('rule_diseases')->where('rule_id', $ruleId)->delete();
                foreach ($rule['refs'] as $order => $reference) {
                    $diseaseId = DB::table('diseases')->where('reference', $reference)->value('disease_id');
                    if ($diseaseId) {
                        DB::table('rule_diseases')->insert([
                            'rule_id'       => $ruleId,
                            'disease_id'    => $diseaseId,
                            'display_order' => $order,
                            'created_at'    => $now,
                            'updated_at'    => $now,
                        ]);
                    }
                }

                // ผูกแผนภูมิถัดไป (Rule Next Diagrams)
                DB::table('rule_next_diagrams')->where('rule_id', $ruleId)->delete();
                foreach ($rule['diagrams'] as $order => $diagramId) {
                    DB::table('rule_next_diagrams')->insert([
                        'rule_id'       => $ruleId,
                        'diagram_id'    => $diagramId,
                        'display_order' => $order,
                        'prompt_text'   => 'ต้องการประเมินอาการนี้ต่อหรือไม่',
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }

                $ruleNumber++;
            }
        });

        $this->command->info('สร้างแผนภูมิที่ 6 (น้ำหนักลด / WEIGHT LOSS) กรอบ 1-9 สำเร็จ');
    }
}
