<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram52JointPainSeeder extends Seeder
{
    private const DIAGRAM_ID = '00052';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '2', '2.1', '2.1.1', '2.1.2', '2.2', '2.3',
                '3', '3.1', '3.2', '4', '5', '5.1', '5.2', '6', '7', '8', '9', '10'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 52 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 52
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ปวดข้อ/ปวดเอ็น (JOINT PAIN)',
                'diagram_name_en' => 'Joint Pain',
                'description' => 'เมื่ออาการปวดขัดในข้อ หรือข้อบวมแดงร้อน ที่ข้อใดข้อหนึ่งของร่างกาย เช่น ต้นคอ ข้อไหล่ ข้อศอก ข้อมือ ข้อสะโพก ข้อเข่า ข้อเท้า ข้อนิ้วมือข้อนิ้วเท้า อาจเป็นพร้อมกันหลายข้อหรือเป็นเพียงข้อเดียวก็ได้ หรือมีอาการปวดตามเส้นเอ็นหรือพังผืดเวลาเคลื่อนไหว สาเหตุที่พบบ่อย ข้อแพลง (113) ข้อเสื่อม (109) เส้นเอ็นอักเสบ (114) เอสแอลอี (111) โรคปวดข้อรูมาตอยด์ (110) ไข้รูมาติก (94) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 10 ถ้ามีอาการปวดหลัง ดูแผนภูมิที่ 53',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 52
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'พบหลังหกล้มหรือได้รับบาดเจ็บ?'],
                'B1_1'    => ['frame' => '1.1',   'type' => 'S', 'q' => 'บวมมากจนเคลื่อนไหวข้อลำบาก? หรือ สงสัยกระดูกหัก?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'ข้อบวมแดงร้อน? ปวดรุนแรง? หรือ มีไข้?'],
                'B2_1'    => ['frame' => '2.1',   'type' => 'S', 'q' => 'ปวดตามข้อใหญ่ๆ (เช่น ข้อศอก ข้อเข่า ข้อเท้า เป็นต้น)?'],
                'B2_1_1'  => ['frame' => '2.1.1', 'type' => 'S', 'q' => 'มีย่างน้อย 2 อย่างดังต่อไปนี้: ปวดเลื่อนที่ทีละข้อ? / มีอาการเจ็บคอมาก่อน 1-4 สัปดาห์? / แขนขาเคลื่อนไหวผิดปกติ? / หัวใจมีเสียงฟู่ (murmur)? / มีผื่นวงแดงตรงกลางขาวขึ้นตามผิวหนัง? / มีตุ่มขึ้นตามข้อต่างๆ? / พบในเด็กอายุ 5-15 ปี?'],
                'B2_1_2'  => ['frame' => '2.1.2', 'type' => 'S', 'q' => 'มีการติดเชื้อในบริเวณอื่นๆ ของร่างกายอยูู่ก่อน (เช่น เป็นฝี คออักเสบ ปอดบวม หนองใน เป็นต้น)?'],
                'B2_2'    => ['frame' => '2.2',   'type' => 'S', 'q' => 'มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้: ปวดที่นิ้วหัวแม่เท้าหรือข้ออื่นๆ เพียงข้อเดียว? / มีประวัติเป็นโรคเกาต์มาก่อน หรือมีพ่อแม่พี่น้องเป็นโรคเกาต์? / มีตุ่มโทฟัส?'],
                'B2_3'    => ['frame' => '2.3',   'type' => 'S', 'q' => 'ปวดตามข้อข้อนิ้วมือข้อนิ้วเท้า 2 ข้าง? และ ผมร่วง หรือมีผื่นปีกผีเสื้อที่ข้างจมูก?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'ปวดตามข้อนิ้วมือข้อนิ้วเท้าพร้อมกันทั้ง 2 ข้าง?'],
                'B3_1'    => ['frame' => '3.1',   'type' => 'S', 'q' => 'ผมร่วง? หรือ มีผื่นปีกผีเสื้อที่ข้างจมูก?'],
                'B3_2'    => ['frame' => '3.2',   'type' => 'S', 'q' => 'ชาตามปลายมือปลายเท้า? หรือ ข้อแข็งเวลาตื่นนอนตอนเช้า (รู้สึกกำมือลำบากหรือขี้เกียจตื่นนอน)?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'ปวดและชาปลายมือข้างหนึ่ง หรือ 2 ข้าง เป็นมากตอนกลางคืน หรือเวลาเกร็งข้อมือ?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'ปวดขัดในข้อเรื้อรังในคนอ้วน หรือคนอายุมากกว่า 40 ปี?'],
                'B5_1'    => ['frame' => '5.1',   'type' => 'S', 'q' => 'ปวดต้นคอ?'],
                'B5_2'    => ['frame' => '5.2',   'type' => 'S', 'q' => 'ปวดข้อเข่าหรือข้อสะโพก และมีเสียงดังกรอบแกรบเวลาโยกข้อไปมา?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => 'ปวดตึงในข้อสะโพก หรือข้อสันหลังนานเกิน 3 เดือน ในคนอยู่น้อยกว่า 30 ปี?'],
                'B7'      => ['frame' => '7',     'type' => 'S', 'q' => 'ปวดที่เส้นเอ็นเฉพาะเวลาบิดข้อ/ขยับข้อ (ไหล่/ข้อมือ/ข้อศอก/ข้อเข่า/ข้อเท้า) ทำให้เคลื่อนไหวข้อได้ลำบาก?'],
                'B8'      => ['frame' => '8',     'type' => 'S', 'q' => 'งอนิ้วมือแล้วเหยียดออกเองไม่ได้?'],
                'B9'      => ['frame' => '9',     'type' => 'S', 'q' => 'รู้สึกปวดส้นเท้าคล้ายถูกมีดปักใน 2-3 ก้าวแรกที่ลุกขึ้นเดินหลังตื่นนอน หรือปวดเวลาเดินขึ้นบันได หรือยืน/เดินบนปลายเท้า?'],
                'B10'     => ['frame' => '10',    'type' => 'S', 'q' => 'ข้อบวมแดงร้อน/ปวดรุนแรง/มีไข้ หรือปวดข้อเรื้อรังทั่วไป?'],
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
                'B1'      => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'    => [['fracture', null], ['sprain', null]],
                'B2'      => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'    => [[null, 'B2_1_1'], [null, 'B2_2']],
                'B2_1_1'  => [['rheumatic_fever', null], [null, 'B2_1_2']],
                'B2_1_2'  => [['septic_arthritis', null], [null, 'B2_2']],
                'B2_2'    => [['gout', null], [null, 'B2_3']],
                'B2_3'    => [['sle_b2_3', null], ['fever_joint_investigate', null]],
                'B3'      => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'    => [['sle_b3_1', null], [null, 'B3_2']],
                'B3_2'    => [['rheumatoid_arthritis', null], [null, 'B4']],
                'B4'      => [['carpal_tunnel_syndrome', null], [null, 'B5']],
                'B5'      => [[null, 'B5_1'], [null, 'B6']],
                'B5_1'    => [['cervical_spondylosis', null], [null, 'B5_2']],
                'B5_2'    => [['osteoarthritis', null], [null, 'B6']],
                'B6'      => [['ankylosing_spondylitis', null], [null, 'B7']],
                'B7'      => [['tendinitis', null], [null, 'B8']],
                'B8'      => [['trigger_finger', null], [null, 'B9']],
                'B9'      => [['plantar_fasciitis', null], [null, 'B10']],
                'B10'     => [['general_joint_care', null], ['general_joint_care', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'fracture' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
กระดูกหัก (213)
• ใช้ไม้ดาม
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['213'],
                    'diagrams'   => [],
                ],
                'sprain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ข้อแพลง (113)
• ยาต้านอักเสบที่ไม่ใช่สเตียรอยด์ (ย2)
• ประคบด้วยน้ำเย็น (ภายใน 48 ชั่วโมงหลังบาดเจ็บ) และน้ำอุ่นจัด ๆ (หลังบาดเจ็บเกิน 48 ชั่วโมง)
• ยาทานวด
• พักข้อ
• พันผ้ายืด
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือมีไข้
NOTE,
                    'refs'       => ['113'],
                    'diagrams'   => [],
                ],
                'rheumatic_fever' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ไข้รูมาติก (94)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['94'],
                    'diagrams'   => [],
                ],
                'septic_arthritis' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ข้ออักเสบชนิดติดเชื้อเฉียบพลัน (112)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['112'],
                    'diagrams'   => [],
                ],
                'gout' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
โรคเกาต์ (128)
• คอลชิซีน หรือยาต้านอักเสบที่ไม่ใช่สเตียรอยด์ (ย2)
⊕ ภายใน 3 วัน เพื่อชันสูตรเพิ่มเติม
NOTE,
                    'refs'       => ['128'],
                    'diagrams'   => [],
                ],
                'sle_b2_3' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เอสแอลอี (111)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['111'],
                    'diagrams'   => [],
                ],
                'fever_joint_investigate' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน เพื่อตรวจหาสาเหตุ อาจเป็น บรูเซลโลซิส (229.4)/เมลลิออยโดซิส (229.2)
