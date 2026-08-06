<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram56AmenorrheaSeeder extends Seeder
{
    private const DIAGRAM_ID = '00056';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.2', '1.3',
                '2', '3', '4', '5', '6', '7', '7.1', '8',
                '9', '10', '11', '12'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 56 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 56
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ประจำเดือนขาด/ไม่มา (AMENORRHEA)',
                'diagram_name_en' => 'Amenorrhea',
                'description' => 'ไม่เคยมีประจำเดือนมาเลยตั้งแต่เข้าวัยสาว หรือเคยมีประจำเดือนทุกเดือน แล้วเกิดขาดไป สาเหตุที่พบบ่อย ตั้งครรภ์ (154) ฉีดยาคุมกำเนิด โรควิตกกังวล (88) โรคอารมณ์แปรปรวน (88.2) วัยหมดประจำเดือน (129) หลังคลอดบุตร หรือให้นมบุตร ถ้าอาการไม่ชัดเจน ควรปรึกษาแพทย์ ถ้าประจำเดือนขาดนานเกิน 3 เดือน',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes)
            $boxes = [
                'B1'      => ['frame' => '1',    'type' => 'S', 'q' => 'เคยมีประจำเดือนมาก่อน?'],
                'B1_1'    => ['frame' => '1.1',  'type' => 'S', 'q' => 'อายุมากกว่า 16 ปี?'],
                'B1_2'    => ['frame' => '1.2',  'type' => 'S', 'q' => 'ปวดท้องเป็นประจำทุกเดือน? หรือ เยื่อพรหมจรรย์โป่งพอง?'],
                'B1_3'    => ['frame' => '1.3',  'type' => 'S', 'q' => 'มีการเจริญเติบโตเป็นปกติเช่นเด็กสาวอื่นๆ (เช่น มีเต้านมโต มีขนที่รักแร้และขนที่อวัยวะเพศ)?'],
                'B2'      => ['frame' => '2',    'type' => 'S', 'q' => 'พบในผู้หญิงที่แต่งงานแล้ว และ มีอาการแพ้ท้องหรือตั้งครรภ์? หรือ ตรวจปัสสาวะพบว่าตั้งครรภ์?'],
                'B3'      => ['frame' => '3',    'type' => 'S', 'q' => 'พบในหญิงที่เพิ่งคลอดบุตรใหม่ๆ?'],
                'B4'      => ['frame' => '4',    'type' => 'S', 'q' => 'ฉีดยาคุมกำเนิด? กินยาคุมกำเนิด? หรือ เคยผ่าตัดมดลูกหรือรังไข่?'],
                'B5'      => ['frame' => '5',    'type' => 'S', 'q' => 'อกร้อนวูบวาบตามผิวกาย หรือ เหงื่อออกตอนกลางคืนในผู้หญิงอายุ 40-55 ปี?'],
                'B6'      => ['frame' => '6',    'type' => 'S', 'q' => 'ปวดศีรษะเรื้อรังโดยไม่ทราบสาเหตุ? หรือ ตาพร่ามัวลงเรื่อยๆ (โดยไม่ใช่เกิดจากต้อกระจก)?'],
                'B7'      => ['frame' => '7',    'type' => 'S', 'q' => 'มีหนวดขึ้น และขนขึ้นตามใบหน้าและแขนขาผิดธรรมชาติ? หรือ มีน้ำนมออกผิดธรรมชาติ (ไม่ใช่ออกหลังคลอดบุตร)?'],
                'B7_1'    => ['frame' => '7.1',  'type' => 'S', 'q' => 'มีสิวขึ้น หน้ามัน หรือ มีบุตรยาก?'],
                'B8'      => ['frame' => '8',    'type' => 'S', 'q' => 'เต้านมแฟบ ขนรักแร้และขนอวัยวะเพศร่วง และ มีประวัติตกลดเลือดหลังคลอด?'],
                'B9'      => ['frame' => '9',    'type' => 'S', 'q' => 'มีอาการอย่างน้อย 2 อย่างดังต่อไปนี้: ขี้ร้อน หรือเหงื่อออกมาก? / น้ำหนักลด? / มือสั่น? / คอพอก? / ชีพจร > 120 ครั้ง/นาที?'],
                'B10'     => ['frame' => '10',   'type' => 'S', 'q' => 'มีอาการอย่างน้อย 2 อย่างดังต่อไปนี้: หนังตาบวม? / เส้นผมบางและหักง่าย? / ผิวหนังหยาบแห้ง และเย็น? / ขี้หนาว? / เสียงแหบ?'],
                'B11'     => ['frame' => '11',   'type' => 'S', 'q' => 'น้ำหนักลดเฉียบพลัน? น้ำหนักขึ้นหรือรูปร่างอ้วน? ออกกำลังหรือซ้อมกีฬามากเกิน? ซีด? ขาดอาหารเรื้อรัง? หรือ เจ็บป่วยเรื้อรัง?'],
                'B12'     => ['frame' => '12',   'type' => 'S', 'q' => 'กังวลใจ? ซึมเศร้า? หรือ เครียด?'],
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

            // อัปเดต Entry Box
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
                'B1'   => [[null, 'B2'], [null, 'B1_1']],
                'B1_1' => [['delayed_puberty_eval', null], [null, 'B1_2']],
                'B1_2' => [['imperforate_hymen', null], [null, 'B1_3']],
                'B1_3' => [['normal_development_wait', null], ['ovary_endocrine_eval_2w', null]],
                'B2'   => [['pregnancy_care', null], [null, 'B3']],
                'B3'   => [['postpartum_lactation_amenorrhea', null], [null, 'B4']],
                'B4'   => [['contraceptive_surgery_cause', null], [null, 'B5']],
                'B5'   => [['menopause', null], [null, 'B6']],
                'B6'   => [['pituitary_tumor_eval_1w', null], [null, 'B7']],
                'B7'   => [[null, 'B7_1'], [null, 'B8']],
                'B7_1' => [['pcos_eval_1w', null], ['cushing_ovarian_adrenal_pituitary_eval', null]],
                'B8'   => [['sheehan_syndrome', null], [null, 'B9']],
                'B9'   => [['hyperthyroidism', null], [null, 'B10']],
                'B10'  => [['hypothyroidism', null], [null, 'B11']],
                'B11'  => [['general_cause_amenorrhea', null], [null, 'B12']],
                'B12'  => [['anxiety_mood_disorder', null], ['amenorrhea_over_3m_eval', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนด Diagnosis Rules
            $rules = [
                'delayed_puberty_eval' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 เดือน',
                    'note'       => <<<NOTE
⊕ ภายใน 1 เดือน เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'imperforate_hymen' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เยื่อพรหมจรรย์ไม่เปิด (151)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['151'],
                    'diagrams'   => [],
                ],
                'normal_development_wait' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ไม่ต้องทำอะไร
• รองจนอายุ 16 ปี ถ้ายังไม่มีประจำเดือนมา ควรแนะนำไปโรงพยาบาล อาจมีความผิดปกติของมดลูกหรือช่องคลอด
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'ovary_endocrine_eval_2w' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 2 สัปดาห์ อาจมีความผิดปกติเกี่ยวกับรังไข่หรือระบบต่อมไร้ท่อ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'pregnancy_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ตั้งครรภ์หรือแพ้ท้อง (154)
• แนะนำไปฝากครรภ์
NOTE,
                    'refs'       => ['154'],
                    'diagrams'   => [],
                ],
                'postpartum_lactation_amenorrhea' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ประจำเดือนมักจะมาเมื่อหลังคลอด 6 สัปดาห์ ในรายที่ให้นมบุตร อาจช้ากว่านี้ได้
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'contraceptive_surgery_cause' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุเหล่านี้ทำให้ขาดประจำเดือนได้เป็นธรรมดา
• ไม่ต้องทำอะไร
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'menopause' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคของหญิงวัยหมดประจำเดือน (129)
• ให้การรักษาตามอาการ
NOTE,
                    'refs'       => ['129'],
                    'diagrams'   => [],
                ],
                'pituitary_tumor_eval_1w' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นเนื้องอกต่อมใต้สมอง (83)
NOTE,
                    'refs'       => ['83'],
                    'diagrams'   => [],
                ],
                'pcos_eval_1w' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นกลุ่มอาการถุงน้ำรังไข่ชนิดหลายถุง (153.1)
