<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram22DizzinessSeeder extends Seeder
{
    private const DIAGRAM_ID = '00022';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            // กรอบทั้งหมดในแผนภูมิที่ 22
            $frameNumbers = [
                '1', '2', '3', '3.1', '4', '5', '6', '7', '7.1', '7.1.1', '7.1.2',
                '7.2', '7.2.1', '7.2.2', '7.2.3', '7.2.4', '7.3', '7.4', '7.5',
                '7.6', '7.6.1', '7.6.2', '7.6.3', '7.7', '7.8', '8', '9', '10',
                '11', '12', '13', '14', '15', '16', '17', '18'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 22 ถูก seed แล้ว กรุณารัน Rollback หรือลบข้อมูลเดิมก่อน'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 22
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'เวียนศีรษะ (DIZZINESS)/บ้านหมุน (VERTIGO)',
                'diagram_name_en' => 'Dizziness / Vertigo',
                'description' => 'อาการเวียน หัวหมุน หรือโครงเคลง หน้ามืดคล้ายจะเป็นลม หรือรู้สึกว่าบ้านหมุนหรือสิ่งรอบข้างหมุน อาจมีอาการพะอืดพะอม คลื่นไส้ อาเจียนร่วมด้วย สาเหตุที่พบบ่อย: บ้านหมุนจากการเปลี่ยนท่า (164.1) เมารถ เมาเรือ สาเหตุจากยา ความดันตกในท่ายืน (93) ไมเกรน (71) ซีด ร่างกายอ่อนเพลีย นอนไม่พอ โรควิตกกังวล/โรคกังวลทั่วไป (88) ถ้าอาการไม่ชัดเจน พักผ่อนให้เพียงพอ ออกกำลังกายเป็นประจำ หลีกเลี่ยงท่าที่ทำให้เกิดอาการ ถ้าคลื่นไส้ อาเจียน ให้ยาแก้อาเจียน (ย19) ถ้าคิดมากหรือเครียด ให้ยาไดอะซีแพม (ย17.1)',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 22
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'มีอาการเป็นลม หรือหมดความรู้สึกร่วมด้วย?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'มีภาวะช็อก (เหงื่อออก ตัวเย็น ชีพจรเร็ว ความดันตก)?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'ลุกนัั่งจะเป็นลม และชีพจรมากกว่า 100 ครั้ง/นาที?'],
                'B3_1'    => ['frame' => '3.1',   'type' => 'S', 'q' => 'ถ่ายดำ? ปวดท้อง? ท้องเดินรุนแรง? หรือ เจ็บหน้าอกรุนแรง?'],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => 'มีไข้? ซีด? หรือ ปวดศีรษะ?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => 'คลื่นไส้ อาเจียนมากจนกินไม่ได้ หรือ เกิดภาวะขาดน้ำ?'],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => 'เห็นภาพซ้อน? พูดไม่ชัด? กลืนลำบาก? หรือ แขนขาชาหรืออ่อนแรง?'],
                'B7'      => ['frame' => '7',     'type' => 'S', 'q' => 'เห็นบ้านหมุนหรือสิ่งรอบข้างหมุน?'],
                'B7_1'    => ['frame' => '7.1',   'type' => 'S', 'q' => 'เดินเซ?'],
                'B7_1_1'  => ['frame' => '7.1.1', 'type' => 'S', 'q' => 'หูตึงข้างหนึ่ง? หรือ ใบหน้าชาหรือเป็นอัมพาตซีกหนึ่ง?'],
                'B7_1_2'  => ['frame' => '7.1.2', 'type' => 'S', 'q' => 'กินยารักษาโรคคลั่งชัก เฟนิโทอิน (ย18.2)?'],
                'B7_2'    => ['frame' => '7.2',   'type' => 'S', 'q' => 'หูตึงหรือไม่ได้ยินชัด? หรือ มีเสียงดังรบกวนในหู?'],
                'B7_2_1'  => ['frame' => '7.2.1', 'type' => 'S', 'q' => 'มีไข้ ปวดหู แก้วหูแดง หรือ มีหนองไหล?'],
                'B7_2_2'  => ['frame' => '7.2.2', 'type' => 'S', 'q' => 'บ้านหมุนอย่างรุนแรงนานหลายวัน? หรือ พบหลังเป็นไข้หวัดหรือไข้หวัดใหญ่?'],
                'B7_2_3'  => ['frame' => '7.2.3', 'type' => 'S', 'q' => 'บ้านหมุนนานเป็นชั่วโมงๆ? หรือ เป็นๆ หายๆ เป็นครั้งคราว?'],
                'B7_3'    => ['frame' => '7.3',   'type' => 'S', 'q' => 'บ้านหมุนอย่างรุนแรงนานหลายวัน? หรือ พบหลังเป็นไข้หวัดหรือไข้หวัดใหญ่?'],
                'B7_4'    => ['frame' => '7.4',   'type' => 'S', 'q' => 'ได้รับบาดเจ็บที่ศีรษะหรือต้นคอ?'],
                'B7_5'    => ['frame' => '7.5',   'type' => 'S', 'q' => 'มีอาการขณะนั่งรถ/เรือ/เครื่องบิน/เครื่องเล่น?'],
                'B7_6'    => ['frame' => '7.6',   'type' => 'S', 'q' => 'มีอาการบ้านหมุนเพียง 20-30 วินาที (ไม่เกิน 1 นาที)?'],
                'B7_6_1'  => ['frame' => '7.6.1', 'type' => 'S', 'q' => 'มีอาการปวดต้นคอหรือท้ายทอย และปวดเสียวหรือชาร้าวลงแขน?'],
                'B7_6_2'  => ['frame' => '7.6.2', 'type' => 'S', 'q' => 'มีอาการบ้านหมุนทันทีเฉพาะเวลาเปลี่ยนท่าบางท่า (เช่น นอนตะแคงข้าง ลุกจากเตียง ก้มศีรษะ)?'],
                'B7_7'    => ['frame' => '7.7',   'type' => 'S', 'q' => 'เมื่อบ้านหมุนไม่มากแล้วมีอาการปวดตา/ขมับ/ตาพร่า? มีสาเหตุกระตุ้น? เคยเป็นไมเกรน? มีอาการเกิดขึ้นขณะยานพาหนะ/คุณภาพเคลื่อนไหว/อยู่ในฝูงชน? หรือ กินยารักษาไมเกรนแล้วทุเลา?'],
                'B7_8'    => ['frame' => '7.8',   'type' => 'S', 'q' => 'มีประวัติกินยา ฉีดยา หรือดื่มแอลกอฮอล์?'],
                'B8'      => ['frame' => '8',     'type' => 'S', 'q' => 'ความดันช่วงบน >= 140 หรือช่วงล่าง >= 90 มม.ปรอท?'],
                'B9'      => ['frame' => '9',     'type' => 'S', 'q' => 'ความดันช่วงบนในท่ายืนต่ำกว่าท่านอน >= 20 มม.ปรอท หรือ ความดันช่วงล่างในท่ายืนต่ำกว่าท่านอน >= 10 มม.ปรอท หรือพบร่วมกันทั้ง 2 อย่าง? รวมดันช่วงบนในท่ายืน <= 90 มม.ปรอท? หรือ ลุกยืนขึ้นมีอาการหน้ามืดเป็นลมและลดลงทันทีเมื่อนอนลง?'],
                'B10'     => ['frame' => '10',    'type' => 'S', 'q' => 'ปวดแน่นลิ้นปี่และร้าวไปที่คอ/กราม/กรับ/คาง หรือแขน? ชีพจรเต้นช้าหรือเร็วกว่าปกติ หรือเต้นไม่สม่ำเสมอ? เท้าบวม? หรือ ฟังหัวใจมีเสียงฟู่ (murmur)?'],
                'B11'     => ['frame' => '11',    'type' => 'S', 'q' => 'พบในหญิงวัยเจริญพันธุ์ที่แต่งงานแล้วและประจำเดือนขาด? หรือ สงสัยตั้งครรภ์?'],
                'B12'     => ['frame' => '12',    'type' => 'S', 'q' => 'พบในผู้สูงอายุ มีอาการรู้สึกโคลงเคลงเฉพาะเวลาเดินหรือหันตัวกลับ?'],
                'B13'     => ['frame' => '13',    'type' => 'S', 'q' => 'เป็นหลังฉีดยาหรือกินยา ดื่มแอลกอฮอล์ หรือสูบบุหรี่?'],
                'B14'     => ['frame' => '14',    'type' => 'S', 'q' => 'อดอาหาร? หรือ มีประวัติกินหรือฉีดยารักษาเบาหวาน?'],
                'B15'     => ['frame' => '15',    'type' => 'S', 'q' => 'นอนไม่พอ อ่อนเพลีย เหนื่อยล้า หิวข้าว หรืออยู่ในที่แออัดอากาศไม่พอหายใจ?'],
                'B16'     => ['frame' => '16',    'type' => 'S', 'q' => 'คิดมากกังวลใจ? ซึมเศร้า ท้อแท้ เบื่อหน่าย? ตื่นตระหนก หรือ มีความรู้สึกกลัวรุนแรง?'],
                'B17'     => ['frame' => '17',    'type' => 'S', 'q' => 'เวียนศีรษะ หน้ามืดชั่วขณะเป็นเฉพาะเวลาลุกขึ้นเร็วๆ?'],
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

            // Entry Point
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'entry_box_id' => $boxes['B1']['id'],
            ]);

            // 3. ตัวเลือก (Choices) และเส้นทางเชื่อมต่อ
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

            // โครงสร้างการตัดสินใจแบบ Binary Tree
            $binary = [
                'B1'      => [['fainting_d15', null], [null, 'B2']],
                'B2'      => [['shock', null], [null, 'B3']],
                'B3'      => [[null, 'B3_1'], [null, 'B4']],
                'B3_1'    => [['severe_cause_3_1', null], ['severe_cause_3_1_no', null]],
                'B4'      => [['fever_anemia_headache', null], [null, 'B5']],
                'B5'      => [['severe_dehydration', null], [null, 'B6']],
                'B6'      => [['stroke', null], [null, 'B7']],
                'B7'      => [[null, 'B7_1'], [null, 'B8']],
                'B7_1'    => [[null, 'B7_1_1'], [null, 'B7_2']],
                'B7_1_1'  => [['acoustic_neuroma', null], [null, 'B7_1_2']],
                'B7_1_2'  => [['phenytoin_toxicity', null], ['brain_tumor_syphilis_stroke', null]],
                'B7_2'    => [[null, 'B7_2_1'], [null, 'B7_3']],
                'B7_2_1'  => [['otitis_media_acute', null], [null, 'B7_2_2']],
                'B7_2_2'  => [['labyrinthitis_acute', null], [null, 'B7_2_3']],
                'B7_2_3'  => [['meniere_or_migraine', null], ['symptomatic_vertigo_7_2_4', null]],
                'B7_3'    => [['vestibular_neuritis', null], [null, 'B7_4']],
                'B7_4'    => [['head_neck_injury', null], [null, 'B7_5']],
                'B7_5'    => [['motion_sickness', null], [null, 'B7_6']],
                'B7_6'    => [[null, 'B7_6_1'], [null, 'B7_7']],
                'B7_6_1'  => [['cervical_nerve_compression', null], [null, 'B7_6_2']],
                'B7_6_2'  => [['bppv', null], ['symptomatic_vertigo_7_6_3', null]],
                'B7_7'    => [['migraine_7_7', null], [null, 'B7_8']],
                'B7_8'    => [['drug_alcohol_cause', null], ['symptomatic_vertigo_7_8', null]],
                'B8'      => [['hypertension', null], [null, 'B9']],
                'B9'      => [['orthostatic_hypotension', null], [null, 'B10']],
                'B10'     => [['heart_disease', null], [null, 'B11']],
                'B11'     => [['pregnancy', null], [null, 'B12']],
                'B12'     => [['elderly_disequilibrium', null], [null, 'B13']],
                'B13'     => [['drug_alcohol_smoking', null], [null, 'B14']],
                'B14'     => [['hypoglycemia', null], [null, 'B15']],
                'B15'     => [['lifestyle_causes', null], [null, 'B16']],
                'B16'     => [['anxiety_panic_depression', null], [null, 'B17']],
                'B17'     => [['postural_cerebral_ischemia', null], ['symptomatic_dizziness_18', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กฎการวินิจฉัย การรักษา คำแนะนำ และระดับความรุนแรง
            $rules = [
                'fainting_d15' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 15 เป็นลม กรอบที่ 3
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00015'],
                ],
                'shock' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ช็อก (31)
⊕ ด่วน พร้อมกับให้น้ำเกลือไประหว่างทาง
NOTE,
                    'refs'       => ['31'],
                    'diagrams'   => [],
                ],
                'severe_cause_3_1' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน (ดู "โรคที่ 32, 34, 35, 47, 48, 50, 51, 52, 54, 56, 91, 96, 157")
NOTE,
                    'refs'       => ['32', '34', '35', '47', '48', '50', '51', '52', '54', '56', '91', '96', '157'],
                    'diagrams'   => [],
                ],
                'severe_cause_3_1_no' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุร้ายแรง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'fever_anemia_headache' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 1 ไข้ กรอบที่ 1
8 ซีด กรอบที่ 1
21 ปวดศีรษะ กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00001', '00008', '00021'],
                ],
                'severe_dehydration' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง
