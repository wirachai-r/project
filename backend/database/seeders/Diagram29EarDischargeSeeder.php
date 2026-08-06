<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram29EarDischargeSeeder extends Seeder
{
    private const DIAGRAM_ID = '00029';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.2', '1.3',
                '2', '2.1', '2.2', '2.3'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 29 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 29
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'หูมีหนองไหล/เลือดออก (EAR DISCHARGE)',
                'diagram_name_en' => 'Ear Discharge',
                'description' => 'มีหนองไหลหรือเลือดออกจากหู สาเหตุที่พบบ่อย หูชั้นนอกอักเสบ (161) หูชั้นกลางอักเสบ (163) แผลถลอกในช่องหู (168) ถ้าอาการไม่ชัดเจน 1. ถ้าเป็นหูน้ำหนวกให้ยาปฏิชีวนะ และปรึกษาแพทย์ถ้าไม่ดีขึ้นใน 1 สัปดาห์ 2. ถ้ามีเลือดไหลไม่หายภายใน 1 สัปดาห์ ควรปรึกษาแพทย์',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 29
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => 'หูมีหนองไหล?'],
                'B1_1' => ['frame' => '1.1', 'type' => 'S', 'q' => 'ปวดหูมาก เวลาดึงถูกใบหู?'],
                'B1_2' => ['frame' => '1.2', 'type' => 'S', 'q' => 'เป็นมาเรื้อรัง? หรือ ใช้เครื่องส่องหูพบเยื่อแก้วหูมีรูทะลุ?'],
                'B1_3' => ['frame' => '1.3', 'type' => 'S', 'q' => 'หลังเป็นไข้หวัด เจ็บคอ หรือมีไข้? หรือ ใช้เครื่องส่องหูพบเยื่อแก้วหูทะลุ?'],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => 'มีเลือดไหล?'],
                'B2_1' => ['frame' => '2.1', 'type' => 'S', 'q' => 'หลังได้รับบาดเจ็บที่ศีรษะ?'],
                'B2_2' => ['frame' => '2.2', 'type' => 'S', 'q' => 'หลังแคะหูหรือแยงหู?'],
                'B2_3' => ['frame' => '2.3', 'type' => 'S', 'q' => 'หลังนั่งเครื่องบินหรือดำน้ำลึก?'],
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
                'B1_1' => [['otitis_externa', null], [null, 'B1_2']],
                'B1_2' => [['chronic_otitis_media', null], [null, 'B1_3']],
                'B1_3' => [['acute_otitis_media_discharge', null], [null, 'B2']],
                'B2'   => [[null, 'B2_1'], ['other_abnormalities', null]],
                'B2_1' => [['head_injury_ear_bleed', null], [null, 'B2_2']],
                'B2_2' => [['ear_canal_abrasion', null], [null, 'B2_3']],
                'B2_3' => [['barotrauma_ear', null], ['unexplained_ear_bleeding', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'otitis_externa' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูชั้นนอกอักเสบ (161)
• ยาแก้ปวด (ย1)
• ไดคล็อกซาซิลลิน (ย4.3) หรืออีริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['161'],
                    'diagrams'   => [],
                ],
                'chronic_otitis_media' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูชั้นกลางอักเสบเรื้อรัง (163)
• เช็ดหูให้แห้งและใช้ยาหยอดหูปฏิชีวนะ (ย25.12)
• ถ้าปวด หรือมีไข้
  - อะม็อกซีซิลลิน (ย4.2) หรือ โคไตรม็อกซาโซล (ย4.7) หรือ อีริโทรไมซิน (ย4.4)
  - ยาแก้ปวดลดไข้ (ย1)
⊕ ถ้าหูน้ำหนวกไม่แห้ง หรือแก้วหูทะลุเป็นรู ปิดเองไม่ได้
NOTE,
                    'refs'       => ['163'],
                    'diagrams'   => [],
                ],
                'acute_otitis_media_discharge' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูชั้นกลางอักเสบเฉียบพลัน (163)
• เช็ดหูให้แห้งและใช้ยาหยอดหูปฏิชีวนะ (ย25.12)
• ถ้าปวด หรือมีไข้
  - อะม็อกซีซิลลิน (ย4.2) หรือ โคไตรม็อกซาโซล (ย4.7) หรือ อีริโทรไมซิน (ย4.4)
  - ยาแก้ปวดลดไข้ (ย1)
⊕ ถ้าหูน้ำหนวกไม่แห้ง หรือแก้วหูทะลุเป็นรู ปิดเองไม่ได้
NOTE,
                    'refs'       => ['163'],
                    'diagrams'   => [],
                ],
                'other_abnormalities' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ถ้ามีความผิดปกติอื่นๆ ตรวจดูอาการเพิ่มเติม
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'head_injury_ear_bleed' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ศีรษะได้รับบาดเจ็บ (81)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['81'],
                    'diagrams'   => [],
                ],
                'ear_canal_abrasion' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
แผลถลอกในช่องหู (168)
• ยาแก้ปวด (ย1)
• ไดคล็อกซาซิลลิน (ย4.3) หรืออีริโทรไมซิน (ย4.4) ถ้ามีการติดเชื้อ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['168'],
                    'diagrams'   => [],
                ],
                'barotrauma_ear' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
หูบาดเจ็บจากความกดดันอากาศ (170.1)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['170.1'],
                    'diagrams'   => [],
                ],
                'unexplained_ear_bleeding' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ถ้าไม่หายภายใน 1 สัปดาห์
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
                    'medical_reference' => 'แผนภูมิที่ 29',
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

        $this->command->info('สร้างแผนภูมิที่ 29 (หูมีหนองไหล/เลือดออก - EAR DISCHARGE) กรอบ 1-2.3 สำเร็จ');
    }
}
