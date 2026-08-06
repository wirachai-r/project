<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram14LocalSwellingSeeder extends Seeder
{
    private const DIAGRAM_ID = '00014';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.2', '2', '2.1', '3', '4',
                '5', '5.1', '5.2', '5.3', '5.4', '6', '6.1', '6.2',
                '7', '7.1', '7.2', '7.3', '7.4', '7.5', '7.6',
                '8', '8.1', '8.2', '9', '9.1', '9.2', '9.3',
                '10', '10.1', '10.2', '10.3', '11', '11.1', '11.1.1', '11.1.2', '11.1.3', '11.1.4',
                '11.2', '11.2.1', '11.2.2', '11.2.3', '11.3', '12', '12.1', '12.2', '13', '13.1', '13.2'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 14 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 14
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'บวมเฉพาะที่/มีก้อน (LOCAL SWELLING/MASS)',
                'diagram_name_en' => 'Local Swelling / Mass',
                'description' => 'มีอาการบวมเฉพาะส่วนใดส่วนหนึ่งของร่างกาย หรือมีก้อนเกิดขึ้นที่บริเวณใดบริเวณหนึ่งของร่างกาย หรือมีอาการบวมที่ขาข้างเดียว สาเหตุที่พบบ่อย 1. มีก้อนที่คอ : คอพอกธรรมดา (120) คอพอกเป็นพิษ (121) 2. หนังตาบวม : เยื่อตาขาวอักเสบ (171) แพ้ยา 3. ริมฝีปากบวม : แพ้ยาหรือแพ้อาหาร 4. ก้อนที่เต้านม : มะเร็งเต้านม (237) ฝีเต้านม (193) 5. ต่อมน้ำเหลือง : ต่อมน้ำเหลืองอักเสบ (194) 6. ไข่ดันบวม : ต่อมน้ำเหลืองอักเสบ (194) ผื่นมะม่วง (212) ไส้เลื่อน (57) ถุงน้ำที่ถุงอัณฑะ (145) หลอดเลือดอัณฑะขอด (146) 8. ผิวหนัง : ฝี (192.1) เนื้อเยื่อใต้ผิวหนังชั้นลึกอักเสบ (192.4) ฟกช้ำ ลมพิษ (198) ตัวจี๊ด (236) ก้อนไขมันหรือถุงน้ำ (ซีสต์) ถ้าอาการไม่ชัดเจน ควรแนะนำไปโรงพยาบาล คางบวมหรือคอบวม ดูแผนภูมิที่ 33 ข้อบวม ดูแผนภูมิที่ 52',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 14
            $boxes = [
                'B1'        => ['frame' => '1',       'type' => 'S', 'q' => 'ได้รับบาดเจ็บ?'],
                'B1_1'      => ['frame' => '1.1',     'type' => 'S', 'q' => 'สงสัยกระดูกหัก?'],
                'B1_2'      => ['frame' => '1.2',     'type' => 'S', 'q' => 'ข้อแพลง?'],
                'B2'        => ['frame' => '2',       'type' => 'S', 'q' => 'บวมที่ขาข้างเดียว?'],
                'B2_1'      => ['frame' => '2.1',     'type' => 'S', 'q' => 'ปวดและกดเจ็บที่น่อง?'],
                'B3'        => ['frame' => '3',       'type' => 'S', 'q' => 'รอยบวมคันและเลื่อนไปเรื่อยๆ?'],
                'B4'        => ['frame' => '4',       'type' => 'S', 'q' => 'ผื่นเป็นนูนขอบแดงและคัน กระจายอยู่ทั่วไป?'],
                'B5'        => ['frame' => '5',       'type' => 'S', 'q' => 'หนังตาบวม?'],
                'B5_1'      => ['frame' => '5.1',     'type' => 'S', 'q' => 'เท้าบวมทั้ง 2 ข้าง? หรือ ท้องบวม?'],
                'B5_2'      => ['frame' => '5.2',     'type' => 'S', 'q' => 'ขอบตาบวมแดง มีแผลเปื่อย หรือมีสะเก็ดสีขาว?'],
                'B5_3'      => ['frame' => '5.3',     'type' => 'S', 'q' => 'คัน?'],
                'B5_4'      => ['frame' => '5.4',     'type' => 'M', 'q' => 'มีอาการอย่างน้อย 2 อย่างดังต่อไปนี้: น้ำหนักขึ้น, เส้นผมบางและหักง่าย, ผิวหนังหยาบ แห้ง และเย็น, ขี้หนาว, เสียงแหบ?'],
                'B6'        => ['frame' => '6',       'type' => 'S', 'q' => 'ริมฝีปากบวม?'],
                'B6_1'      => ['frame' => '6.1',     'type' => 'S', 'q' => 'ปวดฟัน? ฟันผุ? หรือ เหงือกอักเสบ?'],
                'B6_2'      => ['frame' => '6.2',     'type' => 'S', 'q' => 'คัน เกิดหลังกินยา อาหารบางชนิด หรือถูกแมลงต่อย?'],
                'B7'        => ['frame' => '7',       'type' => 'S', 'q' => 'คอพอก (ต่อมไทรอยด์โต)?'],
                'B7_1'      => ['frame' => '7.1',     'type' => 'M', 'q' => 'มีอาการอย่างน้อย 2 อย่างดังต่อไปนี้: เหนื่อยง่าย, ขี้ร้อน (เหงื่อออกมาก), มือสั่น, น้ำหนักลด, ชีพจรมากกว่า 120 ครั้ง/นาที, ตาโปน?'],
                'B7_2'      => ['frame' => '7.2',     'type' => 'M', 'q' => 'มีอาการอย่างน้อย 2 อย่างดังต่อไปนี้: น้ำหนักขึ้น, เส้นผมบางและหักง่าย, ผิวหนังหยาบ แห้ง และเย็น, ขี้หนาว, เสียงแหบ?'],
                'B7_3'      => ['frame' => '7.3',     'type' => 'S', 'q' => 'ต่อมไทรอยด์มีอาการเจ็บปวด? หรือ มีไข้?'],
                'B7_4'      => ['frame' => '7.4',     'type' => 'S', 'q' => 'เป็นก้อนเดี่ยวแข็ง? ติดแน่นกับเนื้อเยื่อโดยรอบ? โตเร็ว? หรือ ต่อมน้ำเหลืองข้างคอโต?'],
                'B7_5'      => ['frame' => '7.5',     'type' => 'S', 'q' => 'มีอาการคอโตตอนเป็นสาววัยรุ่น หรือหลังตั้งครรภ์?'],
                'B7_6'      => ['frame' => '7.6',     'type' => 'S', 'q' => 'มีประวัติอยู่อาศัยในพื้นที่ที่มีโรคคอพอกประจำถิ่น (ทางภาคอีสานหรือภาคเหนือ)?'],
                'B8'        => ['frame' => '8',       'type' => 'S', 'q' => 'ก้อนที่เต้านม?'],
                'B8_1'      => ['frame' => '8.1',     'type' => 'S', 'q' => 'ปวดบวมแดงร้อน?'],
                'B8_2'      => ['frame' => '8.2',     'type' => 'M', 'q' => 'มีอาการอย่างน้อย 1 อย่างดังต่อไปนี้: ก้อนแข็งขรุขระหรือโตเร็ว, หัวนมบุ๋ม, มีเลือดออก หรือน้ำเหลืองไหล, มีก้อนที่รักแร้ร่วมด้วย?'],
                'B9'        => ['frame' => '9',       'type' => 'S', 'q' => 'ก้อนของต่อมน้ำเหลือง (ต่อมน้ำเหลืองโต)?'],
                'B9_1'      => ['frame' => '9.1',     'type' => 'S', 'q' => 'มีก้อนโตพร้อมกันมากกว่า 1 แห่ง ในบริเวณที่ไม่ติดต่อกัน? มีไข้นานกว่า 1 สัปดาห์? มีเลือดออกหรือมีจ้ำเขียวขึ้น? หรือ ตับ/ม้ามโต?'],
                'B9_2'      => ['frame' => '9.2',     'type' => 'S', 'q' => 'พบที่ไหปลาร้า หรือรักแร้?'],
                'B9_3'      => ['frame' => '9.3',     'type' => 'S', 'q' => 'กดเจ็บ? หรือ มีการอักเสบในบริเวณใกล้เคียง?'],
                'B10'       => ['frame' => '10',      'type' => 'S', 'q' => 'ก้อนที่ขาหนีบ (ไข่ดันบวม)?'],
                'B10_1'     => ['frame' => '10.1',    'type' => 'S', 'q' => 'พบหลังเพศสัมพันธ์? หรือ มีเพศสัมพันธ์กับคนที่เป็นกามโรค?'],
                'B10_2'     => ['frame' => '10.2',    'type' => 'S', 'q' => 'มีแผลอักเสบที่ขา? หรือ ก้อนมีลักษณะกดเจ็บ?'],
                'B10_3'     => ['frame' => '10.3',    'type' => 'S', 'q' => 'ก้อนนุ่มไม่ปวด นูนมากเวลาไอ จาม หรือลุกยืน และยุบหายเวลานอน?'],
                'B11'       => ['frame' => '11',      'type' => 'S', 'q' => 'อัณฑะบวม?'],
                'B11_1'     => ['frame' => '11.1',    'type' => 'S', 'q' => 'เจ็บปวด? หรือ แดงร้อน?'],
                'B11_1_1'   => ['frame' => '11.1.1',  'type' => 'S', 'q' => 'ปวดรุนแรง เกิดขึ้นฉับพลัน? หรือ อัณฑะข้างที่ปวดยกสูงขึ้นกว่าข้างที่ปกติ?'],
                'B11_1_2'   => ['frame' => '11.1.2',  'type' => 'S', 'q' => 'หลังเป็นไข้หวัด? หรือ มีหนองไหลจากท่อปัสสาวะ?'],
                'B11_1_3'   => ['frame' => '11.1.3',  'type' => 'S', 'q' => 'พบก้อนหรือพร้อมกับคางทูม หรือหลังเป็นคางทูม 7-10 วัน?'],
                'B11_1_4'   => ['frame' => '11.1.4',  'type' => 'S', 'q' => 'มีประวัติสัมผัสสัตว์เลี้ยงหรือกินนมหรือเนื้อสัตว์ที่ไม่ผ่านกรรมวิธีทำให้ปลอดเชื้อ?'],
                'B11_2'     => ['frame' => '11.2',    'type' => 'S', 'q' => 'ก้อนนุ่ม?'],
                'B11_2_1'   => ['frame' => '11.2.1',  'type' => 'S', 'q' => 'ยุบหายเวลานอน?'],
                'B11_2_2'   => ['frame' => '11.2.2',  'type' => 'S', 'q' => 'เป็นก้อนโตมาตั้งแต่เล็ก และโปร่งแสงเวลาส่องไฟ?'],
                'B11_2_3'   => ['frame' => '11.2.3',  'type' => 'S', 'q' => 'ขรุขระและมีลักษณะยืดหยุ่นๆ แบบหลอดเลือดขอด?'],
                'B11_3'     => ['frame' => '11.3',    'type' => 'S', 'q' => 'ก้อนแข็ง? หรือ โตเร็ว?'],
                'B12'       => ['frame' => '12',      'type' => 'S', 'q' => 'มีลักษณะบวมแดงร้อน?'],
                'B12_1'     => ['frame' => '12.1',    'type' => 'S', 'q' => 'ขึ้นเป็นตุ่มฝี?'],
                'B12_2'     => ['frame' => '12.2',    'type' => 'S', 'q' => 'ขึ้นแผ่เป็นบริเวณกว้าง? หรือ ลุกลาม?'],
                'B13'       => ['frame' => '13',      'type' => 'S', 'q' => 'มีก้อนที่ใต้ผิวหนัง?'],
                'B13_1'     => ['frame' => '13.1',    'type' => 'S', 'q' => 'ก้อนโตเร็ว? แตกเป็นแผลมีเลือดออกง่าย? หรือ ก้อนยื่นออกมาจากกระดูก?'],
                'B13_2'     => ['frame' => '13.2',    'type' => 'S', 'q' => 'ก้อนนุ่ม โตช้า และไม่ปวด?'],
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
                    'min_required' => $box['type'] === 'M' ? 1 : null,
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
                'B1_1'      => [['fracture', null], [null, 'B1_2']],
                'B1_2'      => [['sprain', null], ['contusion', null]],
                'B2'        => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'      => [['dvt', null], ['lymphedema', null]],
                'B3'        => [['gnathostomiasis', null], [null, 'B4']],
                'B4'        => [['urticaria', null], [null, 'B5']],
                'B5'        => [[null, 'B5_1'], [null, 'B6']],
                'B5_1'      => [['diagram13_redirect', null], [null, 'B5_2']],
                'B5_2'      => [['blepharitis', null], [null, 'B5_3']],
                'B5_3'      => [['angioedema_eye', null], [null, 'B5_4']],
                'B5_4'      => [['hypothyroidism_eye', null], ['unexplained_eye_edema', null]],
                'B6'        => [[null, 'B6_1'], [null, 'B7']],
                'B6_1'      => [['gingivitis', null], [null, 'B6_2']],
                'B6_2'      => [['lip_allergy', null], ['unexplained_lip_edema', null]],
                'B7'        => [[null, 'B7_1'], [null, 'B8']],
                'B7_1'      => [['hyperthyroidism', null], [null, 'B7_2']],
                'B7_2'      => [['hypothyroidism', null], [null, 'B7_3']],
                'B7_3'      => [['thyroiditis', null], [null, 'B7_4']],
                'B7_4'      => [['thyroid_cancer', null], [null, 'B7_5']],
                'B7_5'      => [['physiological_goiter', null], [null, 'B7_6']],
                'B7_6'      => [['endemic_goiter', null], ['unexplained_goiter', null]],
                'B8'        => [[null, 'B8_1'], [null, 'B9']],
                'B8_1'      => [['breast_abscess', null], [null, 'B8_2']],
                'B8_2'      => [['breast_cancer', null], ['breast_mass_unexplained', null]],
                'B9'        => [[null, 'B9_1'], [null, 'B10']],
                'B9_1'      => [['lymph_node_systemic', null], [null, 'B9_2']],
                'B9_2'      => [['clavicle_axillary_mass', null], [null, 'B9_3']],
                'B9_3'      => [['lymphadenitis', null], ['lymph_node_observation', null]],
                'B10'       => [[null, 'B10_1'], [null, 'B11']],
                'B10_1'     => [['std_bubo', null], [null, 'B10_2']],
                'B10_2'     => [['groin_lymphadenitis', null], [null, 'B10_3']],
                'B10_3'     => [['inguinal_hernia', null], ['groin_mass_observation', null]],
                'B11'       => [[null, 'B11_1'], [null, 'B12']],
                'B11_1'     => [[null, 'B11_1_1'], [null, 'B11_2']],
                'B11_1_1'   => [['testicular_torsion', null], [null, 'B11_1_2']],
                'B11_1_2'   => [['gonococcal_orchitis', null], [null, 'B11_1_3']],
                'B11_1_3'   => [['mumps_orchitis', null], [null, 'B11_1_4']],
                'B11_1_4'   => [['brucellosis_orchitis', null], ['unexplained_orchitis', null]],
                'B11_2'     => [[null, 'B11_2_1'], [null, 'B11_3']],
                'B11_2_1'   => [['scrotal_hernia', null], [null, 'B11_2_2']],
                'B11_2_2'   => [['hydrocele', null], [null, 'B11_2_3']],
                'B11_2_3'   => [['varicocele', null], ['scrotal_mass_unexplained', null]],
                'B11_3'     => [['testicular_cancer', null], ['testicular_mass_observation', null]],
                'B12'       => [[null, 'B12_1'], [null, 'B13']],
                'B12_1'     => [['abscess', null], [null, 'B12_2']],
                'B12_2'     => [['cellulitis', null], ['abscess', null]],
                'B13'       => [[null, 'B13_1'], ['unexplained_skin_mass', null]],
                'B13_1'     => [['skin_bone_cancer', null], [null, 'B13_2']],
                'B13_2'     => [['lipoma_cyst', null], ['unexplained_skin_mass', null]],
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
• ปฐมพยาบาล
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
• ยาแก้ปวด (ย1)
• 48 ชั่วโมงแรกประคบด้วยน้ำเย็น
• หลัง 48 ชั่วโมงประคบด้วยน้ำอุ่นจัดๆ
NOTE,
                    'refs'       => ['113'],
                    'diagrams'   => [],
                ],
                'contusion' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ฟกช้ำ
• ยาแก้ปวด (ย1)
• 48 ชั่วโมงแรกประคบด้วยน้ำเย็น
• หลัง 48 ชั่วโมงประคบด้วยน้ำอุ่นจัดๆ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'dvt' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ภาวะหลอดเลือดดำส่วนลึกมีลิ่มเลือด (99.1)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['99.1'],
                    'diagrams'   => [],
                ],
                'lymphedema' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นทางเดินน้ำเหลืองอุดตันจากมะเร็ง การฉายรังสี โรคติดเชื้อ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'gnathostomiasis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคพยาธิตัวจี๊ด (236)
