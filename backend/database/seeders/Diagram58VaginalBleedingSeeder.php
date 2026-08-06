<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram58VaginalBleedingSeeder extends Seeder
{
    private const DIAGRAM_ID = '00058';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.1.1', '1.1.2', '1.1.2.1', '1.1.2.2',
                '2', '2.1', '2.1.1', '2.2', '2.2.1', '2.2.2', '2.3',
                '3', '4', '5', '6', '7', '8'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 58 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 58
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'เลือดออกทางช่องคลอด/ประจำเดือนออกมากกว่าปกติ/ตกเลือดระหว่างตั้งครรภ์',
                'diagram_name_en' => 'Vaginal Bleeding / Menometrorrhagia / Bleeding during Pregnancy',
                'description' => 'มีเลือดออกทางช่องคลอดที่ไม่ใช่เลือดประจำเดือน หรือตกเลือดระหว่างตั้งครรภ์หรือเลือดออกกะปริดกะปรอยหลังวัยหมดประจำเดือน หรือมีเลือดประจำเดือนออกมากหรือนานผิดปกติ สาเหตุที่พบบ่อย 1. ประจำเดือนออกมากกว่าปกติ (menometrorrhagia): ปีกมดลูกอักเสบ (147) ดียูบี (152) ใส่ห่วงควบคุมกำเนิดหลังคลอดบุตรใหม่ เนื้องอกมดลูก (152.1) 2. เลือดออกระหว่างตั้งครรภ์: แท้งบุตร (156) ครรภ์นอกมดลูก (157) รกเกาะต่ำ (159) รกลอกตัวก่อนกำหนด (160) 3. เลือดออกทางช่องคลอด (vaginal bleeding): ใส่ห่วงคุมกำเนิด ฉีดยาคุมกำเนิด มะเร็งปากมดลูก (237.3) ถ้าอาการไม่ชัดเจน ควรปรึกษาแพทย์ภายใน 1 สัปดาห์',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes)
            $boxes = [
                'B1'        => ['frame' => '1',       'type' => 'S', 'q' => 'เลือดประจำเดือน?'],
                'B1_1'      => ['frame' => '1.1',     'type' => 'S', 'q' => 'ออกมากกว่าปกติ? หรือ นานกว่าปกติ?'],
                'B1_1_1'    => ['frame' => '1.1.1',   'type' => 'S', 'q' => 'เคยออกมากเป็นประจำอยู่แล้ว? หลังใส่ห่วงคุมกำเนิด? หรือ หลังคลอดบุตรใหม่ๆ?'],
                'B1_1_2'    => ['frame' => '1.1.2',   'type' => 'S', 'q' => 'ปวดท้อง?'],
                'B1_1_2_1'  => ['frame' => '1.1.2.1', 'type' => 'S', 'q' => 'มีกลิ่นเหม็น?'],
                'B1_1_2_2'  => ['frame' => '1.1.2.2', 'type' => 'S', 'q' => 'ปวดท้องเกร็ง ทุกวันตามระยะของประจำเดือน?'],
                'B2'        => ['frame' => '2',       'type' => 'S', 'q' => 'ในผู้หญิงตั้งครรภ์? หรือ มีประวัติขาดประจำเดือน?'],
                'B2_1'      => ['frame' => '2.1',     'type' => 'S', 'q' => 'อายุครรภ์มากกว่า 7 เดือน?'],
                'B2_1_1'    => ['frame' => '2.1.1',   'type' => 'S', 'q' => 'มดลูกเกร็งแข็ง?'],
                'B2_2'      => ['frame' => '2.2',     'type' => 'S', 'q' => 'ปวดท้อง?'],
                'B2_2_1'    => ['frame' => '2.2.1',   'type' => 'S', 'q' => 'ปวดท้องเป็นพักๆ ร่วมกับ มีเลือดออกมาก? / มีชิ้นเนื้อออกทางช่องคลอด? / หรือ คลำได้ก้อนมดลูก?'],
                'B2_2_2'    => ['frame' => '2.2.2',   'type' => 'S', 'q' => 'ประจำเดือนขาด 15 วัน-3 เดือน ร่วมกับ ปวดท้องรุนแรง? หรือ เลือดออกกะปริดกะปรอย?'],
                'B2_3'      => ['frame' => '2.3',     'type' => 'S', 'q' => 'มดลูกโตเร็วกว่าปกติ (โตกว่าอายุครรภ์ที่ควรจะเป็น)? มดลูกโตถึงสะดือแต่เด็กยังไม่ดิ้น? หรือ มีเม็ดใสๆ คล้ายเม็ดสาคูออกทางช่องคลอด?'],
                'B3'        => ['frame' => '3',       'type' => 'S', 'q' => 'มีประวัติได้รับบาดเจ็บบริเวณช่องคลอด?'],
                'B4'        => ['frame' => '4',       'type' => 'S', 'q' => 'มีอาการอย่างใดอย่างหนึ่ง ดังต่อไปนี้: น้ำหนักลดฮวบ? / เลือดออกในหญิงอายุมากกว่า 40 ปีที่มีประวัติขาดประจำเดือน? / เลือดออกทันทีหลังร่วมเพศ? / มีน้ำใสๆ ไหลออกจากช่องคลอดจำนวนมาก?'],
                'B5'        => ['frame' => '5',       'type' => 'S', 'q' => 'คลำได้ก้อนในบริเวณท้องน้อย?'],
                'B6'        => ['frame' => '6',       'type' => 'S', 'q' => 'เป็นไข้เรื้อรัง? ต่อมน้ำเหลืองโต? หรือ มีจุดแดงจ้ำเขียว?'],
                'B7'        => ['frame' => '7',       'type' => 'S', 'q' => 'ใส่ห่วงคุมกำเนิด? ฉีดยาคุมกำเนิด? หรือ กินยาคุมกำเนิด?'],
                'B8'        => ['frame' => '8',       'type' => 'S', 'q' => 'เกิดหลังลงแช่น้ำในห้วย หนอง คลอง บึง?'],
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
                'B1'       => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'     => [[null, 'B1_1_1'], ['normal_menstruation', null]],
                'B1_1_1'   => [['physiologic_heavy_bleeding', null], [null, 'B1_1_2']],
                'B1_1_2'   => [[null, 'B1_1_2_1'], [null, 'B1_1_2_2']],
                'B1_1_2_1' => [['adnexitis_3d', null], [null, 'B1_1_2_2']],
                'B1_1_2_2' => [['endometriosis_1w', null], ['dub_myoma_endometriosis_eval_1w', null]],
                'B2'       => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'     => [[null, 'B2_1_1'], [null, 'B2_2']],
                'B2_1_1'   => [['abruptio_placentae', null], ['placenta_previa', null]],
                'B2_2'     => [[null, 'B2_2_1'], [null, 'B2_3']],
                'B2_2_1'   => [['abortion_24h', null], [null, 'B2_2_2']],
                'B2_2_2'   => [['ectopic_pregnancy', null], ['eval_amenorrhea_bleeding_24h', null]],
                'B2_3'     => [['molar_pregnancy_24h', null], ['eval_molar_pregnancy_24h', null]],
                'B3'       => [['vaginal_trauma_laceration', null], [null, 'B4']],
                'B4'       => [['endometrial_cervical_cancer_1w', null], [null, 'B5']],
                'B5'       => [['pelvic_mass_eval_1w', null], [null, 'B6']],
                'B6'       => [['blood_disorders_3d', null], [null, 'B7']],
                'B7'       => [['contraceptive_induced_bleeding', null], [null, 'B8']],
                'B8'       => [['leech_vaginal_entry', null], ['prolonged_bleeding_eval_1w', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนด Diagnosis Rules
            $rules = [
                'normal_menstruation' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ประจำเดือนปกติ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'physiologic_heavy_bleeding' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ถือเป็นเรื่องปกติไม่ใช่สาเหตุร้ายแรง
• ถ้าซีดให้ยาบำรุงโลหิต (ย24.11)
⊕ ถ้าออกนานเกิน 1 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'adnexitis_3d' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน อาจเป็นปีกมดลูกอักเสบ (147)
NOTE,
                    'refs'       => ['147'],
                    'diagrams'   => [],
                ],
                'endometriosis_1w' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นเยื่อบุมดลูกต่างที่ (153)
NOTE,
                    'refs'       => ['153'],
                    'diagrams'   => [],
                ],
                'dub_myoma_endometriosis_eval_1w' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
• นอนพัก
• กินยาแก้ปวด (ย1)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือปวดท้องมาก อาจเป็นดียูบี (152)/เนื้องอกมดลูก (152.1)/เยื่อบุมดลูกต่างที่ (153)/อื่น ๆ
NOTE,
                    'refs'       => ['152', '152.1', '153'],
                    'diagrams'   => [],
                ],
                'abruptio_placentae' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
รกลอกตัวก่อนกำหนด (160)
⊕ ด่วน
• ให้น้ำเกลือถ้าช็อก
NOTE,
                    'refs'       => ['160'],
                    'diagrams'   => [],
                ],
                'placenta_previa' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