• เพื่อตรวจหาสาเหตุ
• ถ้ามีภาวะขาดน้ำรุนแรง ให้น้ำเกลือทางหลอดเลือดดำ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'stroke' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
โรคหลอดเลือดสมอง (76)
⊕ ด่วน
NOTE,
                    'refs'       => ['76'],
                    'diagrams'   => [],
                ],
                'acoustic_neuroma' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน อาจเป็นเนื้องอกประสาทหู (164.2)
NOTE,
                    'refs'       => ['164.2'],
                    'diagrams'   => [],
                ],
                'phenytoin_toxicity' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
พิษจากยาเกินขนาด
• ลดขนาดยา
• แนะนำให้กลับไปพบแพทย์ที่รักษาอยู่เดิม
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'brain_tumor_syphilis_stroke' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน อาจเป็นเนื้องอกสมอง (83)/ซิฟิลิสระยะ 3 (211)/โรคหลอดเลือดสมอง (76)/โรคทางสมองอื่นๆ
NOTE,
                    'refs'       => ['83', '211', '76'],
                    'diagrams'   => [],
                ],
                'otitis_media_acute' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูชั้นกลางอักเสบเฉียบพลัน (163)
• ยาปฏิชีวนะ (ย4.2, ย4.7)
• ไดเมนไฮดริเนต (ย19.1)
NOTE,
                    'refs'       => ['163'],
                    'diagrams'   => [],
                ],
                'labyrinthitis_acute' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หูชั้นในอักเสบเฉียบพลัน (164)
