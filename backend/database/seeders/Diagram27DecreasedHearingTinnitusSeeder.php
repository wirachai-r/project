<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram27DecreasedHearingTinnitusSeeder extends Seeder
{
    private const DIAGRAM_ID = '00027';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '5', '5.1',
                '6', '7', '8', '9', '10', '11', '12'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 27 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 27
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'หูอื้อ (DECREASED HEARING)/มีเสียงในหู (TINNITUS)',
                'diagram_name_en' => 'Decreased Hearing / Tinnitus',
                'description' => 'มีเสียงดังรบกวนในหู รู้สึกหูตึง หรือหูอื้อส่วนใหญ่ สาเหตุที่พบบ่อย ขี้หูอุดตันรูหู (169) หูตึง/หูหนวก (28) หูชั้นกลางอักเสบเฉียบพลัน (163) หูชั้นในอักเสบ (164) สิ่งแปลกปลอมเข้าหู (170) ถ้าอาการไม่ชัดเจน ควรปรึกษาแพทย์',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 27
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => 'ปวดศีรษะ ปวดหู หรือ ซีด?'],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => 'หูตึงหรือหูหนวก (ฟังไม่ได้ยิน/ไม่ได้ยิน)?'],
                'B3'   => ['frame' => '3',   'type' => 'S', 'q' => 'มีก้อนที่ข้างคอ?'],
                'B4'   => ['frame' => '4',   'type' => 'S', 'q' => "ความดันโลหิตช่วงบน >= 140 หรือช่วงล่าง >= 90 มม.ปรอท?\nหรือ มีประวัติเป็นเบาหวาน หรือ ภาวะไขมันในเลือดผิดปกติ?"],
                'B5'   => ['frame' => '5',   'type' => 'S', 'q' => 'เป็นไข้หวัด หรือเจ็บคอ?'],
                'B5_1' => ['frame' => '5.1', 'type' => 'S', 'q' => 'ใช้เครื่องส่องหู พบเยื่อแก้วหูบวมแดง?'],
                'B6'   => ['frame' => '6',   'type' => 'S', 'q' => 'มีอาการบ้านหมุน เวียนศีรษะเป็นชั่วโมงๆ หรือเป็นวันๆ?'],
                'B7'   => ['frame' => '7',   'type' => 'S', 'q' => 'สิ่งแปลกปลอมเข้าหู?'],
                'B8'   => ['frame' => '8',   'type' => 'S', 'q' => "เป็นหลังฉีดยาหรือกินยา*?\nหรือ หลังดื่มแอลกอฮอล์ กาแฟ โคล่า ช็อกโกแลต หรือสูบบุหรี่?"],
                'B9'   => ['frame' => '9',   'type' => 'S', 'q' => "มีอาการทันทีหลังว่ายน้ำ/ดำน้ำ?\nหรือ ใช้เครื่องส่องหูพบมีขี้หูอุดตัน?"],
                'B10'  => ['frame' => '10',  'type' => 'S', 'q' => 'ได้ยินเสียงดังตามจังหวะการเต้นของชีพจร?'],
                'B11'  => ['frame' => '11',  'type' => 'S', 'q' => 'มีอาการเป็นๆ หายๆ เป็นครั้งคราว และมีประวัติเป็นโรคภูมิแพ้?'],
                'B12'  => ['frame' => '12',  'type' => 'S', 'q' => 'คิดมาก? นอนไม่หลับ? หรือ มีอารมณ์ซึมเศร้า?'],
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
                'B1'   => [['refer_diagram_21_26_8', null], [null, 'B2']],
                'B2'   => [['refer_diagram_28', null], [null, 'B3']],
                'B3'   => [['refer_diagram_33', null], [null, 'B4']],
                'B4'   => [['hypertension_diabetes_dyslipidemia', null], [null, 'B5']],
                'B5'   => [[null, 'B5_1'], [null, 'B6']],
                'B5_1' => [['acute_otitis_media', null], ['eustachian_tube_swelling_infection', null]],
                'B6'   => [['vertigo_inner_ear', null], [null, 'B7']],
                'B7'   => [['foreign_body_in_ear', null], [null, 'B8']],
                'B8'   => [['drug_substance_induced_tinnitus', null], [null, 'B9']],
                'B9'   => [['impacted_cerumen', null], [null, 'B10']],
                'B10'  => [['pulsatile_tinnitus_vascular_tumor', null], [null, 'B11']],
                'B11'  => [['eustachian_tube_swelling_allergy', null], [null, 'B12']],
                'B12'  => [['anxiety_depression_tinnitus', null], ['nasopharyngeal_ca_or_brain_tumor', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'refer_diagram_21_26_8' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 21 ปวดศีรษะ (กรอบที่ 1)
ดูแผนภูมิที่ 26 ปวดหู (กรอบที่ 1)
ดูแผนภูมิที่ 8 ซีด (กรอบที่ 1)
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00021', '00026', '00008'],
                ],
                'refer_diagram_28' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 28 หูตึง/หูหนวก (กรอบที่ 2)
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00028'],
                ],
                'refer_diagram_33' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 33 คางบวม/คอบวม (กรอบที่ 1)
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00033'],
                ],
                'hypertension_diabetes_dyslipidemia' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน เพื่อควบคุมโรคเหล่านี้
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'acute_otitis_media' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูชั้นกลางอักเสบเฉียบพลัน (163)
• อะโมกซีซิลลิน (ย4.2) หรือ โคไตรม็อกซาโซล (ย4.7) หรือ อิริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน
ถ้าดีขึ้น กินยาจนครบ 10 วัน
NOTE,
                    'refs'       => ['163'],
                    'diagrams'   => [],
                ],
                'eustachian_tube_swelling_infection' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ท่อยูสเตเชียนบวมจากโรคติดเชื้อ
