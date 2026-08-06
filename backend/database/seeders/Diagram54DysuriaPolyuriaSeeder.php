<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram54DysuriaPolyuriaSeeder extends Seeder
{
    private const DIAGRAM_ID = '00054';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.2', '1.3', '1.3.1', '1.3.2', '1.4',
                '2', '2.1', '2.1.1', '2.1.2', '2.1.3', '2.1.4', '2.1.5', '2.1.6',
                '2.2', '2.2.1', '2.2.1.1', '2.2.2', '2.2.3',
                '3', '3.1', '3.2', '3.3'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 54 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 54
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ปัสสาวะลำบาก/ปัสสาวะไม่ออกหรือออกน้อย/ปัสสาวะขัด (DYSURIA)/ปัสสาวะบ่อย (POLYURIA)',
                'diagram_name_en' => 'Dysuria / Polyuria',
                'description' => 'มีความผิดปกติเกี่ยวกับจำนวนครั้ง ปริมาณหรือลักษณะของการถ่ายปัสสาวะในแบบใดแบบหนึ่ง ได้แก่ ปัสสาวะบ่อยหรือออกมากกว่าปกติ ปัสสาวะลำบากหรือออกยาก ปวดขัด (ขัดเบา) หรือปวดแสบเวลาปัสสาวะ ถ่ายกะปริบกะปรอยทีละน้อย หรือปัสสาวะออกน้อยหรือไม่ออกเลย บางครั้งอาจมีอาการปวดท้องน้อยร่วมด้วย สาเหตุที่พบบ่อย 1. ปัสสาวะขัด (ขัดเบา) : กระเพาะปัสสาวะอักเสบ (141) หนองใน (208) หนองในเทียม (209) 2. ปัสสาวะบ่อยและมาก : สาเหตุจากจิตใจ เบาหวาน (117) ภาวะไตวาย (134) 3. ปัสสาวะบ่อยและทีละน้อย : ต่อมลูกหมากโต (143) หนังหุ้มปลายองคชาตตีบ (144) 4. ปัสสาวะออกน้อยหรือไม่ออกเลย : ดื่มน้ำน้อย มีไข้ ภาวะขาดน้ำ ถ้าอาการไม่ชัดเจน ถ้ามีอาการขัดเบา ให้การรักษาแบบกระเพาะปัสสาวะอักเสบ (141) ถ้าปัสสาวะออกน้อย ให้ดื่มน้ำมากๆ',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 54
            $boxes = [
                'B1'        => ['frame' => '1',       'type' => 'S', 'q' => 'ปวดขัดหรือปวดแสบปวดร้อนเวลาปัสสาวะ? หรือ ถ่ายกะปริบกะปรอย?'],
                'B1_1'      => ['frame' => '1.1',     'type' => 'S', 'q' => 'มีหนองไหลจากท่อปัสสาวะ? หรือ ตกขาว (ในผู้หญิง)?'],
                'B1_2'      => ['frame' => '1.2',     'type' => 'S', 'q' => 'มีตุ่มน้ำพองตามตัว ร่วมกับปากเปื่อย ตาแดง ตาแฉะ?'],
                'B1_3'      => ['frame' => '1.3',     'type' => 'S', 'q' => 'มีไข้หนาวสั่น?'],
                'B1_3_1'    => ['frame' => '1.3.1',   'type' => 'S', 'q' => 'เคาะเจ็บตรงสีข้าง?'],
                'B1_3_2'    => ['frame' => '1.3.2',   'type' => 'S', 'q' => 'พบในผู้ชายอายุมากกว่า 15 ปี?'],
                'B1_4'      => ['frame' => '1.4',     'type' => 'S', 'q' => 'พบในผู้ชายอายุมากกว่า 50 ปี?'],
                'B2'        => ['frame' => '2',       'type' => 'S', 'q' => 'ปัสสาวะบ่อยครั้งกว่าปกติ?'],
                'B2_1'      => ['frame' => '2.1',     'type' => 'S', 'q' => 'ปัสสาวะออกทีละมาก ๆ? หรือ ต้องตื่นขึ้นถ่ายตอนกลางดึกบ่อย?'],
                'B2_1_1'    => ['frame' => '2.1.1',   'type' => 'S', 'q' => 'มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้: หิวข้าวบ่อย? / ดื่มน้ำบ่อย? / น้ำหนักลด? / เหนื่อยง่ายโดยไม่ทราบสาเหตุ? / คันในช่องคลอด? / เป็นฝีบ่อย? / ตรวจพบน้ำตาลในปัสสาวะ?'],
                'B2_1_2'    => ['frame' => '2.1.2',   'type' => 'S', 'q' => 'ความดันโลหิตสูง? บวม? ซีด? มีประวัติเป็นโรคไต ความดันโลหิตสูง หรือเบาหวานมานาน? หรือ ตรวจพบสารไข่ขาวในปัสสาวะ?'],
                'B2_1_3'    => ['frame' => '2.1.3',   'type' => 'S', 'q' => 'หอบเหนื่อย? หรือ นอนราบไม่ได้?'],
                'B2_1_4'    => ['frame' => '2.1.4',   'type' => 'S', 'q' => 'ปวดศีรษะเรื้อรังโดยไม่ทราบสาเหตุ? หรือ ตาพร่ามัวลงเรื่อยๆ (โดยไม่ใช่เกิดจากต้อกระจก)?'],
                'B2_1_5'    => ['frame' => '2.1.5',   'type' => 'S', 'q' => 'กินยาขับปัสสาวะหรือยาอื่น? หรือ ดื่มกาแฟหรือแอลกอฮอล์?'],
                'B2_1_6'    => ['frame' => '2.1.6',   'type' => 'S', 'q' => 'เป็นหวาดวิตก กังวลหรือเครียด?'],
                'B2_2'      => ['frame' => '2.2',     'type' => 'S', 'q' => 'ปัสสาวะออกทีละน้อย? ปัสสาวะออกยาก? หรือ ปัสสาวะลำเล็กหรือไม่พุ่ง?'],
                'B2_2_1'    => ['frame' => '2.2.1',   'type' => 'S', 'q' => 'ในผู้ชายอายุมากกว่า 50 ปี?'],
                'B2_2_1_1'  => ['frame' => '2.2.1.1', 'type' => 'S', 'q' => 'น้ำหนักลด?'],
                'B2_2_2'    => ['frame' => '2.2.2',   'type' => 'S', 'q' => 'ในผู้ชายที่มีหนังหุ้มปลายองคชาตตีบ?'],
                'B2_2_3'    => ['frame' => '2.2.3',   'type' => 'S', 'q' => 'เคยมีประวัติเป็นหนองใน? หรือ ได้รับบาดเจ็บตรงท่อปัสสาวะ?'],
                'B3'        => ['frame' => '3',       'type' => 'S', 'q' => 'ปัสสาวะออกน้อยหรือไม่ออกเลย (anuria)?'],
                'B3_1'      => ['frame' => '3.1',     'type' => 'S', 'q' => 'ปวดตึงท้องน้อย? หรือ คลำได้ก้อนตึงๆ ที่ท้องน้อย?'],
                'B3_2'      => ['frame' => '3.2',     'type' => 'S', 'q' => 'ท้องเดินรุนแรง? อาเจียนรุนแรง? ตกเลือดรุนแรง? หรือ มีภาวะช็อก?'],
                'B3_3'      => ['frame' => '3.3',     'type' => 'S', 'q' => 'หลังกินยาหรือฉีดยา*? เป็นไข้มาลาเรีย หรือเป็นโรคติดเชื้ออื่นๆ? มีประวัติเป็นนิ่วในไต โรคไต โรคหัวใจ ถูกงูพิษกัด ถูกผึ้ง/ต่อรุมต่อย หรือครรภ์เป็นพิษ? หรือ ตรวจพบความดันโลหิตสูงรุนแรง?'],
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
                'B1'        => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'      => [['gonorrhea_nongnoi', null], [null, 'B1_2']],
                'B1_2'      => [['stevens_johnson_syndrome', null], [null, 'B1_3']],
                'B1_3'      => [[null, 'B1_3_1'], [null, 'B1_4']],
                'B1_3_1'    => [['acute_pyelonephritis_b1_3_1', null], [null, 'B1_3_2']],
                'B1_3_2'    => [['acute_prostatitis', null], ['uti_fever_care', null]],
                'B1_4'      => [['prostate_bladder_cancer_stricture', null], ['cystitis_care', null]],
                'B2'        => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'      => [[null, 'B2_1_1'], [null, 'B2_2']],
                'B2_1_1'    => [['diabetes_mellitus', null], [null, 'B2_1_2']],
                'B2_1_2'    => [['chronic_renal_failure', null], [null, 'B2_1_3']],
                'B2_1_3'    => [['heart_failure', null], [null, 'B2_1_4']],
                'B2_1_4'    => [['brain_tumor', null], [null, 'B2_1_5']],
                'B2_1_5'    => [['caffeine_diuretic_cause', null], [null, 'B2_1_6']],
                'B2_1_6'    => [['psychogenic_frequent_urination', null], ['diabetes_insipidus_prostate_eval', null]],
                'B2_2'      => [[null, 'B2_2_1'], [null, 'B3']],
                'B2_2_1'    => [[null, 'B2_2_1_1'], [null, 'B2_2_2']],
                'B2_2_1_1'  => [['bladder_prostate_cancer_b2_2_1_1', null], ['benign_prostatic_hyperplasia_b2_2_1_1', null]],
                'B2_2_2'    => [['phimosis', null], [null, 'B2_2_3']],
                'B2_2_3'    => [['urethral_stricture', null], ['bph_prostatitis_eval', null]],
                'B3'        => [[null, 'B3_1'], ['normal_or_hematuria_eval', null]],
                'B3_1'      => [['urinary_retention', null], [null, 'B3_2']],
                'B3_2'      => [['shock_acute_renal_failure', null], [null, 'B3_3']],
                'B3_3'      => [['acute_renal_failure_drug_toxic', null], ['oliguria_fluid_care', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'gonorrhea_nongnoi' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อาจเป็นหนองใน (208)/หนองในเทียม (209)
• ชันสูตรเพิ่มเติม
• ให้การรักษาตามสาเหตุ
NOTE,
                    'refs'       => ['208', '209'],
                    'diagrams'   => [],
                ],
                'stevens_johnson_syndrome' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
กลุ่มอาการสตีเวนส์จอห์นสัน (207.1)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['207.1'],
                    'diagrams'   => [],
                ],
                'acute_pyelonephritis_b1_3_1' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
กรวยไตอักเสบเฉียบพลัน (137)
• ชันสูตรเพิ่มเติม
• โคไตรม็อกซาโซล (ย4.7) หรืออะม็อกซีซิลลิน (ย4.2) หรือซิโพรโฟล็กซาซิน (ย4.11.2)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือซีด/ดีซ่าน/มีจุดแดงจ้ำเขียว
NOTE,
                    'refs'       => ['137'],
                    'diagrams'   => [],
                ],
                'acute_prostatitis' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง อาจเป็นต่อมลูกหมากอักเสบเฉียบพลัน (143.1)/อื่นๆ
NOTE,
                    'refs'       => ['143.1'],
                    'diagrams'   => [],
                ],
                'uti_fever_care' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
