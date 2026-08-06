<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram24BlurredVisionSeeder extends Seeder
{
    private const DIAGRAM_ID = '00024';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '3.1', '3.1.1', '3.2',
                '4', '5', '6', '7', '8', '8.1',
                '9', '10', '11', '12', '12.1', '13',
                '14', '15', '15.1', '16'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 24 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 24
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ตามัว/ตาฟาง/มองเห็นเงาหรือภาพผิดปกติ',
                'diagram_name_en' => 'Blurred Vision / Visual Disturbances',
                'description' => 'ตามองเห็นไม่ชัด สายตามืดมัว เห็นภาพผิดเพี้ยน เห็นภาพซ้อน เห็นเงาหยากไย่/แมลงลอยไปมา หรือเห็นแสงวาบคล้ายฟ้าแลบ/แสงแฟลชถ่ายรูป หรือลานสายตาแคบ (มองไม่เห็นด้านข้าง) สาเหตุที่พบบ่อย ต้อกระจก (180) ต้อเนื้อ (179) สายตาผิดปกติ (178) น้ำวุ้นลูกตาเสื่อม เบาหวาน (117) ความดันโลหิตสูง (92) ถ้าอาการไม่ชัดเจน และไม่สงสัยว่าเป็นต้อกระจก ควรปรึกษาแพทย์ภายใน 1 สัปดาห์',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 24
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'ปวดศีรษะ? หรือ ปวดตา?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'มีขี้ตา?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'ตามืด/ตามัวลงอย่างฉับพลัน และต่อเนื่องไม่หาย?'],
                'B3_1'    => ['frame' => '3.1',   'type' => 'S', 'q' => 'ได้รับบาดเจ็บ?'],
                'B3_1_1'  => ['frame' => '3.1.1', 'type' => 'S', 'q' => 'มีเลือดค้างอยู่หลังกระจกตาดำ?'],
                'B3_2'    => ['frame' => '3.2',   'type' => 'S', 'q' => 'รูม่านตา 2 ข้างไม่เท่ากัน?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'กระจกตาดำเป็นแผล หรือมีฝ้าขาว?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'มีเยื่อเหลืองๆ แดงๆ งอกบังตาดำ?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => 'แก้วตา (เลนส์ตา) ขุ่น?'],
                'B7'      => ['frame' => '7',     'type' => 'S', 'q' => 'มองเห็นแสงวาบคล้ายฟ้าแลบ/แสงแฟลชถ่ายรูปเวลาหลับตา หรืออยู่ในที่มืด? หรือ เห็นเงาคล้ายม่านกั้นอยู่ที่ขอบลานสายตา?'],
                'B8'      => ['frame' => '8',     'type' => 'S', 'q' => 'มองเห็นเงาหยากไย่/ยุง/แมลงวัน ลอยไปมา?'],
                'B8_1'    => ['frame' => '8.1',   'type' => 'S', 'q' => 'เกิดขึ้นฉับพลัน? เห็นเงาหยากไย่เพิ่มมากขึ้นรวดเร็ว? เห็นเงาสีแดงบังอยู่ในลูกตา? ตามัว? หรือ เห็นเงาหรือภาพผิดเพี้ยน?'],
                'B9'      => ['frame' => '9',     'type' => 'S', 'q' => 'เห็นภาพซ้อน?'],
                'B10'     => ['frame' => '10',    'type' => 'S', 'q' => 'ตามัวหลังกินยา หรือหยอดยาหยอดตา?'],
                'B11'     => ['frame' => '11',    'type' => 'S', 'q' => 'ตาฟางตอนกลางคืนในผู้ที่ขาดอาหาร/โภชนาการไม่ดี? หรือ มีเกล็ดกระดี่บนตาขาว?'],
                'B12'     => ['frame' => '12',    'type' => 'S', 'q' => 'มองเห็นไม่ชัด (เหมือนภาพถ่ายที่โฟกัสไม่ดี) เวลามองใกล้หรือมองไกล หรือทั้งใกล้และไกล เห็นชัดเมื่อมองผ่านรูเล็กเท่ารูเข็ม?'],
                'B12_1'   => ['frame' => '12.1',  'type' => 'S', 'q' => 'อ่านหนังสือใกล้ตาไม่ชัดในคนอายุมากกว่า 40 ปี?'],
                'B13'     => ['frame' => '13',    'type' => 'S', 'q' => 'ลานสายตาแคบ/มองด้านข้างไม่เห็น (เดินเตะขอบโต๊ะ/ประตู/เดินชนบ่อย)?'],
                'B14'     => ['frame' => '14',    'type' => 'S', 'q' => 'เป็นเบาหวาน หรือความดันโลหิตสูงมานาน?'],
                'B15'     => ['frame' => '15',    'type' => 'S', 'q' => 'สายตาค่อยๆ มัวลงอย่างช้าๆ กินเวลาเป็นเดือนแร็มปี ในคนอายุมากกว่า 50 ปี?'],
                'B15_1'   => ['frame' => '15.1',  'type' => 'S', 'q' => 'มองเห็นภาพมัวเหมือนมีหมอกบัง? หรือ ใช้เครื่องส่องตาไม่มีปฏิกิริยาสีแดง?'],
                'B16'     => ['frame' => '16',    'type' => 'S', 'q' => 'ตรงกลางตาดำมีสีขาวคล้ายตาแมว พบในเด็กเล็ก?'],
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
                'B1'      => [['headache_eye_pain_ref', null], [null, 'B2']],
                'B2'      => [['conjunctivitis_ref', null], [null, 'B3']],
                'B3'      => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'    => [[null, 'B3_1_1'], [null, 'B3_2']],
                'B3_1_1'  => [['hyphema', null], ['retinal_detachment_or_severe', null]],
                'B3_2'    => [['acute_glaucoma_uveitis', null], ['severe_neurological_eye_cause', null]],
                'B4'      => [['corneal_ulcer', null], [null, 'B5']],
                'B5'      => [['pterygium', null], [null, 'B6']],
                'B6'      => [['cataract_early', null], [null, 'B7']],
                'B7'      => [['vitreous_retinal_detachment_early', null], [null, 'B8']],
                'B8'      => [[null, 'B8_1'], [null, 'B9']],
                'B8_1'    => [['retinal_detachment_or_vitreous_bleed', null], ['floater_observation', null]],
                'B9'      => [['diplopia_causes', null], [null, 'B10']],
                'B10'     => [['drug_induced_blurred_vision', null], [null, 'B11']],
                'B11'     => [['vitamin_a_deficiency', null], [null, 'B12']],
                'B12'     => [[null, 'B12_1'], [null, 'B13']],
                'B12_1'   => [['presbyopia', null], ['refractive_error', null]],
                'B13'     => [['chronic_glaucoma_or_brain_tumor', null], [null, 'B14']],
                'B14'     => [['diabetic_hypertensive_retinopathy', null], [null, 'B15']],
                'B15'     => [[null, 'B15_1'], [null, 'B16']],
                'B15_1'   => [['cataract', null], ['chronic_glaucoma_or_amd', null]],
                'B16'     => [['retinoblastoma', null], ['unexplained_blurred_vision', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'headache_eye_pain_ref' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 21 ปวดศีรษะ กรอบที่ 2
หรือ แผนภูมิที่ 23 ปวดตา กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00021', '00023'],
                ],
                'conjunctivitis_ref' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 25 เคืองตา/คันตา/ตาแดง/ตาแฉะ กรอบที่ 2.1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00025'],
                ],
                'hyphema' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
เลือดออกในช่องลูกตาหน้า (185)
⊕ ด่วน
NOTE,
                    'refs'       => ['185'],
                    'diagrams'   => [],
                ],
                'retinal_detachment_or_severe' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นจอตาขอก (181.1)/สาเหตุร้ายแรงอื่นๆ
