<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram26EaracheSeeder extends Seeder
{
    private const DIAGRAM_ID = '00026';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '5',
                '6', '7', '8', '9', '10', '11'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 26 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 26
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ปวดหู (EARACHE)',
                'diagram_name_en' => 'Earache',
                'description' => 'ปวดในหูหรือรอบๆ หูข้างหนึ่งหรือสองข้าง สาเหตุที่พบบ่อย ขี้หูอุดตันรูหู (169) หูชั้นนอกอักเสบ (161) หูชั้นกลางอักเสบ (163) โรคเชื้อราในช่องหู (162) คางทูม (7) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 11',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 26
            $boxes = [
                'B1'  => ['frame' => '1',  'type' => 'S', 'q' => 'ปวดมากเวลาดึงใบหู? หรือ มีหนองไหล?'],
                'B2'  => ['frame' => '2',  'type' => 'S', 'q' => "พบร่วมกับเป็นไข้หวัด หรือเจ็บคอ และมีอาการไข้สูง และหูอื้อ?\nหรือ ใช้เครื่องส่องพบเยื่อแก้วหูบวมแดง?"],
                'B3'  => ['frame' => '3',  'type' => 'S', 'q' => 'แมลงเข้าหู หรือสิ่งแปลกปลอมเข้าหู? หรือ ใช้เครื่องส่องพบแมลงหรือสิ่งแปลกปลอม?'],
                'B4'  => ['frame' => '4',  'type' => 'S', 'q' => 'เป็นหลังแคะหู?'],
                'B5'  => ['frame' => '5',  'type' => 'S', 'q' => 'มีอาการหลังนั่งเครื่องบิน หรือดำน้ำลึก?'],
                'B6'  => ['frame' => '6',  'type' => 'S', 'q' => 'คันในรูหู? หรือ มีขุยขาว น้ำตาล หรือดำในรูหู?'],
                'B7'  => ['frame' => '7',  'type' => 'S', 'q' => 'ปวดฟัน? หรือ เหงือกอักเสบ?'],
                'B8'  => ['frame' => '8',  'type' => 'S', 'q' => 'เป็นคางทูม?'],
                'B9'  => ['frame' => '9',  'type' => 'S', 'q' => 'ปากเบี้ยวและหลับตาไม่มิดข้างหนึ่ง?'],
                'B10' => ['frame' => '10', 'type' => 'S', 'q' => "มีอาการหูอื้อร่วมด้วยเกิดขึ้นทันทีหลังว่ายน้ำ/ดำน้ำ?\nหรือ ใช้เครื่องส่องหูพบมีขี้หูอุดตัน?"],
                'B11' => ['frame' => '11', 'type' => 'S', 'q' => 'ปวดหูโดยไม่มีอาการชัดเจนข้างต้น?'],
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
                'B1'  => [['otitis_externa', null], [null, 'B2']],
                'B2'  => [['acute_otitis_media', null], [null, 'B3']],
                'B3'  => [['foreign_body_in_ear', null], [null, 'B4']],
                'B4'  => [['ear_canal_abrasion', null], [null, 'B5']],
                'B5'  => [['barotrauma', null], [null, 'B6']],
                'B6'  => [['otomycosis', null], [null, 'B7']],
                'B7'  => [['toothache_gingivitis', null], [null, 'B8']],
                'B8'  => [['mumps', null], [null, 'B9']],
                'B9'  => [['bells_palsy', null], [null, 'B10']],
                'B10' => [['impacted_cerumen', null], [null, 'B11']],
                'B11' => [['unclear_earache_care', null], ['unclear_earache_care', null]],
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
• ไดคลอกซาซิลลิน (ย4.3) หรืออีริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['161'],
                    'diagrams'   => [],
                ],
                'acute_otitis_media' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูชั้นกลางอักเสบเฉียบพลัน (163)
• ยาแก้ปวดลดไข้ (ย1)
• อะโมกซีซิลลิน (ย4.2) หรือโคไตรม็อกซาโซล (ย4.7) หรืออีริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน
(ถ้าดีขึ้น กินยาจนครบ 10 วัน)
NOTE,
                    'refs'       => ['163'],
                    'diagrams'   => [],
                ],
                'foreign_body_in_ear' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สิ่งแปลกปลอมเข้าหู (170)
• เอียงหูข้างนั้นต่ำ เคาะที่ศีรษะเบาๆ
• สำหรับแมลงเข้าหู ให้หยอดหูด้วยน้ำมันพืช หรือกลีเซอรีนโบแรกซ์ (ย25.8)
⊕ ถ้าไม่ได้ผล
NOTE,
                    'refs'       => ['170'],
                    'diagrams'   => [],
                ],
                'ear_canal_abrasion' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
แผลถลอกในช่องหู (168)
• ยาแก้ปวด (ย1)
• ไดคลอกซาซิลลิน (ย4.3) หรืออีริโทรไมซิน (ย4.4) ถ้ามีการติดเชื้อ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['168'],
                    'diagrams'   => [],
                ],
                'barotrauma' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูบาดเจ็บจากความกดอากาศ (170.1)
• ยาแก้ปวด (ย1)
• สูโดเอเฟดรีน (ย8.2) 60 มก. ทุก 6 ชั่วโมง
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือปวดมาก/มีเลือดออก
NOTE,
                    'refs'       => ['170.1'],
                    'diagrams'   => [],
                ],
                'otomycosis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคเชื้อราในช่องหู (162)
• เช็ดภายในช่องหูด้วยโพวิโดนไอโอดีน
• ใช้ยาหยอดหูที่เข้ารถยารักษาเชื้อรา
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['162'],
                    'diagrams'   => [],
                ],
                'toothache_gingivitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปวดฟัน/ฟันผุ (60)/เหงือกอักเสบ (61)
• ยาแก้ปวด (ย1)
• ยาปฏิชีวนะ (ย4)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['60', '61'],
                    'diagrams'   => [],
                ],
                'mumps' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
คางทูม (7)
• พาราเซตามอล (ย1.2)
⊕ ถ้าไม่หายใน 2 สัปดาห์
NOTE,
                    'refs'       => ['7'],
                    'diagrams'   => [],
                ],
                'bells_palsy' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2-3 สัปดาห์',
                    'note'       => <<<NOTE
อัมพาตเบลล์/อัมพาตใบหน้าครึ่งซีก (77)
• ตรวจหาสาเหตุ
• ถ้าเป็นอัมพาตเบลล์ให้เพร็ดนิโซโลน (ย12)
⊕ ถ้าไม่ดีขึ้นใน 2-3 สัปดาห์
NOTE,
                    'refs'       => ['77'],
                    'diagrams'   => [],
                ],
                'impacted_cerumen' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ขี้หูอุดตันรูหู (169)
• ถ้าขี้หูไม่แน่นมาก เขี่ยหรือใช้น้ำฉีดล้างออก
• ถ้าขี้หูแน่นมาก หยอดยาละลายขี้หู (ย25.13) 3-5 วัน แล้วใช้น้ำฉีดล้างออก
⊕ ถ้าไม่ได้ผล หรือเจ็บหูมาก
NOTE,
                    'refs'       => ['169'],
                    'diagrams'   => [],
                ],
                'unclear_earache_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ปวด (ย1)
⊕ ถ้าไม่หายปวดใน 2 สัปดาห์
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
                    'medical_reference' => 'แผนภูมิที่ 26',
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

        $this->command->info('สร้างแผนภูมิที่ 26 (ปวดหู - EARACHE) กรอบ 1-11 สำเร็จ');
    }
}