NOTE,
                    'refs'       => ['164'],
                    'diagrams'   => [],
                ],
                'meniere_or_migraine' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคเมเนียร์ (165)/ไมเกรน (71)
NOTE,
                    'refs'       => ['165', '71'],
                    'diagrams'   => [],
                ],
                'symptomatic_vertigo_7_2_4' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ
• ไดเมนไฮดริเนต (ย19.1)
• ยาแก้ปวด (ย1) สำหรับไมเกรน
• หลีกเลี่ยงท่า/สาเหตุที่กระตุ้นให้หวิวเวียน
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรืออาเจียนมาก/กินไม่ได้/ขาดน้ำ อาจเป็นเนื้องอกประสาทหู (164.2) หรือภาวะรุนแรงอื่นๆ
NOTE,
                    'refs'       => ['164.2'],
                    'diagrams'   => [],
                ],
                'vestibular_neuritis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เส้นประสาทการทรงตัวอักเสบ (164)
NOTE,
                    'refs'       => ['164'],
                    'diagrams'   => [],
                ],
                'head_neck_injury' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ศีรษะได้รับบาดเจ็บ (81)
• ไดเมนไฮดริเนต (ย19.1)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือมีอาการทางสมอง เช่น ปวดศีรษะรุนแรง อาเจียนรุนแรง ซึม ชัก คอแข็ง รูม่านตา 2 ข้างไม่เท่ากัน เป็นต้น
NOTE,
                    'refs'       => ['81'],
                    'diagrams'   => [],
                ],
                'motion_sickness' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เมารถ/เรือ/เครื่องบิน/เครื่องเล่น (motion sickness)
