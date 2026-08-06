<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram63RashSeeder extends Seeder
{
    private const DIAGRAM_ID = '00063';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '5', '5.1', '5.2', '6', '7', '8',
                '9', '10', '11', '11.1', '11.2', '11.3', '12', '13',
                '14', '15', '15.1', '15.2'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 63 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 63
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ผื่น ตุ่ม วงด่าง (RASH)',
                'diagram_name_en' => 'Rash',
                'description' => 'ผิวหนังขึ้นเป็นผื่น ตุ่ม หรือวงด่างๆ สาเหตุที่พบบ่อย สำหรับผื่น ตุ่ม หรือวงด่าง ที่ไม่มีอาการคัน : เกลื้อน (191) โรคด่างขาว (203) ฝี/พุพอง (192.1, 192.2) หูด (189) เริม (187) งูสวัด (188) ถ้าอาการไม่ชัดเจน ควรปรึกษาแพทย์เมื่อเป็นอยู่นานกว่า 1 เดือน ถ้ามีไข้ร่วมด้วย ดูแผนภูมิที่ 4 ถ้ามีอาการคันร่วมด้วย ดูแผนภูมิที่ 64',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes)
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'มีตุ่มน้ำพุพองตามตัว ร่วมกับ ปากเปื่อย ตาแดงตาแฉะ?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'มีไข้?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'คัน?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'ผื่นแดงคล้ายรอยถลอก มีขอบเขตชัดเจน พบที่รักแร้/ขาหนีบ/ใต้ราวนม/สะดือ/ซอกสะโพก/ง่ามนิ้ว?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'ตุ่มน้ำใสเล็กๆ?'],
                'B5_1'    => ['frame' => '5.1',   'type' => 'S', 'q' => 'ขึ้นเป็นแนวยาว ตามหน้าอก/ใบหน้า/แขนขา เพียงซีกเดียวของร่างกาย?'],
                'B5_2'    => ['frame' => '5.2',   'type' => 'S', 'q' => 'ขึ้นเป็นกลุ่มหรือเป็นหย่อมที่ ริมฝีปาก/แก้ม/จมูก/หู/อวัยวะสืบพันธุ์/ก้น/อื่นๆ?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => 'เป็นฝี หรือตุ่มหนอง?'],
                'B7'      => ['frame' => '7',     'type' => 'S', 'q' => 'ตุ่มนูนตรงรอยแผลเป็น?'],
                'B8'      => ['frame' => '8',     'type' => 'S', 'q' => 'หูด? หูดข้าวสุก? หรือ หงอนไก่?'],
                'B9'      => ['frame' => '9',     'type' => 'S', 'q' => 'นิ้วเท้าหรือฝ่าเท้าเป็นตาปลาหรือหนังหนาด้าน?'],
                'B10'     => ['frame' => '10',    'type' => 'S', 'q' => 'เป็นตุ่มแข็งเท่าเม็ดสาคูกระจายทั่วไป?'],
                'B11'     => ['frame' => '11',    'type' => 'S', 'q' => 'ขึ้นเป็นผื่นแดงเล็กๆ กระจายทั่วตัว?'],
                'B11_1'   => ['frame' => '11.1',  'type' => 'S', 'q' => 'เกิดหลังกินยา แอมพิซิลลิน หรือ ยาอื่นๆ?'],
                'B11_2'   => ['frame' => '11.2',  'type' => 'S', 'q' => 'ขึ้นบนฝ่ามือฝ่าเท้า? หรือ เคยเป็นแผลที่อวัยวะเพศมาก่อน?'],
                'B11_3'   => ['frame' => '11.3',  'type' => 'S', 'q' => 'ผื่นแดงกระจายตามลำตัวด้านหน้าคล้ายรูปตัว T ด้านหลังคล้ายต้นคริสต์มาส? หรือ พบผื่นขนาด 2-6 ซม. ตูดคล้ายกลากขึ้น 1-2 แห่ง?'],
                'B12'     => ['frame' => '12',    'type' => 'S', 'q' => 'เป็นวงด่าง เป็นตุ่มหรือแผ่นหนา เข็มแทงไม่เจ็บ หรือ หูหนาตาเล่อ?'],
                'B13'     => ['frame' => '13',    'type' => 'S', 'q' => 'ขึ้นเป็นปื่นหนา มีเกล็ดสีเงินคลุมที่ผิว แกะออกมีเลือดซิบๆ ตรงข้อศอก ข้อเข่า ก้นกบ หน้าแข้ง เป็นๆหายๆ เรื้อรัง?'],
                'B14'     => ['frame' => '14',    'type' => 'S', 'q' => 'วงด่างเล็กๆ ขึ้นติดๆกัน แผ่เป็นวงกว้างในบริเวณที่มีเหงื่อมาก (เช่น ซอกคอ แผ่นหลังหรือหน้าอก)?'],
                'B15'     => ['frame' => '15',    'type' => 'S', 'q' => 'รอยด่างขาวขึ้นเฉพาะแห่ง?'],
                'B15_1'   => ['frame' => '15.1',  'type' => 'S', 'q' => 'ขอบเขตชัดเจน ขึ้นกระจายพร้อมกันทั้ง 2 ข้างของร่างกาย?'],
                'B15_2'   => ['frame' => '15.2',  'type' => 'S', 'q' => 'ขึ้นที่หน้าหรือไหล่ ในเด็ก/วัยรุ่น?'],
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
                'B1'     => [['stevens_johnson_syndrome_24h', null], [null, 'B2']],
                'B2'     => [['see_diagram4_fever_rash', null], [null, 'B3']],
                'B3'     => [['see_diagram64_itchy_rash', null], [null, 'B4']],
                'B4'     => [['candidiasis_intertrigo', null], [null, 'B5']],
                'B5'     => [[null, 'B5_1'], [null, 'B6']],
                'B5_1'   => [['herpes_zoster', null], [null, 'B5_2']],
                'B5_2'   => [['herpes_simplex', null], ['symptomatic_care_1w', null]],
                'B6'     => [['abscess_impetigo_skin_infection', null], [null, 'B7']],
                'B7'     => [['keloid_hypertrophic_scar', null], [null, 'B8']],
                'B8'     => [['warts_molluscum_condyloma', null], [null, 'B9']],
                'B9'     => [['corns_calluses', null], [null, 'B10']],
                'B10'    => [['cysticercosis_2w', null], [null, 'B11']],
                'B11'    => [[null, 'B11_1'], [null, 'B12']],
                'B11_1'  => [['drug_eruption', null], [null, 'B11_2']],
                'B11_2'  => [['syphilis_secondary', null], [null, 'B11_3']],
                'B11_3'  => [['pityriasis_rosea', null], [null, 'B12']],
                'B12'    => [['leprosy_1w', null], [null, 'B13']],
                'B13'    => [['psoriasis_1w', null], [null, 'B14']],
                'B14'    => [['tinea_versicolor', null], [null, 'B15']],
                'B15'    => [[null, 'B15_1'], ['chronic_rash_evaluation_2w', null]],
                'B15_1'  => [['vitiligo_1m', null], [null, 'B15_2']],
                'B15_2'  => [['pityriasis_alba', null], ['chronic_rash_evaluation_2w', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนด Diagnosis Rules
            $rules = [
                'stevens_johnson_syndrome_24h' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
กลุ่มอาการสตีเวนส์จอห์นสัน (207.1)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['207.1'],
                    'diagrams'   => [],
                ],
                'see_diagram4_fever_rash' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 4 ไข้ร่วมกับมีผื่นหรือตุ่มขึ้น กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00004'],
                ],
                'see_diagram64_itchy_rash' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 64 ผื่น/ตุ่ม/วงด่าง ร่วมกับมีอาการคัน กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00064'],
                ],
                'candidiasis_intertrigo' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
โรคเชื้อราแคนดิดา (191.1)
• ทาครีมรักษาโรคเชื้อรา (ย25.2)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์ อาจเป็นโซริอาซิส (203.2)/อื่น ๆ
NOTE,
                    'refs'       => ['191.1', '203.2'],
                    'diagrams'   => [],
                ],
                'herpes_zoster' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ตามอาการ / เร่งด่วนกรณีภาวะแทรกซ้อน',
                    'note'       => <<<NOTE