• ให้การรักษาตามอาการ
• อัลเบนดาโซล (ย6.3)
NOTE,
                    'refs'       => ['236'],
                    'diagrams'   => [],
                ],
                'urticaria' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ลมพิษ (198)
• ยาแก้แพ้ (ย7)
• ทายาแก้ผดผื่นคัน (ย25.5)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือหายใจลำบาก/เกิดหลังถูกผึ้งหรือต่อต่อย
NOTE,
                    'refs'       => ['198'],
                    'diagrams'   => [],
                ],
                'diagram13_redirect' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 13 บวมทั่วไป กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00013'],
                ],
                'blepharitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หนังตาอักเสบ (176.1)
• ประคบด้วยน้ำอุ่นจัดๆ
• ยาป้ายตาปฏิชีวนะ (ย25.9)
• ถ้าเป็นมาก กินไดคล็อกซาซิลลิน (ย4.3) หรืออิริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 7 วัน หรือเป็นๆ หายๆ บ่อย
NOTE,
                    'refs'       => ['176.1'],
                    'diagrams'   => [],
                ],
                'angioedema_eye' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
บวมจากการแพ้ (เช่น แพ้ยา อาหาร เครื่องสำอาง แมลงต่อย)
• หยุดใช้สิ่งที่แพ้
• ยาแก้แพ้ (ย7)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หายใจลำบาก หรือถูกผึ้ง/ต่อต่อย
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'hypothyroidism_eye' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ภาวะขาดไทรอยด์/ต่อมไทรอยด์ทำงานน้อย (124)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['124'],
                    'diagrams'   => [],
                ],
                'unexplained_eye_edema' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ถ้าไม่หายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'gingivitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เหงือกอักเสบ (61)
