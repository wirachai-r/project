<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram65AlopeciaBaldnessSeeder extends Seeder
{
    private const DIAGRAM_ID = '00065';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.2', '1.3', '1.4', '1.5',
                '2', '3', '4', '5', '6', '7', '8', '9', '10'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 65 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 65
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ผมร่วง/ผมบาง (ALOPECIA/BALDNESS)',
                'diagram_name_en' => 'Alopecia / Baldness',
                'description' => 'มีอาการผมร่วง หรือผมบางผิดไปจากปกติ สาเหตุที่พบบ่อย โรควิตกกังวล/โรคกังวลทั่วไป (88) ผมร่วงหลังคลอดบุตร ผมร่วงในทารกแรกเกิด ผมร่วงกรรมพันธุ์ (ศีรษะเถิก ศีรษะล้าน) ผมร่วงหย่อมไม่ทราบสาเหตุ กลากที่ศีรษะ (190) เอสแอลอี (111) ซิฟิลิส (211) ถ้าอาการไม่ชัดเจน ควรปรึกษาแพทย์',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes)
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'ผมร่วงเป็นหย่อม?'],
                'B1_1'    => ['frame' => '1.1',   'type' => 'S', 'q' => 'รอบๆ เป็นผื่นแดง คัน และมีขุย? หรือ เป็นกลากหรือโรคเชื้อราในบริเวณอื่นร่วมด้วย?'],
                'B1_2'    => ['frame' => '1.2',   'type' => 'M', 'q' => 'มีประวัติเที่ยวผู้หญิงหรือสัมผัสกับผู้ป่วยกามโรคในระยะ 12 เดือนที่ผ่านมา ร่วมกับอาการข้อใดข้อหนึ่งดังต่อไปนี้:
- มีประวัติเป็นแผลที่อวัยวะเพศในระยะ 12 เดือน ที่ผ่านมา?
- มีผื่นแดงขึ้นทั่วตัวรวมทั้งที่ฝ่ามือ ฝ่าเท้า?
- ผมร่วงมีลักษณะคล้ายรอยแมลงแทะ?'],
                'B1_3'    => ['frame' => '1.3',   'type' => 'S', 'q' => 'ชอบถอนผมเล่น?'],
                'B1_4'    => ['frame' => '1.4',   'type' => 'S', 'q' => 'เป็นรอยแผลเป็น?'],
                'B1_5'    => ['frame' => '1.5',   'type' => 'S', 'q' => 'หนังศีรษะบริเวณที่ผมแหว่งมีลักษณะเป็นปกติ?'],
                'B2'      => ['frame' => '2',     'type' => 'M', 'q' => 'มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้:
- เป็นไข้นานกว่า 2 สัปดาห์?
- ปวดตามข้อต่างๆ?
- ผื่นปีกผีเสื้อที่ข้างจมูก?'],
                'B3'      => ['frame' => '3',     'type' => 'M', 'q' => 'มีอาการอย่างน้อย 2 อย่างดังต่อไปนี้:
- หนังตาบวม?
- ผิวหนังหยาบ แห้งและเย็น?
- ขี้หนาว?
- อ้วนขึ้น?
- เสียงแหบ?'],
                'B4'      => ['frame' => '4',     'type' => 'M', 'q' => 'มีประวัติเที่ยวผู้หญิง หรือสัมผัสผู้ป่วยกามโรคในระยะ 12 เดือนที่ผ่านมา ร่วมกับอาการข้อใดข้อหนึ่งดังต่อไปนี้:
- มีประวัติเป็นแผลที่อวัยวะเพศในระยะ 12 เดือนที่ผ่านมา?
- มีผื่นแดงขึ้นทั่วตัวรวมทั้งฝ่ามือ ฝ่าเท้า?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'มีโรคเรื้อรัง เช่น ซีด วัณโรค ขาดอาหาร?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => 'มีประวัติกินยา* หรือฉายรังสี หรือรับเคมีบำบัดในระยะใกล้ๆ นี้?
(* ยาที่ทำให้ผมร่วง เช่น เฮพาริน ยาต้านไทรอยด์ ยารักษามะเร็ง คอลชิซิน อัลโลพูรินอล แอมเฟตามีน ยาแก้ซึมเศร้าชนิดไตรไซคลิก เป็นต้น)'],
                'B7'      => ['frame' => '7',     'type' => 'S', 'q' => 'พบหลังเป็นไข้สูง หลังผ่าตัด หลังคลอด หรือหลังมีภาวะเครียดทางจิตใจ 2-3 เดือน? หรือ พบในทารกแรกเกิด?'],
                'B8'      => ['frame' => '8',     'type' => 'S', 'q' => 'ผมบางเฉพาะที่หน้าผาก (ศีรษะเถิก) หรือกลางศีรษะ (ทุ่งหมาหลง หรือดงช้างข้าม)? หรือ มีญาติพี่น้องศีรษะล้าน?'],
                'B9'      => ['frame' => '9',     'type' => 'S', 'q' => 'ม้วนผม ดัดผม เป่าผม? หรือ ทำผมด้วยวิธีต่างๆ?'],
                'B10'     => ['frame' => '10',    'type' => 'S', 'q' => 'มีเรื่องวิตกกังวลหรือคิดมาก?'],
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
                    'min_required' => $box['type'] === 'M' ? 1 : null,
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
                'B1'     => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'   => [['tinea_capitis', null], [null, 'B1_2']],
                'B1_2'   => [['syphilis_alopecia_areata', null], [null, 'B1_3']],
                'B1_3'   => [['trichotillomania', null], [null, 'B1_4']],
                'B1_4'   => [['cicatricial_alopecia', null], [null, 'B1_5']],
                'B1_5'   => [['alopecia_areata_unknown', null], ['refer_doctor_2w_1', null]],
                'B2'     => [['sle_1w', null], [null, 'B3']],
                'B3'     => [['hypothyroidism_1w', null], [null, 'B4']],
                'B4'     => [['syphilis_diffuse_1w', null], [null, 'B5']],
                'B5'     => [['chronic_disease_hair_loss', null], [null, 'B6']],
                'B6'     => [['drug_chemo_induced_alopecia', null], [null, 'B7']],
                'B7'     => [['telogen_effluvium', null], [null, 'B8']],
                'B8'     => [['androgenetic_alopecia', null], [null, 'B9']],
                'B9'     => [['hair_styling_alopecia', null], [null, 'B10']],
                'B10'    => [['anxiety_hair_loss', null], ['refer_doctor_2w_2', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนด Diagnosis Rules
            $rules = [
                'tinea_capitis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
โรคเชื้อราหรือกลากที่ศีรษะ (190)
• กรีซีโอฟุลวิน (ย4.10)
• ถ้าดีขึ้นกินนาน 6-8 สัปดาห์
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['190'],
                    'diagrams'   => [],
                ],
                'syphilis_alopecia_areata' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ซิฟิลิสระยะสอง (211)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['211'],
                    'diagrams'   => [],
                ],
                'trichotillomania' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากการถอนผม
• ยาทางจิตประสาท (ย17) ถ้ามีความเครียด
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'cicatricial_alopecia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รอยแผลเป็นที่หนังศีรษะ (202)
• ไม่ต้องทำอะไร
⊕ ถ้ากังวลใจ
NOTE,
                    'refs'       => ['202'],
                    'diagrams'   => [],
                ],
                'alopecia_areata_unknown' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
ผมร่วงหย่อมไม่ทราบสาเหตุ (202)
⊕ ภายใน 2 สัปดาห์
NOTE,
                    'refs'       => ['202'],
                    'diagrams'   => [],
                ],
                'refer_doctor_2w_1' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 2 สัปดาห์ เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'sle_1w' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เอสแอลอี (111)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['111'],
                    'diagrams'   => [],
                ],
                'hypothyroidism_1w' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ภาวะขาดไทรอยด์/ต่อมไทรอยด์ทำงานน้อย (124)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['124'],
                    'diagrams'   => [],
                ],
                'syphilis_diffuse_1w' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ซิฟิลิส (211)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['211'],
                    'diagrams'   => [],
                ],
                'chronic_disease_hair_loss' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากโรคเรื้อรัง
