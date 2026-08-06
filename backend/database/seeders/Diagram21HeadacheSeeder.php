<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram21HeadacheSeeder extends Seeder
{
    private const DIAGRAM_ID = '00021';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '2.1', '2.2', '2.3', '2.4', '2.4.1',
                '3', '3.1', '4', '4.1', '4.2', '4.3',
                '5', '6', '6.1', '7', '8', '8.1', '8.2',
                '9', '10', '11'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 21 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 21
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ปวดศีรษะ (HEADACHE)',
                'diagram_name_en' => 'Headache',
                'description' => 'มีอาการปวดตึง ปวดตื้อ ปวดจี๊ด หรือปวดตุบๆ บริเวณใดบริเวณหนึ่งของศีรษะ รอบตา หรือโคนหนา สาเหตุที่พบบ่อย ปวดศีรษะจากความเครียด (72) ไมเกรน (71) ไซนัสอักเสบ (26) หวัดภูมิแพ้ (25) สายตาผิดปกติ (178) ความดันโลหิตสูง (92) ถ้าอาการไม่ชัดเจน ให้การดูแลรักษาดังกรอบที่ 11 ถ้ามีอาการปวดตาร่วมด้วย ดูแผนภูมิที่ 23 ประกอบ',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 21
            $boxes = [
                'B1'     => ['frame' => '1',     'type' => 'S', 'q' => 'มีไข้? ปวดหู? คัดจมูก/น้ำมูกไหล? หรือ ปวดฟัน?'],
                'B2'     => ['frame' => '2',     'type' => 'S', 'q' => 'มีอาการเพียงอย่างใดอย่างหนึ่งดังต่อไปนี้: ปวดรุนแรง? เห็นภาพซ้อน? ตาพร่ามัวลงเรื่อยๆ ติดต่อกัน (เป็นวันหรือเป็นสัปดาห์)? เดินเซหรือแขนขาอ่อนแรง? อาเจียนรุนแรง?'],
                'B2_1'   => ['frame' => '2.1',   'type' => 'S', 'q' => 'มีประวัติได้รับบาดเจ็บที่ศีรษะภายใน 7 วันที่ผ่านมา?'],
                'B2_2'   => ['frame' => '2.2',   'type' => 'S', 'q' => 'มีประวัติเป็นหูหนวก ไซนัสอักเสบ หรือไข้เรื้อรัง?'],
                'B2_3'   => ['frame' => '2.3',   'type' => 'S', 'q' => 'คอแข็ง?'],
                'B2_4'   => ['frame' => '2.4',   'type' => 'S', 'q' => 'แขนขาแข็งแรง? และ รู้สึกตัวดี?'],
                'B2_4_1' => ['frame' => '2.4.1', 'type' => 'S', 'q' => 'ตาข้างหนึ่งปวดรุนแรง และมีอาการตาพร่ามัว ตาแดง เรื่อๆ รูม่านตาโตกว่าข้างที่ไม่ปวด?'],
                'B3'     => ['frame' => '3',     'type' => 'S', 'q' => 'ความดันโลหิตช่วงบน >= 140 หรือช่วงล่าง >= 90 มม.ปรอท?'],
                'B3_1'   => ['frame' => '3.1',   'type' => 'S', 'q' => 'พบในหญิงตั้งครรภ์ และมีอาการบวมร่วมด้วย?'],
                'B4'     => ['frame' => '4',     'type' => 'S', 'q' => 'ปวดศีรษะ/ใบหน้าข้างเดียว?'],
                'B4_1'   => ['frame' => '4.1',   'type' => 'S', 'q' => 'ปวดตาข้างหนึ่งอย่างต่อเนื่อง และมีอาการตาพร่ามัว ตาแดงเรื่อๆ รูม่านตาโตกว่าข้างที่ไม่ปวด?'],
                'B4_2'   => ['frame' => '4.2',   'type' => 'S', 'q' => 'ปวดตาและใบหน้าซีกหนึ่ง แต่ละครั้งนาน 15 นาที ถึง 1 ชั่วโมง? และตาข้างที่ปวดมีอาการตาแดง หนังตาตก น้ำตาไหล และรูม่านตาแคบ?'],
                'B4_3'   => ['frame' => '4.3',   'type' => 'S', 'q' => 'ปวดตุบๆ ที่ขมับข้างเดียว แต่ละครั้งปวดนาน 4-72 ชั่วโมง และมีสาเหตุกระตุ้น?'],
                'B5'     => ['frame' => '5',     'type' => 'S', 'q' => 'ปวดขมับหรือขมับข้างเดียวหรือ 2 ข้าง? หรือ แต่ละครั้งปวดนาน 4-72 ชั่วโมง และมีสาเหตุกระตุ้น?'],
                'B6'     => ['frame' => '6',     'type' => 'S', 'q' => 'ปวดหลังจากอ่านหนังสือ หรือใช้สายตามาก?'],
                'B6_1'   => ['frame' => '6.1',   'type' => 'S', 'q' => 'สายตาไม่ดี?'],
                'B7'     => ['frame' => '7',     'type' => 'S', 'q' => 'เป็นหลังจากดื่มแอลกอฮอล์จัด?'],
                'B8'     => ['frame' => '8',     'type' => 'S', 'q' => 'ปวดที่ต้นคอหรือท้ายทอย?'],
                'B8_1'   => ['frame' => '8.1',   'type' => 'S', 'q' => 'ปวดร้าวเสียวแปล๊บๆ หรือรู้สึกชาลงมาที่แขน?'],
                'B8_2'   => ['frame' => '8.2',   'type' => 'S', 'q' => 'ปวดกดในข้อกระดูกคอ?'],
                'B9'     => ['frame' => '9',     'type' => 'S', 'q' => 'ปวดหลังตื่นนอน กลางคืนนอนกรนมาก และกลางวันง่วงนอนง่าย?'],
                'B10'    => ['frame' => '10',    'type' => 'S', 'q' => 'คิดมาก? นอนไม่หลับ? มีอารมณ์ซึมเศร้า? หรือ คร่ำเคร่งกับงาน?'],
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
                'B1'     => [['fever_ear_nasal_tooth_pain', null], [null, 'B2']],
                'B2'     => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'   => [['head_injury', null], [null, 'B2_2']],
                'B2_2'   => [['brain_abscess', null], [null, 'B2_3']],
                'B2_3'   => [['meningitis', null], [null, 'B2_4']],
                'B2_4'   => [[null, 'B2_4_1'], ['other_severe_headache_causes', null]],
                'B2_4_1' => [['acute_glaucoma_2_4_1', null], ['other_severe_headache_causes', null]],
                'B3'     => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'   => [['preeclampsia', null], ['hypertension', null]],
                'B4'     => [[null, 'B4_1'], [null, 'B5']],
                'B4_1'   => [['acute_glaucoma_4_1', null], [null, 'B4_2']],
                'B4_2'   => [['cluster_headache', null], [null, 'B4_3']],
                'B4_3'   => [['migraine_4_3', null], ['herpes_zoster_or_others', null]],
                'B5'     => [['migraine_5', null], [null, 'B6']],
                'B6'     => [[null, 'B6_1'], [null, 'B7']],
                'B6_1'   => [['refractive_error', null], ['eyestrain_from_reading', null]],
                'B7'     => [['hangover_headache', null], [null, 'B8']],
                'B8'     => [[null, 'B8_1'], [null, 'B9']],
                'B8_1'   => [['cervical_herniated_disc', null], [null, 'B8_2']],
                'B8_2'   => [['cervical_spondylosis', null], [null, 'B9']],
                'B9'     => [['sleep_apnea', null], [null, 'B10']],
                'B10'    => [['tension_anxiety_depression_headache', null], ['headache_care_frame11', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'fever_ear_nasal_tooth_pain' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 1 ไข้ กรอบที่ 1
26 ปวดหู กรอบที่ 1
30 คัดจมูก/น้ำมูกไหล กรอบที่ 1
32 โรคฟัน กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00001', '00026', '00030', '00032'],
                ],
                'head_injury' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ศีรษะได้รับบาดเจ็บ/เลือดออกในสมอง (81)
⊕ ด่วน
NOTE,
                    'refs'       => ['81'],
                    'diagrams'   => [],
                ],
                'brain_abscess' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ฝีสมอง (82)
⊕ ด่วน
NOTE,
                    'refs'       => ['82'],
                    'diagrams'   => [],
                ],
                'meningitis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
เยื่อหุ้มสมองอักเสบ (66)
⊕ ด่วน
NOTE,
                    'refs'       => ['66'],
                    'diagrams'   => [],
                ],
                'acute_glaucoma_2_4_1' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ต้อหินชนิดเฉียบพลัน (181)