• ชันสูตรเพิ่มเติม
• โคไตรม็อกซาโซล (ย4.7) หรืออะม็อกซีซิลลิน (ย4.2) หรือซิโพรโฟล็กซาซิน (ย4.11.2)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือซีด/ดีซ่าน/มีจุดแดงจ้ำเขียว
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'prostate_bladder_cancer_stricture' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นต่อมลูกหมากโต (143)/มะเร็งกระเพาะปัสสาวะ (237.16)/มะเร็งต่อมลูกหมาก (237.15)/ท่อปัสสาวะตีบ (142)
NOTE,
                    'refs'       => ['143', '237.16', '237.15', '142'],
                    'diagrams'   => [],
                ],
                'cystitis_care' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
กระเพาะปัสสาวะอักเสบ (141)
• โคไตรม็อกซาโซล (ย4.7) หรือ อะม็อกซีซิลลิน (ย4.2) หรือซิโพรโฟล็กซาซิน (ย4.11.2)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือมีไข้หนาวสั่น/เป็นๆ หายๆ บ่อย/สงสัยเป็นต่อมลูกหมากอักเสบเรื้อรัง (143.1) (ในผู้ชายแม้อาการจะทุเลาแล้ว ก็ควรชันสูตรเพิ่มเติม)
NOTE,
                    'refs'       => ['141', '143.1'],
                    'diagrams'   => [],
                ],
                'diabetes_mellitus' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เบาหวาน (117)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['117'],
                    'diagrams'   => [],
                ],
                'chronic_renal_failure' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ภาวะไตวายเรื้อรัง (134)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['134'],
                    'diagrams'   => [],
                ],
                'heart_failure' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะหัวใจวาย (98)
