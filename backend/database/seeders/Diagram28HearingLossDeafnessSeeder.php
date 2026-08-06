<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram28HearingLossDeafnessSeeder extends Seeder
{
    private const DIAGRAM_ID = '00028';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '5',
                '6', '7', '7.1', '8', '9', '10'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 28 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 28
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'หูตึง/หูหนวก (HEARING LOSS/DEAFNESS)',
                'diagram_name_en' => 'Hearing Loss / Deafness',
                'description' => 'ฟังได้ไม่ชัด หรือฟังไม่ได้ยิน สาเหตุที่พบบ่อย หูตึงในผู้สูงอายุ (166) หูชั้นกลางอักเสบเฉียบพลัน (163) เยื่อแก้วหูทะลุ (167) ถ้าอาการไม่ชัดเจน ควรปรึกษาแพทย์',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 28
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => 'ปวดหู?'],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => 'เป็นมาแต่กำเนิด?'],
                'B3'   => ['frame' => '3',   'type' => 'S', 'q' => 'หูน้ำหนวกไหล?'],
                'B4'   => ['frame' => '4',   'type' => 'S', 'q' => 'เป็นไข้หวัด หรือเจ็บคอ? และ ใช้เครื่องส่องหูพบเยื่อแก้วหูบวมแดง?'],
                'B5'   => ['frame' => '5',   'type' => 'S', 'q' => "เกิดขึ้นทันทีหลังแคะหู/ถูกตี/เล่นพลุ/จุดประทัด/อยู่ใกล้เสียงระเบิด?\nหรือ ใช้เครื่องส่องหูพบเยื่อแก้วหูมีรูทะลุ?"],
                'B6'   => ['frame' => '6',   'type' => 'S', 'q' => 'มีอาการบ้านหมุนนานเป็นชั่วโมงๆ หรือเป็นวันๆ และมีเสียงดังในหู?'],
                'B7'   => ['frame' => '7',   'type' => 'S', 'q' => 'ได้ยินเสียงต่ำ (เสียงเคาะประตู) ได้ดีกว่าเสียงสูง (เสียงกระดิ่ง)?'],
                'B7_1' => ['frame' => '7.1', 'type' => 'S', 'q' => 'ทำงานในที่มีเสียงดังมาก (เช่น โรงงาน)?'],
                'B8'   => ['frame' => '8',   'type' => 'S', 'q' => 'เกิดขึ้นหลังกินยา หรือฉีดยา*?'],
                'B9'   => ['frame' => '9',   'type' => 'S', 'q' => 'อายุมากกว่า 60 ปี?'],
                'B10'  => ['frame' => '10',  'type' => 'S', 'q' => 'เคยเป็นหัด คางทูม เยื่อหุ้มสมองอักเสบ สมองอักเสบ หรือซิฟิลิสมาก่อน?'],
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
                'B1'   => [['refer_diagram_26', null], [null, 'B2']],
                'B2'   => [['congenital_deafness', null], [null, 'B3']],
                'B3'   => [['refer_diagram_29', null], [null, 'B4']],
                'B4'   => [['acute_otitis_media', null], [null, 'B5']],
                'B5'   => [['perforated_eardrum', null], [null, 'B6']],
                'B6'   => [['vertigo_meniere_inner_ear', null], [null, 'B7']],
                'B7'   => [[null, 'B7_1'], [null, 'B8']],
                'B7_1' => [['noise_induced_hearing_loss', null], [null, 'B8']],
                'B8'   => [['drug_induced_hearing_loss', null], [null, 'B9']],
                'B9'   => [['presbycusis', null], [null, 'B10']],
                'B10'  => [['nerve_deafness_from_infections', null], ['unexplained_hearing_loss', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'refer_diagram_26' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 26 ปวดหู (กรอบที่ 1)
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00026'],
                ],
                'congenital_deafness' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูตึงโดยกำเนิด (166)
มักมีสาเหตุจากหัดเยอรมัน (4)
NOTE,
                    'refs'       => ['166', '4'],
                    'diagrams'   => [],
                ],
                'refer_diagram_29' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 29 หูมีหนองไหล (กรอบที่ 1.1)
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00029'],
                ],
                'acute_otitis_media' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูชั้นกลางอักเสบเฉียบพลัน (163)
• ยาแก้ปวดลดไข้ (ย1)
• อะโมกซีซิลลิน (ย4.2) หรือโคไตรม็อกซาโซล (ย4.7) หรืออีริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน
ถ้าดีขึ้นกินยาต่อจนครบ 10 วัน
NOTE,
                    'refs'       => ['163'],
                    'diagrams'   => [],
                ],
                'perforated_eardrum' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เยื่อแก้วหูทะลุ (167)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['167'],
                    'diagrams'   => [],
                ],
                'vertigo_meniere_inner_ear' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน
อาจเป็นโรคเมเนียร์ (165)/หูชั้นในอักเสบเฉียบพลัน (164)/เนื้องอกประสาทหู (164.2)/อื่นๆ
NOTE,
                    'refs'       => ['165', '164', '164.2'],
                    'diagrams'   => [],
                ],
                'noise_induced_hearing_loss' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูตึงเนื่องจากเสียง (166)
• พักงาน
• แนะนำไปปรึกษาแพทย์ (ถ้าเสพนานมักจะหูตึงอย่างถาวร)
NOTE,
                    'refs'       => ['166'],
                    'diagrams'   => [],
                ],
                'drug_induced_hearing_loss' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากยา
• หยุดยา
• แนะนำให้กลับไปพบแพทย์ที่รักษาอยู่เดิม

* ยาที่ทำให้หูตึง เช่น สเตรปโตไมซิน (ย4.12) คานาไมซิน (kanamycin) เจนตาไมซิน (gentamicin) คลอโรควีน (ย5.1) ควินิน (ย5.3) ฟูโรซีไมด์ (ย21.1) เป็นต้น
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'presbycusis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูตึงในผู้สูงอายุ (166)
• แนะนำไปปรึกษาแพทย์ อาจต้องใช้เครื่องช่วยฟัง
NOTE,
                    'refs'       => ['166'],
                    'diagrams'   => [],
                ],
                'nerve_deafness_from_infections' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ประสาทหูพิการจากโรคเหล่านี้
• แนะนำไปปรึกษาแพทย์ อาจต้องใช้เครื่องช่วยฟัง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'unexplained_hearing_loss' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 2 สัปดาห์
เพื่อตรวจหาสาเหตุ
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
                    'medical_reference' => 'แผนภูมิที่ 28',
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

        $this->command->info('สร้างแผนภูมิที่ 28 (หูตึง/หูหนวก - HEARING LOSS/DEAFNESS) กรอบ 1-10 สำเร็จ');
    }
}