• ไดเมนไฮดริเนต (ย19.1) กินรักษาหรือป้องกัน (การป้องกันควรกินก่อนขึ้นยานพาหนะ 60 นาที)
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'cervical_nerve_compression' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
กระดูกคอออกกดรากประสาท (108.1)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['108.1'],
                    'diagrams'   => [],
                ],
                'bppv' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
บ้านหมุนจากการเปลี่ยนท่า (164.1)
NOTE,
                    'refs'       => ['164.1'],
                    'diagrams'   => [],
                ],
                'symptomatic_vertigo_7_6_3' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ
• ไดเมนไฮดริเนต (ย19.1)
• หลีกเลี่ยงท่าที่ทำให้บ้านหมุน
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือเป็นๆ หายๆ บ่อย/มีเสียงดังรบกวนในหู/เดินเซ/เห็นภาพซ้อน/พูดไม่ชัด/กลืนลำบาก/ชาขอบปาก/แขนขาชาหรืออ่อนแรง/อาเจียนมาก
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'migraine_7_7' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไมเกรน (71)
• ให้การดูแลรักษาแบบไมเกรน
NOTE,
                    'refs'       => ['71'],
                    'diagrams'   => [],
                ],
                'drug_alcohol_cause' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากยา* /แอลกอฮอล์