⊕ ด่วน
NOTE,
                    'refs'       => ['181'],
                    'diagrams'   => [],
                ],
                'other_severe_headache_causes' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุอื่นๆ เช่น เนื้องอกสมอง (83)/หลอดเลือดสมองแตก (76)/เลือดออกในสมอง (81) เป็นต้น
NOTE,
                    'refs'       => ['83', '76', '81'],
                    'diagrams'   => [],
                ],
                'preeclampsia' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
ครรภ์เป็นพิษ (155)
⊕ ภายใน 3 วัน
NOTE,
                    'refs'       => ['155'],
                    'diagrams'   => [],
                ],
                'hypertension' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ความดันโลหิตสูง (92)
• ชันสูตรเพิ่มเติม
• ยาลดความดัน (ย22)
⊕ ถ้าควบคุมความดันไม่ได้/มีภาวะแทรกซ้อน/สงสัยเป็นความดันโลหิตสูงชนิดทุติยภูมิ
NOTE,
                    'refs'       => ['92'],
                    'diagrams'   => [],
                ],
                'acute_glaucoma_4_1' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ต้อหินเฉียบพลัน (181)
⊕ ด่วน
NOTE,
                    'refs'       => ['181'],
                    'diagrams'   => [],
                ],
                'cluster_headache' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปวดศีรษะคลัสเตอร์ (71.1)