• ยาแก้ปวด (ย1)
• เพนิซิลลินวี (ย4.1) หรือ อิริโทรไมซิน (ย4.4) หรือ ดอกซีไซคลีน (ย4.5.1)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน
NOTE,
                    'refs'       => ['61'],
                    'diagrams'   => [],
                ],
                'lip_allergy' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
แพ้ยา/แพ้อาหาร/แมลง
• ยาแก้แพ้ (ย7)
• อย่ายากินหรืออาหารที่แพ้อีก
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หายใจลำบาก หรือถูกผึ้ง/ต่อต่อย
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'unexplained_lip_edema' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ถ้าไม่หายใน 1 สัปดาห์ เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'hyperthyroidism' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ภาวะต่อมไทรอยด์ทำงานเกิน/คอพอกเป็นพิษ (121)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['121'],
                    'diagrams'   => [],
                ],
                'hypothyroidism' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ภาวะขาดไทรอยด์/ต่อมไทรอยด์ทำงานน้อย (124)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['124'],
                    'diagrams'   => [],
                ],
                'thyroiditis' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
ต่อมไทรอยด์อักเสบ (122)
⊕ ภายใน 3 วัน
NOTE,
                    'refs'       => ['122'],
                    'diagrams'   => [],
                ],
                'thyroid_cancer' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