รกเกาะต่ำ (159)
• แนะนำไปคลอดที่โรงพยาบาล
⊕ ด่วน ถ้าเลือดออกมากหรือครรภ์เกิน 9 เดือน
NOTE,
                    'refs'       => ['159'],
                    'diagrams'   => [],
                ],
                'abortion_24h' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
แท้งบุตร (156)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['156'],
                    'diagrams'   => [],
                ],
                'ectopic_pregnancy' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ครรภ์นอกมดลูก (157)
⊕ ด่วน
NOTE,
                    'refs'       => ['157'],
                    'diagrams'   => [],
                ],
                'eval_amenorrhea_bleeding_24h' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'molar_pregnancy_24h' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ครรภ์ไข่ปลาอุก (158)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['158'],
                    'diagrams'   => [],
                ],
                'eval_molar_pregnancy_24h' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'vaginal_trauma_laceration' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
บาดแผลในบริเวณช่องคลอด
⊕ ถ้าเลือดออกมาก หรือหาทางห้ามเลือดไม่ได้
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'endometrial_cervical_cancer_1w' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งเยื่อบุมดลูก (237.4)/มะเร็งปากมดลูก (237.3)/อื่น ๆ
NOTE,
                    'refs'       => ['237.4', '237.3'],
                    'diagrams'   => [],
                ],
                'pelvic_mass_eval_1w' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นเนื้องอกมดลูก (152.1)/มะเร็งเยื่อบุมดลูก (237.4)/เนื้องอกรังไข่ (153.1)/มะเร็งรังไข่ (237.5)
