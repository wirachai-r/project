<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram31EpistaxisSeeder extends Seeder
{
    private const DIAGRAM_ID = '00031';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '5', '6', '7'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 31 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 31
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'เลือดกำเดาไหล (EPISTAXIS)',
                'diagram_name_en' => 'Epistaxis',
                'description' => 'มีเลือดไหลออกจากจมูกข้างเดียวหรือสองข้าง สาเหตุที่พบบ่อย ไข้หวัด (1) สาเหตุจากแรงกระแทก (30) ความดันโลหิตสูง (92) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 7',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 31
            $boxes = [
                'B1' => [
                    'frame' => '1',
                    'type' => 'S',
                    'q' => "เลือดกำเดาหยุดยาก?\nมีจุดแดง/จ้ำเขียวตามตัว?\nมีเลือดออกที่อื่นร่วมด้วย? ซีด?\nมีไข้เกิน 7 วัน? หรือ ตับ/ม้ามโต?"
                ],
                'B2' => [
                    'frame' => '2',
                    'type' => 'S',
                    'q' => 'ตรวจพบก้อนเนื้อในรูจมูก?'
                ],
                'B3' => [
                    'frame' => '3',
                    'type' => 'S',
                    'q' => "มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้\n- น้ำหนักลด?\n- มีก้อนที่ข้างคอ?\n- เสียงแหบนานกว่า 3 สัปดาห์?\n- คัดจมูกเรื้อรัง?\n- หูอื้อเรื้อรัง?"
                ],
                'B4' => [
                    'frame' => '4',
                    'type' => 'S',
                    'q' => 'ความดันโลหิตช่วงบน >= 140 หรือช่วงล่าง >= 90 มม.ปรอท?'
                ],
                'B5' => [
                    'frame' => '5',
                    'type' => 'S',
                    'q' => 'เป็นหวัด?'
                ],
                'B6' => [
                    'frame' => '6',
                    'type' => 'S',
                    'q' => 'ได้รับแรงกระแทก?'
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
                'B1' => [['bleeding_disorder_leukemia_dengue', null], [null, 'B2']],
                'B2' => [['nasal_polyp', null], [null, 'B3']],
                'B3' => [['nasopharyngeal_carcinoma', null], [null, 'B4']],
                'B4' => [['hypertension', null], [null, 'B5']],
                'B5' => [['refer_diagram_30', null], [null, 'B6']],
                'B6' => [['epistaxis_trauma', null], ['epistaxis_first_aid', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'bleeding_disorder_leukemia_dengue' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง
อาจเป็นโรคเลือด (103, 104, 106)/ไข้เลือดออก (225)/โลหิตเป็นพิษ (228)
NOTE,
                    'refs'       => ['103', '104', '106', '225', '228'],
                    'diagrams'   => [],
                ],
                'nasal_polyp' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ติ่งเนื้อเมือกจมูก (28)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['28'],
                    'diagrams'   => [],
                ],
                'nasopharyngeal_carcinoma' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
มะเร็งโพรงหลังจมูก (237.8)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['237.8'],
                    'diagrams'   => [],
                ],
                'hypertension' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ความดันโลหิตสูง (92)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['92'],
                    'diagrams'   => [],
                ],
                'refer_diagram_30' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 30 คัดจมูก/น้ำมูกไหล กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00030'],
                ],
                'epistaxis_trauma' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เลือดกำเดาไหลจากแรงกระแทก (30)

ปฐมพยาบาล:
• นั่งก้มหน้าลงมาก ๆ ใช้นิ้วกดปีกจมูกทั้ง 2 ข้าง หายใจลึกๆ ทางปากแทน
⊕ ถ้าไม่หยุดไหลใน 2-3 ชั่วโมง หรือมีไข้เกิน 7 วัน/มีประวัติศีรษะได้รับบาดเจ็บ/เป็นๆ หายๆ บ่อย
NOTE,
                    'refs'       => ['30'],
                    'diagrams'   => [],
                ],
                'epistaxis_first_aid' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปฐมพยาบาล:
• นั่งก้มหน้าลงมาก ๆ ใช้นิ้วกดปีกจมูกทั้ง 2 ข้าง หายใจลึกๆ ทางปากแทน
⊕ ถ้าไม่หยุดไหลใน 2-3 ชั่วโมง หรือมีไข้เกิน 7 วัน/มีประวัติศีรษะได้รับบาดเจ็บ/เป็นๆ หายๆ บ่อย
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
                    'medical_reference' => 'แผนภูมิที่ 31',
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

        $this->command->info('สร้างแผนภูมิที่ 31 (เลือดกำเดาไหล - EPISTAXIS) กรอบ 1-7 สำเร็จ');
    }
}