มะเร็งไทรอยด์ (123)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['123'],
                    'diagrams'   => [],
                ],
                'physiological_goiter' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
คอพอกสรีระ (120)
• ชันสูตรเพิ่มเติม
• ถ้าก้อนไม่โตมาก ไม่ต้องให้ยาอะไร ติดตามดูอาการ
⊕ ถ้าก้อนโตมากหรือเป็นก้อนเดี่ยวแข็ง/เสียงแหบ/กลืนลำบาก/หายใจลำบาก
NOTE,
                    'refs'       => ['120'],
                    'diagrams'   => [],
                ],
                'endemic_goiter' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
คอพอกประจำถิ่น (120)
• ชันสูตรเพิ่มเติม
• ให้เกลือไอโอดีน
⊕ ถ้าก้อนโตมาก หรือเสียงแหบ/กลืนลำบาก/หายใจลำบาก
NOTE,
                    'refs'       => ['120'],
                    'diagrams'   => [],
                ],
                'unexplained_goiter' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1-2 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1-2 สัปดาห์ เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'breast_abscess' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ฝีเต้านม (193)
• ประคบด้วยน้ำอุ่นจัดๆ
• ยาแก้ปวด (ย1)
• ไดคล็อกซาซิลลิน (ย4.3) หรืออิริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือเป็นเบาหวาน
NOTE,
                    'refs'       => ['193'],
                    'diagrams'   => [],
                ],
                'breast_cancer' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งเต้านม (237.2)