NOTE,
                    'refs'       => ['152.1', '237.4', '153.1', '237.5'],
                    'diagrams'   => [],
                ],
                'blood_disorders_3d' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน อาจเป็นโรคเลือด (ดู "โรคที่ 103. 104. 106")
NOTE,
                    'refs'       => ['103', '104', '106'],
                    'diagrams'   => [],
                ],
                'contraceptive_induced_bleeding' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
วิธีคุมกำเนิดเหล่านี้อาจทำให้มีเลือดออกทางช่องคลอดได้
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'leech_vaginal_entry' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ปลิงเข้าช่องคลอด (223)
• ห้ามเลือด
⊕ ถ้าเลือดไม่หยุดใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['223'],
                    'diagrams'   => [],
                ],
                'prolonged_bleeding_eval_1w' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ถ้าเลือดออกนานกว่า 1 สัปดาห์ อาจเป็นดียูบี (152)/เนื้องอกมดลูก (152.1)/มะเร็งเยื่อบุมดลูก (237.4)/มะเร็งปากมดลูก (237.3)/อื่น ๆ
NOTE,
                    'refs'       => ['152', '152.1', '237.4', '237.3'],
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
                    'medical_reference' => 'แผนภูมิที่ 58',
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

        $this->command->info('สร้างแผนภูมิที่ 58 (เลือดออกทางช่องคลอด/ประจำเดือนออกมากกว่าปกติ) สำเร็จ');
    }
}
