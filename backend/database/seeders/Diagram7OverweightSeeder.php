<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram7OverweightSeeder extends Seeder
{
    private const DIAGRAM_ID = '00007';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '2', '3', '3.1', '3.2', '4', '5', '6', '7', '8', '9', '10'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 7 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 7
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'น้ำหนักมากหรืออ้วน (OVERWEIGHT)',
                'diagram_name_en' => 'Overweight or Obesity',
                'description' => 'น้ำหนักตัวมากกว่าคนปกติ หรือรูปร่างอ้วนผิดปกติ สาเหตุที่พบบ่อย: กินอาหารเกินความต้องการ ออกกำลังกายน้อย กรรมพันธุ์ จากสเตียรอยด์ (ย12) บวมน้ำ / ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ (11)',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 7
            $boxes = [
                'B1'     => ['frame' => '1',   'type' => 'S', 'q' => 'รูปร่างอ้วนมานานแล้ว หรืออ้วนตั้งแต่เด็ก?'],
                'B1_1'   => ['frame' => '1.1', 'type' => 'S', 'q' => 'มีพ่อแม่ หรือพี่น้อง อ้วนด้วย?'],
                'B2'     => ['frame' => '2',   'type' => 'S', 'q' => 'เท้าบวม และกดมีรอยบุ๋ม?'],
                'B3'     => ['frame' => '3',   'type' => 'S', 'q' => 'อ่อนเพลีย เหนื่อยง่าย?'],
                'B3_1'   => [
                    'frame' => '3.1',
                    'type'  => 'S',
                    'q'     => 'มีอาการอย่างน้อย 2 อย่าง ดังต่อไปนี้
- หนังตาบวม?
- เส้นผมบางและหักง่าย?
- ผิวหนังหยาบแห้งและเย็น?
- ขี้หนาว?
- เสียงแหบ?'
                ],
                'B3_2'   => [
                    'frame' => '3.2',
                    'type'  => 'S',
                    'q'     => 'มีอาการอย่างน้อย 2 อย่าง ดังต่อไปนี้
- หน้าอูมกลมคล้ายพระจันทร์?
- มีสิว และขนอ่อนขึ้นที่หน้า?
- หน้าท้องลาย?
- ประจำเดือนไม่มา?
- มีจ้ำเขียวคล้ายรอยฟกช้ำ?
- ความดันโลหิตสูง?'
                ],
                'B4'     => ['frame' => '4',   'type' => 'S', 'q' => 'ปวดศีรษะ และตาพร่ามัวลงเรื่อยๆ?'],
                'B5'     => ['frame' => '5',   'type' => 'S', 'q' => 'กินยาชุด ยาลูกกลอน หรือยาสเตียรอยด์ (ย12)?'],
                'B6'     => ['frame' => '6',   'type' => 'S', 'q' => 'ดื่มนมหรือกินอาหารบ่อยมื้อขึ้น (อาจพบในผู้ป่วยโรคกระเพาะ)?'],
                'B7'     => ['frame' => '7',   'type' => 'S', 'q' => 'พบหลังอดบุหรี่?'],
                'B8'     => ['frame' => '8',   'type' => 'S', 'q' => 'ผู้หญิงหลังคลอดบุตร?'],
                'B9'     => ['frame' => '9',   'type' => 'S', 'q' => 'อายุมากกว่า 40 ปี?'],
                'B10'    => ['frame' => '10',  'type' => 'S', 'q' => 'ทำงานเบาลงกว่าเมื่อก่อน หรือไม่ค่อยได้ออกกำลังกาย?'],
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
                'B1'    => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'  => [['hereditary_overweight', null], ['dietary_overweight', null]],
                'B2'    => [['refer_diagram_13', null], [null, 'B3']],
                'B3'    => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'  => [['hypothyroidism', null], [null, 'B3_2']],
                'B3_2'  => [['cushing_syndrome', null], ['unexplained_fatigue_obesity', null]],
                'B4'    => [['pituitary_tumor', null], [null, 'B5']],
                'B5'    => [['steroid_side_effects', null], [null, 'B6']],
                'B6'    => [['overeating_frequent_meals', null], [null, 'B7']],
                'B7'    => [['post_smoking_cessation', null], [null, 'B8']],
                'B8'    => [['postpartum_weight', null], [null, 'B9']],
                'B9'    => [['age_over_40_inactivity', null], [null, 'B10']],
                'B10'   => [['reduced_physical_activity', null], ['general_obesity_care', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $generalCareNote = '• ลดอาหารพวกไขมัน แป้ง น้ำตาล
• อย่ากินจุกจิก
• กินผัก ผลไม้ ของน้ำๆ ให้มากขึ้น
• ออกกำลังกายให้มากขึ้น
⊕ ถ้าความดันโลหิตสูง หรือสงสัยเป็นเบาหวาน/น้ำหนักไม่ลดใน 1 เดือน/นอนกรนและหยุดหายใจเป็นช่วงๆ/พบในผู้หญิงที่มีสิวขึ้น หน้ามัน มีหนวดขึ้นผิดปกติ ประจำเดือนขาด หรือมีบุตรยาก';

            $rules = [
                'hereditary_overweight' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => "อาจมีสาเหตุจากกรรมพันธุ์ หรือสิ่งแวดล้อมภายในบ้านที่ส่งเสริมให้มีการกินอาหารมากเกินต้องการ
{$generalCareNote}",
                    'refs'       => ['31.1', '153.1'],
                    'diagrams'   => [],
                ],
                'dietary_overweight' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => "สาเหตุจากนิสัยการกินที่มากเกินต้องการ
{$generalCareNote}",
                    'refs'       => ['31.1', '153.1'],
                    'diagrams'   => [],
                ],
                'refer_diagram_13' => [
                    'urgency'    => 'R',
                    'time_frame' => null,
                    'note'       => 'ดูแผนภูมิที่ 13 บวม กรอบที่ (1)',
                    'refs'       => [],
                    'diagrams'   => ['00013'],
                ],
                'hypothyroidism' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => 'ภาวะขาดไทรอยด์/ต่อมไทรอยด์ทำงานน้อย (124)',
                    'refs'       => ['124'],
                    'diagrams'   => [],
                ],
                'cushing_syndrome' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => 'โรคคุชชิง (125)',
                    'refs'       => ['125'],
                    'diagrams'   => [],
                ],
                'unexplained_fatigue_obesity' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => 'เพื่อตรวจหาสาเหตุ',
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'pituitary_tumor' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => 'เนื้องอกต่อมใต้สมอง (83)',
                    'refs'       => ['83'],
                    'diagrams'   => [],
                ],
                'steroid_side_effects' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => 'ผลข้างเคียงจากยาสเตียรอยด์ ดูเรื่องสเตียรอยด์ (ย12) ในภาค 2',
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'overeating_frequent_meals' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => "อ้วนจากการกินอาหารมากขึ้น
{$generalCareNote}",
                    'refs'       => ['31.1', '153.1'],
                    'diagrams'   => [],
                ],
                'post_smoking_cessation' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => "คนที่อดบุหรี่มักจะกินมากขึ้น ทำให้อ้วนได้
{$generalCareNote}",
                    'refs'       => ['31.1', '153.1'],
                    'diagrams'   => [],
                ],
                'postpartum_weight' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => "ผู้หญิงที่ตั้งครรภ์มักมีน้ำหนักขึ้น ถ้าหลังคลอดออกกำลังกายไม่เพียงพอ น้ำหนักจะไม่ลดลงเท่าก่อนตั้งครรภ์
{$generalCareNote}",
                    'refs'       => ['31.1', '153.1'],
                    'diagrams'   => [],
                ],
                'age_over_40_inactivity' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => "คนในวัยนี้มักจะออกกำลังกายน้อย
{$generalCareNote}",
                    'refs'       => ['31.1', '153.1'],
                    'diagrams'   => [],
                ],
                'reduced_physical_activity' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => "อ้วนจากการออกกำลังกายน้อย
{$generalCareNote}",
                    'refs'       => ['31.1', '153.1'],
                    'diagrams'   => [],
                ],
                'general_obesity_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => $generalCareNote,
                    'refs'       => ['31.1', '153.1'],
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
                    'medical_reference' => 'แผนภูมิที่ 7',
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

        $this->command->info('สร้างแผนภูมิที่ 7 (น้ำหนักมากหรืออ้วน / OVERWEIGHT) กรอบ 1-11 สำเร็จ');
    }
}