NOTE,
                    'refs'       => ['237.2'],
                    'diagrams'   => [],
                ],
                'breast_mass_unexplained' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นเนื้องอกเต้านม หรือมะเร็งเต้านม (237.2)
NOTE,
                    'refs'       => ['237.2'],
                    'diagrams'   => [],
                ],
                'lymph_node_systemic' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งต่อมน้ำเหลือง (106.1)/มะเร็งเม็ดเลือดขาว (106)/เอดส์ (238)/บรูเซลโลซิส (229.4)/อื่นๆ
NOTE,
                    'refs'       => ['106.1', '106', '238', '229.4'],
                    'diagrams'   => [],
                ],
                'clavicle_axillary_mass' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็ง (237)/สาเหตุอื่นๆ
NOTE,
                    'refs'       => ['237'],
                    'diagrams'   => [],
                ],
                'lymphadenitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ต่อมน้ำเหลืองอักเสบ (194)
• ยาแก้ปวด (ย1)
• เพนิซิลลินวี (ย4.1) หรือไดคล็อกซาซิลลิน (ย4.3) หรืออิริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['194'],
                    'diagrams'   => [],
                ],
                'lymph_node_observation' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• รักษาตามอาการ
⊕ ถ้าก้อนโตกว่า 1 ซม./ก้อนโตขึ้น/มีไข้เกิน 1 สัปดาห์/น้ำหนักลดฮวบ/ซีด/มีจ้ำเขียวขึ้น
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'std_bubo' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นแผลริมอ่อน (210)/ซิฟิลิส (211)/ฝีมะม่วง (212)
NOTE,
                    'refs'       => ['210', '211', '212'],
                    'diagrams'   => [],
                ],
                'groin_lymphadenitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ต่อมน้ำเหลืองอักเสบ (194)