งูสวัด (188)
• ทายาแก้ผื่นคัน (ย25.5)
• ยาแก้ปวด (ย1)
• ให้อะไซโคลเวียร์ (ย4.17) ในผู้ป่วยอายุมากกว่า 50 ปี หรือปวดรุนแรง
⊕ ถ้าขึ้นที่บริเวณหน้า หรือมีภาวะภูมิคุ้มกันบกพร่อง
NOTE,
                    'refs'       => ['188'],
                    'diagrams'   => [],
                ],
                'herpes_simplex' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เริม (187)
• ประคบด้วยน้ำแข็ง
• ทาด้วยโพวิโดนไอโอดีน
• อะไซโคลเวียร์ (ย4.17)
⊕ ถ้าขึ้นเป็นครั้งแรก หรือเป็นรุนแรง หรือขึ้นที่ตา
NOTE,
                    'refs'       => ['187'],
                    'diagrams'   => [],
                ],
                'symptomatic_care_1w' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
• รักษาตามอาการ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'abscess_impetigo_skin_infection' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
ฝี (192.1)/พุพอง (192.2)
• ไดคล็อกซาซิลลิน (ย4.3) หรืออีริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือเป็นเบาหวาน (117)/เป็นๆ หายๆ บ่อย/เป็นฝีคัณฑสูตร (58.2)
NOTE,
                    'refs'       => ['192.1', '192.2'],
                    'diagrams'   => [],
                ],
                'keloid_hypertrophic_scar' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