NOTE,
                    'refs'       => ['181.1'],
                    'diagrams'   => [],
                ],
                'acute_glaucoma_uveitis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ต้อหินชนิดเฉียบพลัน (181)/ม่านตาอักเสบ (183)
⊕ ด่วน
NOTE,
                    'refs'       => ['181', '183'],
                    'diagrams'   => [],
                ],
                'severe_neurological_eye_cause' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุร้ายแรงอื่นๆ เช่น เนื้องอกสมอง (83)/จอตาถอก (181.1)/จุดภาพชัดเสื่อมตามอายุ (181.2)/หลอดเลือดจอตาอุดตัน (central retinal artery/vein occlusion)
NOTE,
                    'refs'       => ['83', '181.1', '181.2'],
                    'diagrams'   => [],
                ],
                'corneal_ulcer' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
แผลกระจกตา (182)
⊕ ด่วน ถ้ามีอาการปวดตา/ตาแดง/เกิดขึ้นฉับพลัน
NOTE,
                    'refs'       => ['182'],
                    'diagrams'   => [],
                ],
                'pterygium' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ต้อเนื้อ (179)
• แนะนำไปลอกที่โรงพยาบาล
NOTE,
                    'refs'       => ['179'],
                    'diagrams'   => [],
                ],
                'cataract_early' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ต้อกระจก (180)