NOTE,
                    'refs'       => ['229.4', '229.2'],
                    'diagrams'   => [],
                ],
                'sle_b3_1' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เอสแอลอี (111)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['111'],
                    'diagrams'   => [],
                ],
                'rheumatoid_arthritis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
โรคปวดข้อรูมาตอยด์ (110)
• แอสไพริน (ย1.1)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['110'],
                    'diagrams'   => [],
                ],
                'carpal_tunnel_syndrome' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เส้นประสาทมือถูกพังผืดรัดแน่น (115)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['115'],
                    'diagrams'   => [],
                ],
                'cervical_spondylosis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กระดูกคอเสื่อม (108.1)
• พาราเซตามอล (ย1.2)
• ยาคลายกล้ามเนื้อ (ย3)
• บริหารคอ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือมีอาการปวดเสียวและชาลงมาที่แขน
NOTE,
                    'refs'       => ['108.1'],
                    'diagrams'   => [],
                ],
                'osteoarthritis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ข้อเสื่อม (109)
• พาราเซตามอล (ย1.2)
• ถ้าปวดมาก หรือข้อบวม ให้ยาต้านอักเสบที่ไม่ใช่สเตียรอยด์ (ย2)
• หลีกเลี่ยงท่าที่ทำให้ปวด
• บริหารข้อ
• ลดน้ำหนัก
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือมีไข้
NOTE,
                    'refs'       => ['109'],
                    'diagrams'   => [],
                ],
                'ankylosing_spondylitis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็น ข้อสันหลังอักเสบเรื้อรัง (110.1)