• รักษาแบบไข้หวัด/เจ็บคอ
• สูโดเอเฟดรีน (ย8.2) ครั้งละ 60 มก. ทุก 6 ชั่วโมง
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'vertigo_inner_ear' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน อาจเป็นโรคเมเนียร์ (165)/หูชั้นในอักเสบเฉียบพลัน (164)/เนื้องอกประสาทหู (164.2)/อื่นๆ
NOTE,
                    'refs'       => ['165', '164', '164.2'],
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
                'drug_substance_induced_tinnitus' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากยา/แอลกอฮอล์/บุหรี่/เครื่องดื่มต่างๆ
• หยุดยา/สารเหล่านี้
• แนะนำให้กลับไปพบแพทย์ที่รักษาอยู่เดิม

* ยาที่ทำให้หูอื้อ เช่น แอสไพริน (ย1.1) ยาต้านอักเสบที่ไม่ใช่สเตียรอยด์ (ย2) คลอโรควีน (ย5.1) ควินิน (ย5.3) เตตราไซคลีน (ย4.5) ดอกซีไซคลิน (ย4.5.1) อีริโทรไมซิน (ย4.4) ยาแก้แพ้ (ย7) สูโดเอเฟดรีน (ย8.2) ฟูโรซีไมด์ (ย21.1) โคไตรม็อกซาโซล (ย4.7) อะมิทริปไทลีน (ย17.2) เป็นต้น
NOTE,
                    'refs'       => [],
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
                'pulsatile_tinnitus_vascular_tumor' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นเนื้องอก/ความผิดปกติของหลอดเลือดของหูชั้นใน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'eustachian_tube_swelling_allergy' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ท่อยูสเตเชียนบวมจากการแพ้
• ยาแก้แพ้ (ย7)
• สูโดเอเฟดรีน (ย8.2) 60 มก. ทุก 6 ชั่วโมง
• หลีกเลี่ยงสิ่งที่แพ้
• ออกกำลังกายเป็นประจำ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ (ดู "โรคที่ 25 หวัดภูมิแพ้" ประกอบ)
NOTE,
                    'refs'       => ['25'],
                    'diagrams'   => [],
                ],
                'anxiety_depression_tinnitus' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรควิตกกังวล/โรคกังวลทั่วไป (88)/โรคอารมณ์แปรปรวน/โรคซึมเศร้า (88.2)
• ยาทางจิตประสาท (ย17)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['88', '88.2'],
                    'diagrams'   => [],
                ],
                'nasopharyngeal_ca_or_brain_tumor' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1-2 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1-2 สัปดาห์ อาจเป็นมะเร็งโพรงหลังจมูก (237.8)/เนื้องอกสมอง (83)/อื่นๆ
NOTE,
                    'refs'       => ['237.8', '83'],
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
                    'medical_reference' => 'แผนภูมิที่ 27',
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

        $this->command->info('สร้างแผนภูมิที่ 27 (หูอื้อ/มีเสียงในหู - DECREASED HEARING/TINNITUS) กรอบ 1-12 สำเร็จ');
    }
}
