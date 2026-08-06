<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram35SoreThroatSeeder extends Seeder
{
    private const DIAGRAM_ID = '00035';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '2', '3', '4', '4.1',
                '5', '6'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 35 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 35
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'เจ็บคอ (SORE THROAT)',
                'diagram_name_en' => 'Sore Throat',
                'description' => 'รู้สึกเจ็บหรือแสบภายในลำคอ สาเหตุที่พบบ่อย : หวัดภูมิแพ้ (25) ไข้หวัด (1) ไข้หวัดใหญ่ (2) ทอนซิลอักเสบ (8) ก้างปลาหรือกระดูกติดคอ (215) โรคกรดไหลย้อน (49.1) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 7',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 35
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => "หายใจลำบาก?\nหรือ ไอเสียงก้อง?"],
                'B1_1' => ['frame' => '1.1', 'type' => 'S', 'q' => "มีแผ่นเยื่อสีเทา/เหลือง\nปนเทาในลำคอ?"],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => 'ทอนซิลโตแดง หรือเป็นหนอง?'],
                'B3'   => ['frame' => '3',   'type' => 'S', 'q' => "คัดจมูกหรือน้ำมูกไหล?\nหรือ ไอ?"],
                'B4'   => ['frame' => '4',   'type' => 'S', 'q' => 'มีไข้?'],
                'B4_1' => ['frame' => '4.1', 'type' => 'S', 'q' => "ต่อมน้ำเหลืองใต้คาง\nบวมและปวด?"],
                'B5'   => ['frame' => '5',   'type' => 'S', 'q' => 'เกิดขึ้นทันทีขณะกินอาหาร?'],
                'B6'   => ['frame' => '6',   'type' => 'S', 'q' => "เจ็บคอเฉพาะช่วงหลังตื่นนอน?\nและ มีอาการจุกแน่นหรือแสบ\nลิ้นปี่ หรือเรอเปรี้ยว เป็นๆหายๆ\nเรื้อรัง?"],
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
                'B1_1' => [['diphtheria', null], ['croup_anthrax_severe', null]],
                'B2'   => [['tonsillitis', null], [null, 'B3']],
                'B3'   => [['refer_diagram_30_38', null], [null, 'B4']],
                'B4'   => [[null, 'B4_1'], [null, 'B5']],
                'B4_1' => [['cervical_lymphadenitis', null], ['symptomatic_sore_throat', null]],
                'B5'   => [['foreign_body_throat', null], [null, 'B6']],
                'B6'   => [['gerd', null], ['general_sore_throat_care', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'diphtheria' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
คอดิบ (10)
⊕ ด่วน
NOTE,
                    'refs'       => ['10'],
                    'diagrams'   => [],
                ],
                'croup_anthrax_severe' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นครู้ป (11)/แอนแทรกซ์ (229.3)/สาเหตุร้ายแรงอื่น ๆ
NOTE,
                    'refs'       => ['11', '229.3'],
                    'diagrams'   => [],
                ],
                'tonsillitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ทอนซิลอักเสบ (8)
• ยาแก้ปวดลดไข้ (ย1)
• เพนิซิลลินวี (ย4.1) หรืออะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน
ถ้าดีขึ้น กินยาจนครบ 10 วัน
NOTE,
                    'refs'       => ['8'],
                    'diagrams'   => [],
                ],
                'refer_diagram_30_38' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 30 คัดจมูก/น้ำมูกไหล กรอบที่ 1
38 ไอ กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00030', '00038'],
                ],
                'cervical_lymphadenitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ต่อมน้ำเหลืองอักเสบ (194)
• เพนิซิลลินวี (ย4.1) หรือไดคล็อกซาซิลลิน (ย4.3) หรืออีริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน
NOTE,
                    'refs'       => ['194'],
                    'diagrams'   => [],
                ],
                'symptomatic_sore_throat' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ
• ยาลดไข้ (ย1)
• น้ำเกลือกลั้วคอ
⊕ ถ้าไม่ดีขึ้นใน 4 วัน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'foreign_body_throat' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ก้างปลาหรือกระดูกติดคอ (215)
• คีบออก (ถ้ามองเห็น)
• ให้กลืนก้อนข้าวสุก/กล้วย/ขนมปังนิ่มๆ (ถ้ามองไม่เห็น)
⊕ ถ้าไม่หายเจ็บใน 3 วัน
NOTE,
                    'refs'       => ['215'],
                    'diagrams'   => [],
                ],
                'gerd' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคกรดไหลย้อน (49.1)
• ยาต้านกรด (ย14.1)
• รานิทิดีน (ย14.3)
• นอนหมอนสูง
• หลีกเลี่ยงสิ่งกระตุ้น
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['49.1'],
                    'diagrams'   => [],
                ],
                'general_sore_throat_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ใช้น้ำเกลือกลั้วคอ
• งดบุหรี่ และแอลกอฮอล์
• พักการใช้เสียง
• ถ้าคันคอ/คัดจมูกให้ยาแก้แพ้ (ย7)
⊕ ถ้าไม่หายใน 2 สัปดาห์ หรือมีก้อนโตกว่า 1 ซม.ที่ข้างคอ/น้ำหนักลด/เจ็บคอหรือเสียงแหบนานเกิน 3 สัปดาห์
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
                    'medical_reference' => 'แผนภูมิที่ 35',
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

        $this->command->info('สร้างแผนภูมิที่ 35 (เจ็บคอ - SORE THROAT) กรอบ 1-7 สำเร็จ');
    }
}