NOTE,
                    'refs'       => ['110.1'],
                    'diagrams'   => [],
                ],
                'tendinitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เส้นเอ็นอักเสบ (114)
• ยาต้านอักเสบที่ไม่ใช่สเตียรอยด์ (ย2)
• ประคบด้วยน้ำเย็น (ภายใน 48 ชั่วโมง หลังบาดเจ็บ) และน้ำอุ่นจัดๆ (หลังบาดเจ็บเกิน 48 ชั่วโมง)
• ยาทานวด
• พักข้อ
• พันผ้ายืด
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['114'],
                    'diagrams'   => [],
                ],
                'trigger_finger' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
นิ้วล็อก (ดู "โรคที่ 114")
⊕ ภายใน 2 สัปดาห์
NOTE,
                    'refs'       => ['114'],
                    'diagrams'   => [],
                ],
                'plantar_fasciitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
พังผืดส้นเท้าอักเสบ (114.1)
• ยาต้านอักเสบที่ไม่ใช่สเตียรอยด์ (ย2)
• ประคบด้วยน้ำแข็ง
• ลดน้ำหนัก
• บริหารกล้ามเนื้อน่อง
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['114.1'],
                    'diagrams'   => [],
                ],
                'general_joint_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาต้านอักเสบที่ไม่ใช่สเตียรอยด์ (ย2)
• พักข้อ
• ยาทางจิตประสาท (ย17) ถ้ามีภาวะวิตกกังวล/ซึมเศร้า/เครียด
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือมีไข้เกิน 7 วัน/น้ำหนักลดฮวบ/ข้อบวมแดงร้อน/ซีด/ปวดรุนแรง
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
                    'medical_reference' => 'แผนภูมิที่ 52',
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

        $this->command->info('สร้างแผนภูมิที่ 52 (ปวดข้อ/ปวดเอ็น - JOINT PAIN) กรอบ 1-10 สำเร็จ');
    }
}