• ประคบด้วยน้ำอุ่นจัดๆ
• ยาแก้ปวด (ย1)
• เพนิซิลลินวี (ย4.1) หรือ ไดคล็อกซาซิลลิน (ย4.3) หรืออิริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['194'],
                    'diagrams'   => [],
                ],
                'inguinal_hernia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไส้เลื่อน (57)
• แนะนำไปตรวจที่โรงพยาบาลเมื่อมีโอกาส
⊕ ด่วน ถ้าปวดท้องรุนแรง/อาเจียนรุนแรง/กลายเป็นก้อนแข็งไม่ยุบและกดเจ็บ
NOTE,
                    'refs'       => ['57'],
                    'diagrams'   => [],
                ],
                'groin_mass_observation' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• รักษาตามอาการ
⊕ ถ้าก้อนโตกว่า 1 ซม./ก้อนโตขึ้น/มีไข้เกิน 1 สัปดาห์/น้ำหนักลดฮวบ/ซีด/มีจ้ำเขียวขึ้น/สงสัยเป็นมะเร็งต่อมน้ำเหลือง (106.1)
⊕ ด่วน ถ้าสงสัยเป็นไส้เลื่อน (57) ชนิดติดค้าง
NOTE,
                    'refs'       => ['106.1', '57'],
                    'diagrams'   => [],
                ],
                'testicular_torsion' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
อัณฑะบิดตัว (146.1)
⊕ ด่วน
NOTE,
                    'refs'       => ['146.1'],
                    'diagrams'   => [],
                ],
                'gonococcal_orchitis' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
อัณฑะอักเสบจากหนองใน (208)
⊕ ภายใน 3 วัน
NOTE,
                    'refs'       => ['208'],
                    'diagrams'   => [],
                ],
                'mumps_orchitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อัณฑะอักเสบจากคางทูม (7)
