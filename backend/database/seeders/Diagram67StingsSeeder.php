<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram67StingsSeeder extends Seeder
{
    private const DIAGRAM_ID = '00067';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = ['1', '2', '3', '4'];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 67 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 67
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'แมลงต่อย (STINGS)',
                'diagram_name_en' => 'Stings',
                'description' => 'บาดแผลถูกผึ้ง แตน ต่อ มด เห็บ แมงมุม แมงป่อง หรือตะขาบกัด ต่อย หรือสัมผัสถูกแมงกะพรุน การปฐมพยาบาล เอาเหล็กไนออก ทาด้วยแอมโมเนียหรือครีมสตีรอยด์ (ย25.6) ให้ยาแก้ปวด (ย1) ยาแก้แพ้ (ย7) ดูรายละเอียดเพิ่มเติม โรคที่ 222 แมลงกัดต่อย',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes)
            $boxes = [
                'B1' => ['frame' => '1', 'type' => 'S', 'q' => 'ช็อก (เป็นลม เหงื่อออก ตัวเย็น)? หรือ หายใจหอบ หรือมีเสียงวี้ด?'],
                'B2' => ['frame' => '2', 'type' => 'S', 'q' => 'บวมคันทั่วตัว? หรือ เป็นลมพิษ?'],
                'B3' => ['frame' => '3', 'type' => 'S', 'q' => 'ปวดแผลมาก?'],
                'B4' => ['frame' => '4', 'type' => 'S', 'q' => 'เป็นตุ่มนูน หรือรอยบวมคัน ตรงบริเวณที่ถูกต่อย?'],
            ];

            // สร้าง box_id ถัดไปอัตโนมัติ
            $nextBoxId = ((int) DB::table('question_boxes')->max('box_id')) + 1;
            foreach ($boxes as &$box) {
                $box['id'] = str_pad((string) $nextBoxId++, 10, '0', STR_PAD_LEFT);
            }
            unset($box);

            // Insert คำถามลง DB
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

            // ตั้งค่า Entry Box
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'entry_box_id' => $boxes['B1']['id'],
            ]);

            // 3. กำหนด Choice และ Flow
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

            // โครงสร้างการตัดสินใจแบบ Binary
            $binary = [
                'B1' => [['anaphylaxis_shock', null], [null, 'B2']],
                'B2' => [['moderate_allergic_reaction', null], [null, 'B3']],
                'B3' => [['severe_local_pain', null], [null, 'B4']],
                'B4' => [['local_skin_reaction', null], ['general_sting_care', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนด Diagnosis Rules
            $rules = [
                'anaphylaxis_shock' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 30 นาที',
                    'note'       => <<<NOTE
แพ้พิษรุนแรง
• เอาเหล็กไนออก
• ฉีดอะดรีนาลีน (ย11) ไดเฟนไฮดรามีน (ย7.2) รานิทิดีน (ย14.3) และเมทิลเพรดนิโซโลน (ย12)
⊕ ถ้าไม่ดีขึ้นใน 30 นาที (ดู "โรคที่ 91 ช็อก" ประกอบ)
NOTE,
                    'refs'       => ['222', '91'],
                    'diagrams'   => [],
                ],
                'moderate_allergic_reaction' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
แพ้พิษปานกลาง
• เอาเหล็กไนออก
• ฉีดยาแก้แพ้ (ย7)
• ถ้าไม่ดีขึ้นฉีดอะดรีนาลีน (ย11)
⊕ ถ้าไม่ดีขึ้นใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['222'],
                    'diagrams'   => [],
                ],
                'severe_local_pain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
พบมากในกรณีที่ถูกผึ้ง ตะขาบหรือแมงป่องต่อย
• ประคบด้วยน้ำแข็ง
• กินยาแก้ปวด (ย1)
• ถ้าปวดมากฉีดยาชาเข้ารอยแผล
NOTE,
                    'refs'       => ['222'],
                    'diagrams'   => [],
                ],
                'local_skin_reaction' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• เอาเหล็กไนออก
• ประคบด้วยน้ำแข็ง
• ทาด้วยครีมสตีรอยด์ (ย25.6)
• ยาแก้แพ้ (ย7)
NOTE,
                    'refs'       => ['222'],
                    'diagrams'   => [],
                ],
                'general_sting_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• เอาเหล็กไนออก
• ทาด้วยแอมโมเนีย หรือครีมสตีรอยด์ (ย25.6) หลังถูกต่อยหรือสัมผัสถูกทันที
• ยาแก้ปวดลดไข้ (ย1)
• ยาแก้แพ้ (ย7) ถ้าคัน
⊕ ถ้าถูกผึ้งหรือต่อหลายตัวต่อย

หมายเหตุ กรณีที่ถูกพิษแมงกะพรุน มีรายละเอียดแตกต่างจากการถูกแมลงต่อยโดยทั่วไป ขอให้ดู "โรคที่ 222" ประกอบ
NOTE,
                    'refs'       => ['222'],
                    'diagrams'   => [],
                ],
            ];

            // 5. Insert Rules, Conditions, Diseases, Next Diagrams
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
                    'medical_reference' => 'แผนภูมิที่ 67',
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

                // Insert Conditions
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

                // Insert Rule Diseases
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

                // Insert Rule Next Diagrams
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

        $this->command->info('สร้างแผนภูมิที่ 67 (แมลงต่อย) สำเร็จ');
    }
}