• ฉีดไดไฮโดรเออร์โกตามีนเข้ากล้าม
• กินยาป้องกัน
⊕ ถ้าปวดรุนแรง หรือพบว่าปวดเป็นครั้งแรก
NOTE,
                    'refs'       => ['71.1'],
                    'diagrams'   => [],
                ],
                'migraine_4_3' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไมเกรน (71)
• ยาแก้ปวด (ย1)
• นอนพัก
• พยายามหลีกเลี่ยงสิ่งกระตุ้นให้ปวด
⊕ ถ้าเป็นการปวดครั้งแรกในคนอายุมากกว่า 40 ปี/ความดันโลหิตสูง/ปวดนายนเกิน 72 ชั่วโมง/ปวดแรงหรือถี่ขึ้นกว่าเดิม
NOTE,
                    'refs'       => ['71'],
                    'diagrams'   => [],
                ],
                'herpes_zoster_or_others' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ อาจเป็นงูสวัด (188) ระยะแรก หรืออื่นๆ
• ยาแก้ปวด (ย1)
• ถ้าต่อมาพบว่ามีตุ่มใสขึ้นเป็นแนวยาวที่ใบหน้าให้การรักษาแบบงูสวัด (188)
⊕ ถ้าปวดรุนแรง/เป็นการปวดครั้งแรกในคนอายุมากกว่า 40 ปี/ความดันโลหิตสูง/ปวดตลอดเวลานานเกิน 72 ชั่วโมง/เป็นๆ หายๆ บ่อย (ดูหัวข้อ "ข้อแนะนำ" ใน "โรคที่ 71" เพิ่มเติม)
NOTE,
                    'refs'       => ['188'],
                    'diagrams'   => [],
                ],
                'migraine_5' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไมเกรน (71)