• รักษาตามอาการ
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือปวดรุนแรง
NOTE,
                    'refs'       => ['7'],
                    'diagrams'   => [],
                ],
                'brucellosis_orchitis' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
บรูเซลโลซิส (229.4)
⊕ ภายใน 3 วัน
NOTE,
                    'refs'       => ['229.4'],
                    'diagrams'   => [],
                ],
                'unexplained_orchitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ปวด (ย1)
• ประคบด้วยน้ำอุ่นจัดๆ
• ไดคล็อกซาซิลลิน (ย4.3) หรืออิริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือปวดรุนแรง/สงสัยอัณฑะบิดตัว
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'scrotal_hernia' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 เดือน',
                    'note'       => <<<NOTE
ไส้เลื่อน (57)
⊕ ภายใน 1 เดือน
⊕ ด่วน ถ้าปวดท้องรุนแรง/อาเจียนรุนแรง/กลายเป็นก้อนแข็งไม่ยุบและกดเจ็บ
NOTE,
                    'refs'       => ['57'],
                    'diagrams'   => [],
                ],
                'hydrocele' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ถุงน้ำที่ถุงอัณฑะ (145)
⊕ ถ้าก้อนโตมาก
NOTE,
                    'refs'       => ['145'],
                    'diagrams'   => [],
                ],
                'varicocele' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หลอดเลือดอัณฑะขอด (146)
• ไม่มีอันตรายอะไร
• ถ้าเป็นที่ข้างซ้ายและไม่มีอาการอะไรก็ไม่ต้องรักษาแต่อย่างใด
⊕ ถ้ามีอาการเจ็บปวดหรือเป็นที่อัณฑะข้างขวา
NOTE,
                    'refs'       => ['146'],
                    'diagrams'   => [],
                ],
                'scrotal_mass_unexplained' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ประเมินก้อนที่อัณฑะเพิ่มเติม (ต่อกรอบ 11.3)
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'testicular_cancer' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งอัณฑะ (237.17)/อื่นๆ
NOTE,
                    'refs'       => ['237.17'],
                    'diagrams'   => [],
                ],
                'testicular_mass_observation' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1-2 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1-2 สัปดาห์ เพื่อตรวจหาสาเหตุ
⊕ ด่วน ถ้าสงสัยเป็นไส้เลื่อน (57) ชนิดติดค้าง/อัณฑะบิดตัว (146.1)
NOTE,
                    'refs'       => ['57', '146.1'],
                    'diagrams'   => [],
                ],
                'abscess' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ฝี (192.1)
NOTE,
                    'refs'       => ['192.1'],
                    'diagrams'   => [],
                ],
                'cellulitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เนื้อเยื่อใต้ผิวหนังชั้นลึกอักเสบ (192.4)/ไฟลามทุ่ง (192.5)
• ประคบด้วยน้ำอุ่นจัดๆ
• ยาแก้ปวดลดไข้ (ย1)
• ไดคล็อกซาซิลลิน (ย4.3) หรืออิริโทรไมซิน (ย4.4)
⊕ ถ้าไม่ดีขึ้นใน 3 วัน หรือ เป็นเบาหวาน (117)/สงสัยเป็นเมลิออยโดซิส (229.2)/เป็นหลังกินหอยนางรมดิบหรือเล่นน้ำทะเล
NOTE,
                    'refs'       => ['192.4', '192.5', '117', '229.2'],
                    'diagrams'   => [],
                ],
                'skin_bone_cancer' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นมะเร็งผิวหนัง (237.1)/มะเร็งกระดูก (237.19)
NOTE,
                    'refs'       => ['237.1', '237.19'],
                    'diagrams'   => [],
                ],
                'lipoma_cyst' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ก้อนไขมัน/ถุงน้ำ (ซีสต์)
• ไม่มีอันตราย
• ถ้าก้อนเล็กไม่ต้องทำอะไร
• ถ้าก้อนโตหรือเจ็บปวดให้ผ่าตัดออก
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'unexplained_skin_mass' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1-2 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1-2 สัปดาห์ ถ้ามีก้อนบวม
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
                    'medical_reference' => 'แผนภูมิที่ 14',
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

        $this->command->info('สร้างแผนภูมิที่ 14 (บวมเฉพาะที่/มีก้อน - LOCAL SWELLING/MASS) สำเร็จ');
    }
}
