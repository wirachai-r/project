<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram9AnemiaWithFeverSeeder extends Seeder
{
    private const DIAGRAM_ID = '00009';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.2', '1.2.1', '2', '3'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 9 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 9
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ซีด/โลหิตจาง ร่วมกับมีไข้',
                'diagram_name_en' => 'Paleness / Anemia with Fever',
                'description' => 'มีอาการโลหิตจาง พร้อมกับตัวร้อน อุณหภูมิของร่างกายสูงกว่า 37.2 °ซ โดยการวัดทางปาก สาเหตุที่พบบ่อย: มาลาเรีย (224), ไทฟอยด์ (37), ทาลาสซีเมีย (105), โลหิตจางจากเม็ดเลือดแดงแตก (101) / ถ้าอาการไม่ชัดเจน ควรส่งไปรักษาที่โรงพยาบาลภายใน 3 วัน',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 9
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'จับไข้หนาวสั่นมาก? หรือ ตาเหลือง (ดีซ่าน)?'],
                'B1_1'    => ['frame' => '1.1',   'type' => 'S', 'q' => 'เคยเข้าไปในดงมาลาเรีย หรือได้รับการถ่ายเลือด ภายในระยะหลายเดือนที่ผ่านมา?'],
                'B1_2'    => ['frame' => '1.2',   'type' => 'S', 'q' => 'ปัสสาวะสีน้ำปลา หรือ สีโค้ก?'],
                'B1_2_1'  => ['frame' => '1.2.1', 'type' => 'S', 'q' => 'หน้าตาแปลก? ซีดเหลืองมาตั้งแต่เด็ก? หรือ ม้ามโต?'],
                'B2'      => [
                    'frame' => '2',
                    'type'  => 'S',
                    'q'     => 'มีไข้เกิน 7 วัน? ต่อมน้ำเหลืองโตทั่วไป? มีจุดแดงจ้ำเขียว? มีเลือดออก (เช่น เลือดกำเดา เลือดออกตามไรฟัน ถ่ายเป็นเลือด)? หรือ น้ำหนักลดฮวบ?'
                ],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'ปวดข้อนิ้วมือ 2 ข้าง? ผมร่วง? หรือ มีผื่นปีกผีเสื้อที่ข้างจมูก?'],
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
                'B1_1'   => [['malaria', null], [null, 'B1_2']],
                'B1_2'   => [[null, 'B1_2_1'], ['severe_causes_24h', null]],
                'B1_2_1' => [['thalassemia_24h', null], ['hemolytic_anemia_24h', null]],
                'B2'     => [['fever_anemia_complex_causes', null], [null, 'B3']],
                'B3'     => [['sle', null], ['unexplained_anemia_fever', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'malaria' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => 'มาลาเรีย (224)
⊕ ภายใน 24 ชั่วโมง',
                    'refs'       => ['224'],
                    'diagrams'   => [],
                ],
                'severe_causes_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => '⊕ ภายใน 24 ชั่วโมง อาจมีสาเหตุร้ายแรงอื่นๆ',
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'thalassemia_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => 'ทาลาสซีเมีย (105)
⊕ ภายใน 24 ชั่วโมง',
                    'refs'       => ['105'],
                    'diagrams'   => [],
                ],
                'hemolytic_anemia_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => 'โลหิตจางจากเม็ดเลือดแดงแตก (101)
⊕ ภายใน 24 ชั่วโมง',
                    'refs'       => ['101'],
                    'diagrams'   => [],
                ],
                'fever_anemia_complex_causes' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => '⊕ ภายใน 24 ชั่วโมง อาจเป็นมะเร็งเม็ดเลือดขาว (106)/โลหิตจางจากไขกระดูกฝ่อ (103)/เล็ปโตสไปโรซิส (227)/โลหิตเป็นพิษ (228)/ไข้เลือดออก (225)/เอสแอลอี (111)/เยื่อบุหัวใจอักเสบ (95)/ไทฟอยด์ (37)/เอดส์ (238)/วัณโรคปอด (14)/เมลิออยโดซิส (229.2)/บรูเซลโลซิส (229.4)',
                    'refs'       => ['106', '103', '227', '228', '225', '111', '95', '37', '238', '14', '229.2', '229.4'],
                    'diagrams'   => [],
                ],
                'sle' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => 'เอสแอลอี (111)
⊕ ภายใน 3 วัน',
                    'refs'       => ['111'],
                    'diagrams'   => [],
                ],
                'unexplained_anemia_fever' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => '⊕ ภายใน 3 วัน เพื่อตรวจหาสาเหตุ',
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
                    'time_frame' => $rule['time_frame'] ?? null,
                    'time_frame_en' => null,
                    'note' => $rule['note'],
                    'note_en' => null,
                    'medical_reference' => 'แผนภูมิที่ 9',
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

        $this->command->info('สร้างแผนภูมิที่ 9 (ซีด/โลหิตจาง ร่วมกับมีไข้) กรอบ 1-3 สำเร็จ');
    }
}