• แนะนำไปตรวจที่โรงพยาบาลเมื่อมีโอกาส
⊕ ถ้าปวดตา หรือมองไม่เห็น
NOTE,
                    'refs'       => ['180'],
                    'diagrams'   => [],
                ],
                'vitreous_retinal_detachment_early' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจเป็นน้ำวุ้นลูกตาถอก/จอตาลึก/จอตาถอก (181.1)
NOTE,
                    'refs'       => ['181.1'],
                    'diagrams'   => [],
                ],
                'retinal_detachment_or_vitreous_bleed' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจเป็นจอตาถอก/เลือดออกในน้ำวุ้นลูกตา (ดู "โรคที่ 181.1")
NOTE,
                    'refs'       => ['181.1'],
                    'diagrams'   => [],
                ],
                'floater_observation' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สังเกตอาการ
• ถ้ามีจำนวนไม่มาก คงเรื้อรังมานาน
⊕ ถ้าเห็นเงาเพิ่มมากขึ้น หรือเห็นแสงวาบคล้ายฟ้าแลบ/ตามัว
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'diplopia_causes' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง - 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง - 1 สัปดาห์ อาจมีสาเหตุจากศีรษะได้รับบาดเจ็บ (81)/โรคหลอดเลือดสมอง (76)/เนื้องอกสมอง (83)/ไมแอสทีเนียเกรวิส (79)/ประสาทเลี้ยงกล้ามเนื้อกลอกลูกตาเสื่อมจากเบาหวาน (117)/คอพอกเป็นพิษ (121)
NOTE,
                    'refs'       => ['81', '76', '83', '79', '117', '121'],
                    'diagrams'   => [],
                ],
                'drug_induced_blurred_vision' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ตามัวจากยา*
• หยุดยา
• แนะนำให้กลับไปพบแพทย์ที่รักษาอยู่เดิม
* ยาที่ทำให้ตามัว เช่น คลอโรควีน (ยร.1), ควนิน (ยร.3), ไอเอ็นเอช (ย4.13), อีแทมบูทอล (ย4.15), สเตรปโตไมซิน (ย4.12), อะโทรพีน (ย20), เฟนิลบิวตาโซน, ยาหยอดตาอะโทรพีน เป็นต้น
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'vitamin_a_deficiency' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคขาดวิตามินเอ (131)
• วิตามินเอ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือมีการติดเชื้อ
NOTE,
                    'refs'       => ['131'],
                    'diagrams'   => [],
                ],
                'presbyopia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สายตายาวในผู้สูงอายุ (178)
• แนะนำไปตรวจวัดสายตา
NOTE,
                    'refs'       => ['178'],
                    'diagrams'   => [],
                ],
                'refractive_error' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สายตาผิดปกติ (178)
• แนะนำไปตรวจวัดสายตา
NOTE,
                    'refs'       => ['178'],
                    'diagrams'   => [],
                ],
                'chronic_glaucoma_or_brain_tumor' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน อาจเป็นต้อหินเรื้อรัง (181)/เนื้องอกสมอง (83)
NOTE,
                    'refs'       => ['181', '83'],
                    'diagrams'   => [],
                ],
                'diabetic_hypertensive_retinopathy' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ตามัวเนื่องจากเบาหวาน (117)/ความดันโลหิตสูง (92)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['117', '92'],
                    'diagrams'   => [],
                ],
                'cataract' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ต้อกระจก (180)
• แนะนำไปตรวจที่โรงพยาบาลเมื่อมีโอกาส
NOTE,
                    'refs'       => ['180'],
                    'diagrams'   => [],
                ],
                'chronic_glaucoma_or_amd' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นต้อหินเรื้อรัง (181)/จุดภาพชัดเสื่อมตามอายุ (181.2)
NOTE,
                    'refs'       => ['181', '181.2'],
                    'diagrams'   => [],
                ],
                'retinoblastoma' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
มะเร็งลูกตาในเด็ก (237.20)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['237.20'],
                    'diagrams'   => [],
                ],
                'unexplained_blurred_vision' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ
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
                    'medical_reference' => 'แผนภูมิที่ 24',
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

        $this->command->info('สร้างแผนภูมิที่ 24 (ตามัว/ตาฟาง/มองเห็นเงาหรือภาพผิดปกติ - BLURRED VISION) กรอบ 1-16 สำเร็จ');
    }
}