แผลคีลอยด์ (206)
• ถ้าคันทาครีมสเตียรอยด์ (ย25.6)
⊕ ถ้าโตมาก
NOTE,
                    'refs'       => ['206'],
                    'diagrams'   => [],
                ],
                'warts_molluscum_condyloma' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูด/หูดข้าวสุก (189)/หงอนไก่ (189.1)
• จี้หรือตัดออก
NOTE,
                    'refs'       => ['189', '189.1'],
                    'diagrams'   => [],
                ],
                'corns_calluses' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ตาปลา/หนังหนาด้าน (207)
• ปิดพลาสเตอร์ที่มีกรดซาลิไซลิก หรือทายากัดตาปลา
⊕ ถ้าเป็นมาก ปวดมาก ติดเชื้อรุนแรง เป็นเบาหวาน หรือสงสัยโครงสร้างเท้าผิดปกติ
NOTE,
                    'refs'       => ['207'],
                    'diagrams'   => [],
                ],
                'cysticercosis_2w' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 2 สัปดาห์',
                    'note'       => <<<NOTE
โรคพยาธิตืดหมู (232)
⊕ ภายใน 2 สัปดาห์ เพื่อตรวจให้แน่ใจ
NOTE,
                    'refs'       => ['232'],
                    'diagrams'   => [],
                ],
                'drug_eruption' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ผื่นจากยา
• หยุดยา
• แนะนำให้กลับไปพบแพทย์ที่รักษาอยู่เดิม
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'syphilis_secondary' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ซิฟิลิส (211)
• ชันสูตรเพิ่มเติม
• ยาปฏิชีวนะรักษาซิฟิลิส
NOTE,
                    'refs'       => ['211'],
                    'diagrams'   => [],
                ],
                'pityriasis_rosea' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 6 สัปดาห์',
                    'note'       => <<<NOTE
ผื่นพีอาร์ (203.1)
• ถ้าคันกินยาแก้แพ้ (ย7) และทาครีมสเตียรอยด์ (ย25.6)
⊕ ถ้าไม่ดีขึ้นใน 6 สัปดาห์ หรือลุกลามมากขึ้น หรือสงสัยโรคอื่น
NOTE,
                    'refs'       => ['203.1'],
                    'diagrams'   => [],
                ],
                'leprosy_1w' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
โรคเรื้อน (197)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['197'],
                    'diagrams'   => [],
                ],
                'psoriasis_1w' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
โซริอาซิส (203.2)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['203.2'],
                    'diagrams'   => [],
                ],
                'tinea_versicolor' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เกลื้อน (191)
• ทาโซเดียมไทโอซัลเฟต (ย25.3) หรือใช้แชมพูคีโตโคนาโซล (ย4.9) ฟอกล้าง
NOTE,
                    'refs'       => ['191'],
                    'diagrams'   => [],
                ],
                'vitiligo_1m' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 1 เดือน',
                    'note'       => <<<NOTE
โรคด่างขาว (203)
⊕ ภายใน 1 เดือน
NOTE,
                    'refs'       => ['203'],
                    'diagrams'   => [],
                ],
                'pityriasis_alba' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กลากน้ำนม (203)
• ทาครีมสเตียรอยด์ (ย25.6)
⊕ ถ้าลุกลามมากขึ้น
NOTE,
                    'refs'       => ['203'],
                    'diagrams'   => [],
                ],
                'chronic_rash_evaluation_2w' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
⊕ ถ้ามีอาการนานเกิน 2 สัปดาห์ หรือเป็นๆ หายๆ เรื้อรัง อาจเกิดจากสาเหตุอื่น ๆ เช่น ผื่นพีอาร์ (203.1) โซริอาซิส (203.2)
NOTE,
                    'refs'       => ['203.1', '203.2'],
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
                    'medical_reference' => 'แผนภูมิที่ 63',
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

        $this->command->info('สร้างแผนภูมิที่ 63 (ผื่น ตุ่ม วงด่าง) สำเร็จ');
    }
}