• งดดื่มแอลกอฮอล์
• ถ้าเกิดจากยา แนะนำให้กลับไปพบแพทย์ที่สั่งใช้ยา
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'symptomatic_vertigo_7_8' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ
• ไดเมนไฮดริเนต (ย19.1)
• หลีกเลี่ยงท่าที่ทำให้บ้านหมุน
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรืออาเจียนมาก/กินไม่ได้/ขาดน้ำ
NOTE,
                    'refs'       => [],
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
                'orthostatic_hypotension' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ความดันตกในท่ายืน (93)
• รักษาตามสาเหตุ เช่น แก้ไขภาวะขาดน้ำ ปรับลดยาลดความดัน เป็นต้น
⊕ ถ้ามีภาวะตกเลือด/ให้การดูแลเบื้องต้นแล้วไม่ดีขึ้น
NOTE,
                    'refs'       => ['93'],
                    'diagrams'   => [],
                ],
                'heart_disease' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน อาจเป็นโรคหัวใจขาดเลือด (96)/โรคหัวใจเต้นผิดจังหวะ (97)/ลิ้นหัวใจรั่วหรือตีบ
NOTE,
                    'refs'       => ['96', '97'],
                    'diagrams'   => [],
                ],
                'pregnancy' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ตั้งครรภ์ (154)
• ตรวจปัสสาวะ
• แนะนำไปฝากครรภ์
NOTE,
                    'refs'       => ['154'],
                    'diagrams'   => [],
                ],
                'elderly_disequilibrium' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ภาวะสูญเสียการทรงตัวในผู้สูงอายุ (73)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['73'],
                    'diagrams'   => [],
                ],
                'drug_alcohol_smoking' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากยา* แอลกอฮอล์ หรือบุหรี่
• งดดื่มแอลกอฮอล์
• ถ้าเกิดจากยา แนะนำให้กลับไปพบแพทย์ที่สั่งใช้ยา
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'hypoglycemia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
น้ำตาลในเลือดต่ำ (118)
• ให้กินน้ำหวาน
⊕ ถ้าหมดสติ
NOTE,
                    'refs'       => ['118'],
                    'diagrams'   => [],
                ],
                'lifestyle_causes' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เวียนศีรษะจากสาเหตุเหล่านี้
• แก้ตามสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'anxiety_panic_depression' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรควิตกกังวล/โรคกังวลทั่วไป (88)/โรคแพนิก (88.1)/โรคอารมณ์แปรปรวน/โรคซึมเศร้า (88.2)
• ยาทางจิตประสาท (ย17)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['88', '88.1', '88.2'],
                    'diagrams'   => [],
                ],
                'postural_cerebral_ischemia' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
เลือดชี้เลี้ยงสมองไม่ทัน
• ค่อยๆ ลุกขึ้นนั่งหรือยืดอย่างช้าๆ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'symptomatic_dizziness_18' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
รักษาตามอาการ
• ถ้าคลื่นไส้ อาเจียนให้ไดเมนไฮดริเนต (ย19.1)
• ขณะมีอาการให้รีบนั่งหรือนอนลง
• ลุกขึ้นนั่งหรือยืนอย่างช้าๆ
• นอนหลับพักผ่อนให้เพียงพอ
• ออกกำลังกายเป็นประจำ
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือเป็นๆ หายๆ บ่อย
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
            ];

            // 5. บันทึก Rules และ Relations ลงฐานข้อมูล
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
                    'medical_reference' => 'แผนภูมิที่ 22',
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

                // Rule Conditions
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

                // Rule Diseases (รหัสโรคอ้างอิง)
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

                // Rule Next Diagrams (แผนภูมิอ้างอิงถัดไป)
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

        $this->command->info('สร้างแผนภูมิที่ 22 (เวียนศีรษะ/บ้านหมุน) กรอบ 1-18 สำเร็จ');
    }
}