• ยาแก้ปวด (ย1)
• นอนพัก
• พยายามหลีกเลี่ยงสิ่งกระตุ้นให้ปวด
⊕ ถ้าเป็นการปวดครั้งแรกในคนอายุมากกว่า 40 ปี/ความดันโลหิตสูง/ปวดนานเกิน 72 ชั่วโมง/ปวดแรงหรือถี่ขึ้นกว่าเดิม
NOTE,
                    'refs'       => ['71'],
                    'diagrams'   => [],
                ],
                'refractive_error' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สายตาผิดปกติ (178)
• ยาแก้ปวด (ย1)
• แนะนำไปตรวจวัดสายตา
NOTE,
                    'refs'       => ['178'],
                    'diagrams'   => [],
                ],
                'eyestrain_from_reading' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ปวด (ย1)
• พักสายตาเวลาดูหนังสือ หรือเวลาใช้สายตามากไป
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'hangover_headache' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปวดจากฤทธิ์แอลกอฮอล์ (hangover) (มักเป็นตอนตื่นนอนในเช้าวันรุ่งขึ้น)
• ยาแก้ปวด (ย1)
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'cervical_herniated_disc' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
หมอนรองกระดูกสันหลังส่วนคอเคลื่อน (108)/กระดูกคอซอกกดรากประสาท (108.1)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['108', '108.1'],
                    'diagrams'   => [],
                ],
                'cervical_spondylosis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กระดูกคอเสื่อม (108.1)
• ยาต้านอักเสบที่ไม่ใช่สเตียรอยด์ (ย2)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['108.1'],
                    'diagrams'   => [],
                ],
                'sleep_apnea' => [
                    'urgency'    => 'M',
                    'time_frame' => 'ภายใน 1 เดือน',
                    'note'       => <<<NOTE
ภาวะหยุดหายใจขณะหลับ (31.1)
⊕ ภายใน 1 เดือน/ความดันโลหิตสูง
NOTE,
                    'refs'       => ['31.1'],
                    'diagrams'   => [],
                ],
                'tension_anxiety_depression_headache' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ปวดศีรษะจากความเครียด (72)/โรควิตกกังวล/โรควิตกกังวลทั่วไป (88)/โรคอารมณ์แปรปรวน/โรคซึมเศร้า (88.2)
• ยาแก้ปวด (ย1)
• ยาทางจิตประสาท (ย17)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['72', '88', '88.2'],
                    'diagrams'   => [],
                ],
                'headache_care_frame11' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาแก้ปวด (ย1)
• ไดอะซีแพม (ย17.1) ถ้าเครียด
• นวดต้นคอ หรือขมับ หรือทานวดด้วยยาหม่อง
• นอนพักในห้องมืดๆ เงียบๆ
• หลีกเลี่ยงสาเหตุที่ทำให้ปวด เช่น การดื่มแอลกอฮอล์ หิวเกินไป เหนื่อยเกินไป นอนไม่พอ เป็นต้น
⊕ ถ้ามีลักษณะอย่างใดอย่างหนึ่งดังต่อไปนี้:
  1. ปวดรุนแรง (กินยาไม่ทุเลา) หรืออาเจียนรุนแรงร่วมด้วย
  2. กินยาแล้วหายปวดชั่วคราว แต่กลับปวดแรงและถี่ขึ้นทุกวันนานกว่า 7 วัน
  3. ปวดมากตอนดึกหรือเช้ามืดจนทำให้สะดุ้งตื่น
  4. ปวดศีรษะข้างเดียวเป็นครั้งแรกในคนอายุมากกว่า 40 ปี
  5. เป็นๆ หายๆ โดยไม่ทราบสาเหตุแน่ชัดนานเกิน 2 สัปดาห์
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
                    'medical_reference' => 'แผนภูมิที่ 21',
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

        $this->command->info('สร้างแผนภูมิที่ 21 (ปวดศีรษะ - HEADACHE) กรอบ 1-11 สำเร็จ');
    }
}