• ยาขับปัสสาวะ (ย21)
⊕ ด่วน
NOTE,
                    'refs'       => ['98'],
                    'diagrams'   => [],
                ],
                'brain_tumor' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เนื้องอกสมอง (83)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['83'],
                    'diagrams'   => [],
                ],
                'caffeine_diuretic_cause' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากยา/กาแฟ/แอลกอฮอล์
• ไม่ต้องทำอะไร
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'psychogenic_frequent_urination' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากจิตใจ
• ไม่ต้องทำอะไร ยกเว้นถ้าเครียดมาก ให้ยาทางจิตประสาท (ย17)
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'diabetes_insipidus_prostate_eval' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นเบาจืด (117.1)/ต่อมลูกหมากโต (143)/ต่อมลูกหมากอักเสบเรื้อรัง (143.1)/อื่นๆ
NOTE,
                    'refs'       => ['117.1', '143', '143.1'],
                    'diagrams'   => [],
                ],
                'bladder_prostate_cancer_b2_2_1_1' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งกระเพาะปัสสาวะ (237.16)/มะเร็งต่อมลูกหมาก (237.15)
NOTE,
                    'refs'       => ['237.16', '237.15'],
                    'diagrams'   => [],
                ],
                'benign_prostatic_hyperplasia_b2_2_1_1' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ต่อมลูกหมากโต (143)/มะเร็งต่อมลูกหมาก (237.15)