NOTE,
                    'refs'       => ['153.1'],
                    'diagrams'   => [],
                ],
                'cushing_ovarian_adrenal_pituitary_eval' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นโรคคุชชิง (125)/เนื้องอกรังไข่/ต่อมหมวกไต/สมอง/ต่อมใต้สมอง
NOTE,
                    'refs'       => ['125'],
                    'diagrams'   => [],
                ],
                'sheehan_syndrome' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
โรคชีแฮน (127)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['127'],
                    'diagrams'   => [],
                ],
                'hyperthyroidism' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ภาวะต่อมไทรอยด์ทำงานเกิน (121)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['121'],
                    'diagrams'   => [],
                ],
                'hypothyroidism' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ภาวะขาดไทรอยด์ (124)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['124'],
                    'diagrams'   => [],
                ],
                'general_cause_amenorrhea' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อาการเหล่านี้อาจทำให้ประจำเดือนขาดได้ ควรหาสาเหตุ และให้การรักษาตามสาเหตุ (ดูแผนภูมิที่ 6, 7, 8 ประกอบ)
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00006', '00007', '00008'],
                ],
                'anxiety_mood_disorder' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
โรควิตกกังวล (88)/โรคอารมณ์แปรปรวน (88.2)
• ยาทางจิตประสาท (ย17)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['88', '88.2'],
                    'diagrams'   => [],
                ],
                'amenorrhea_over_3m_eval' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 เดือน',
                    'note'       => <<<NOTE
⊕ ถ้าประจำเดือนขาดนานเกินกว่า 3 เดือน
NOTE,
                    'refs'       => [],
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
                    'medical_reference' => 'แผนภูมิที่ 56',
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

        $this->command->info('สร้างแผนภูมิที่ 56 (ประจำเดือนขาด/ไม่มา) สำเร็จ');
    }
}