• ให้การรักษาโรคที่เป็นสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'drug_chemo_induced_alopecia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ผมร่วงจากยา (202)/การบำบัด
• แนะนำให้กลับไปปรึกษาแพทย์ที่รักษาอยู่เดิม
NOTE,
                    'refs'       => ['202'],
                    'diagrams'   => [],
                ],
                'telogen_effluvium' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ผมร่วงเนื่องจากผมหยุดการเจริญชั่วคราว (202)
• ไม่ต้องทำอะไร
NOTE,
                    'refs'       => ['202'],
                    'diagrams'   => [],
                ],
                'androgenetic_alopecia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ผมร่วงกรรมพันธุ์ (202)
• ไม่ต้องทำอะไร
⊕ ถ้ากังวลใจ
NOTE,
                    'refs'       => ['202'],
                    'diagrams'   => [],
                ],
                'hair_styling_alopecia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ผมร่วงจากการทำผม (202)
• ถ้าผมร่วงมากควรหยุดทำผม
NOTE,
                    'refs'       => ['202'],
                    'diagrams'   => [],
                ],
                'anxiety_hair_loss' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรควิตกกังวล/โรคกังวลทั่วไป (88)
• ยาทางจิตประสาท (ย17)
NOTE,
                    'refs'       => ['88'],
                    'diagrams'   => [],
                ],
                'refer_doctor_2w_2' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 2 สัปดาห์ เพื่อตรวจหาสาเหตุ
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
                    'medical_reference' => 'แผนภูมิที่ 65',
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

        $this->command->info('สร้างแผนภูมิที่ 65 (ผมร่วง/ผมบาง) สำเร็จ');
    }
}