• สวนปัสสาวะถ้าถ่ายไม่ออก
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['143', '237.15'],
                    'diagrams'   => [],
                ],
                'phimosis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หนังหุ้มปลายองคชาตตีบ (144)
• ยาปฏิชีวนะ (ย4) ถ้ามีหนอง
• แนะนำให้ไปขลิบที่โรงพยาบาล
NOTE,
                    'refs'       => ['144'],
                    'diagrams'   => [],
                ],
                'urethral_stricture' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ท่อปัสสาวะตีบ (142)
• แนะนำให้ไปถ่างที่โรงพยาบาล
NOTE,
                    'refs'       => ['142'],
                    'diagrams'   => [],
                ],
                'bph_prostatitis_eval' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ ในหญิงวัยเจริญพันธุ์ อาจเกิดจาก เนื้องอกมดลูก (152.1)/มะเร็งรังไข่ (237.5)/ตั้งครรภ์ (154)
หรือ อาจเป็นต่อมลูกหมากโต (143)/มะเร็งต่อมลูกหมาก (237.15)/ต่อมลูกหมากอักเสบเรื้อรัง (143.1)/อื่นๆ
NOTE,
                    'refs'       => ['152.1', '237.5', '154', '143', '237.15', '143.1'],
                    'diagrams'   => [],
                ],
                'urinary_retention' => [
                    'urgency'    => 'P',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปัสสาวะคั่งกระเพาะ
• สวนปัสสาวะ
⊕ ถ้าสวนไม่ได้หรือเป็นซ้ำอีกครั้ง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'shock_acute_renal_failure' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ช็อก (91)/ภาวะไตวายเฉียบพลัน (134)
⊕ ด่วน
NOTE,
                    'refs'       => ['91', '134'],
                    'diagrams'   => [],
                ],
                'acute_renal_failure_drug_toxic' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นภาวะไตวายเฉียบพลัน (134)
NOTE,
                    'refs'       => ['134'],
                    'diagrams'   => [],
                ],
                'oliguria_fluid_care' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
• ดื่มน้ำมากๆ
⊕ ถ้าไม่ดีขึ้นใน 24 ชั่วโมง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'normal_or_hematuria_eval' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ถ้าไม่มีอาการผิดปกติอื่น ๆ ไม่ต้องทำอะไร
• ถ้าปัสสาวะขุ่น/มีสีผิดปกติ ดูแผนภูมิที่ 55 กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00055'],
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
                    'medical_reference' => 'แผนภูมิที่ 54',
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

        $this->command->info('สร้างแผนภูมิที่ 54 (ปัสสาวะลำบาก/ปัสสาวะไม่ออกหรือออกน้อย/ปัสสาวะขัด/ปัสสาวะบ่อย) กรอบ 1-3.3 สำเร็จ');
    }
}
