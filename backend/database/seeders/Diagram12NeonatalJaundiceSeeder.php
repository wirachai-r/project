<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram12NeonatalJaundiceSeeder extends Seeder
{
    private const DIAGRAM_ID = '00012';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = ['1', '1.1', '1.2', '2', '3', '4'];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 12 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 12
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ดีซ่านในทารกแรกเกิด',
                'diagram_name_en' => 'Jaundice in Newborn',
                'description' => 'ตาเหลือง ตัวเหลือง ปัสสาวะเหลืองเข้มเหมือนขมิ้นซึ่งพบในทารกแรกเกิด (อายุ 0-28 วัน) สาเหตุที่พบบ่อย ดีซ่านสรีระในทารกแรกเกิด (42) ถ้าอาการไม่ชัดเจน ทารกดูดนมได้ และท่าทางสบายดี ให้ทารกดูดน้ำมากๆ ตากแดดอ่อนๆ หรือแสงนีออน',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 12
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => "มีไข้? ซีด? ไม่ดูดนม? อาเจียน?\nตัวอ่อนปวกเปียก? ชัก? จุดแดง\nจ้ำเขียว? ซึม? หรือ เริ่มมีอาการ\nตัวเหลืองภายใน 24 ชั่วโมงหลัง\nคลอด หรือในระยะมากกว่า 3\nวันหลังคลอด?"],
                'B1_1' => ['frame' => '1.1', 'type' => 'S', 'q' => "แม่มีประวัติน้ำเดินก่อนคลอด?\nแม่มีไข้? แม่เลือดบวก? -\nหรือ ทารกสะดือบวมแดง?"],
                'B1_2' => ['frame' => '1.2', 'type' => 'S', 'q' => "แม่มีประวัติได้รับเลือดจากผู้อื่น?\nลูกคนก่อนเคยตัวเหลือง? ซีด?\nหรือ ตับโต ม้ามโต?"],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => "ตัวเหลืองขึ้นเรื่อยๆ นานกว่า 7\nวัน? หรือ อุจจาระสีเหลืองอ่อน\nหรือซีดขาว?"],
                'B3'   => ['frame' => '3',   'type' => 'S', 'q' => 'อาการเหลืองกระจายลง<br>ถึงฝ่าเท้า?'],
                'B4'   => ['frame' => '4',   'type' => 'S', 'q' => 'ดูดนมได้และท่าทางสบายดี?'],
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
                'B1'   => [[null, 'B1_1'], [null, 'B2']],
                'B1_1' => [['septicemia_in_newborn', null], [null, 'B1_2']],
                'B1_2' => [['g6pd_or_hemolytic_anemia', null], ['unexplained_jaundice_24h', null]],
                'B2'   => [['biliary_atresia_24h', null], [null, 'B3']],
                'B3'   => [['severe_jaundice_24h', null], [null, 'B4']],
                'B4'   => [['physiological_jaundice', null], ['hypothyroidism_24h', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'septicemia_in_newborn' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => "โลหิตเป็นพิษใน\nทารกแรกเกิด (229)\n⊕ ด่วน",
                    'refs'       => ['229'],
                    'diagrams'   => [],
                ],
                'g6pd_or_hemolytic_anemia' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => "ภาวะพร่องเอนไซม์\nจี-6-พีดี (101)/\nเม็ดเลือดแดงแตกใน\nทารกแรกเกิด (102)\n⊕ ด่วน",
                    'refs'       => ['101', '102'],
                    'diagrams'   => [],
                ],
                'unexplained_jaundice_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => '⊕ ภายใน 24 ชั่วโมง เพื่อตรวจหาสาเหตุ',
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'biliary_atresia_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => '⊕ ภายใน 24 ชั่วโมง อาจเป็นท่อน้ำดีตีบตันแต่กำเนิด (43)/อื่นๆ',
                    'refs'       => ['43'],
                    'diagrams'   => [],
                ],
                'severe_jaundice_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => '⊕ ภายใน 24 ชั่วโมง ภาวะตัวเหลืองจัด ที่อาจมีอันตรายต่อเด็ก',
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'physiological_jaundice' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => "ดีซ่านสรีระในทารกแรกเกิด (42)\n• ดูดนมและน้ำมากๆ\n• ตากแดดอ่อน ๆ หรือแสงนีออน\n⊕ ถ้าไม่จางลงใน 2 สัปดาห์ หรือฝ่าเท้าเหลือง/\nซึม/ไม่ดูดนม",
                    'refs'       => ['42'],
                    'diagrams'   => [],
                ],
                'hypothyroidism_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => '⊕ ภายใน 24 ชั่วโมง อาจเป็นภาวะขาดไทรอยด์ (124)/อื่นๆ',
                    'refs'       => ['124'],
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
                    'medical_reference' => 'แผนภูมิที่ 12',
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

        $this->command->info('สร้างแผนภูมิที่ 12 (ดีซ่านในทารกแรกเกิด) กรอบ 1-4 สำเร็จ');
    }
}
