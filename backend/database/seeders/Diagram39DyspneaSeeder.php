<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram39DyspneaSeeder extends Seeder
{
    private const DIAGRAM_ID = '00039';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '3', '4', '4.1', '4.1.1', '4.2',
                '5', '6', '6.1', '6.2', '7', '7.1', '7.2',
                '7.3', '7.4', '7.4.1', '7.5', '8', '9', '10',
                '11', '12', '13', '13.1', '13.2'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 39 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 39
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'หอบ/เหนื่อยง่าย (DYSPNEA)',
                'diagram_name_en' => 'Dyspnea',
                'description' => 'มีอาการหายใจขัดหรือลำบาก หายใจถี่เร็ว หายใจลึก หรือหายใจแรง อาจเห็นรูจมูกบาน คอบุ๋ม ช่องซี่โครงบุ๋ม หรือปากเขียว เล็บเขียว หรือรู้สึกเหนื่อยง่ายเวลาออกแรงเพียงเล็กน้อย สาเหตุที่พบบ่อย หืด (24) ปอดอักเสบ (19) ภาวะหัวใจวาย (98) ถุงลมปอดโป่งพอง (16) กลุ่มอาการระบายลมหายใจเกิน (89) ถ้าอาการไม่ชัดเจน 1. ถ้ามีอาการหอบอย่างชัดเจน ส่งโรงพยาบาลด่วน 2. ถ้าเพียงแต่บ่นหายใจไม่อิ่ม หรือถอนหายใจบ่อยโดยไม่มีอาการหอบ ให้ยาทางจิตประสาท (ย17) ถ้าไม่ดีขึ้นใน 1 สัปดาห์ควรปรึกษาแพทย์ ถ้ารู้สึกอ่อนเพลียโดยไม่มีอาการหอบเหนื่อย ดูแผนภูมิที่ 5',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 39
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => 'มีไข้?'],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => 'เจ็บหน้าอกรุนแรง?'],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => "เท้าบวม? หรือ นอนราบไม่ได้\n(ต้องนอนหมอนสูง)?"],
                'B4'      => ['frame' => '4',     'type' => 'S', 'q' => "หลอดลมใหญ่เบี้ยวไปข้างหนึ่ง?\nหรือ ใช้เครื่องฟังปอดข้างหนึ่ง\nไม่ได้ยินเสียงหายใจ?"],
                'B4_1'    => ['frame' => '4.1',   'type' => 'S', 'q' => "ปอดข้างหนึ่ง\nเคาะทึบกว่า\nปกติ?"],
                'B4_1_1'  => ['frame' => '4.1.1', 'type' => 'S', 'q' => "ได้รับบาดเจ็บ\nที่หน้าอก?"],
                'B4_2'    => ['frame' => '4.2',   'type' => 'S', 'q' => 'ปอดข้างหนึ่งเคาะโปร่งกว่าปกติ?'],
                'B5'      => ['frame' => '5',     'type' => 'S', 'q' => "มีอาการหอบเกิดขึ้นทันที ใน\nเด็กที่เล่นเหรียญสตางค์ กระดุม\nเมล็ดน้อยหน่า ถั่ว หรือของอื่นๆ\nหรือ ในผู้ที่สำลักอาหารหรือกลืน\nเมล็ดผลไม้?"],
                'B6'      => ['frame' => '6',     'type' => 'S', 'q' => "ใช้เครื่องฟังปอดมีเสียง\nกรอบแกรบ (crepitation)?"],
                'B6_1'    => ['frame' => '6.1',   'type' => 'S', 'q' => "เกิดอาการหอบ\nหลังให้น้ำเกลือเร็วๆ?"],
                'B6_2'    => ['frame' => '6.2',   'type' => 'S', 'q' => "มีประวัติเป็นโรคหัวใจ เบาหวาน\nหรือความดันโลหิตสูง?"],
                'B7'      => ['frame' => '7',     'type' => 'S', 'q' => "ใช้เครื่องฟังปอดมีเสียงวี้ด\n(wheezing)?"],
                'B7_1'    => ['frame' => '7.1',   'type' => 'S', 'q' => "มีอาการหอบหลังฉีดยา\nกินยา หรือถูกผึ้งหรือ\nต่อต่อย?"],
                'B7_2'    => ['frame' => '7.2',   'type' => 'S', 'q' => "เคยเป็นหืดหรือหอบบ่อยๆ?\nมีประวัติเป็นโรคหืดมาก่อน?\nหรือ หอบเป็นครั้งคราว ในผู้ที่มี\nประวัติครอบครัวเป็นหืดหรือโรค\nภูมิแพ้?"],
                'B7_3'    => ['frame' => '7.3',   'type' => 'S', 'q' => "ปอดเคาะโปร่ง และใช้เครื่องฟัง\nปอดพบเสียงหายใจค่อย?\nหรือ พบในผู้สูงอายุที่มีประวัติสูบ\nบุหรี่จัดมานาน?"],
                'B7_4'    => ['frame' => '7.4',   'type' => 'S', 'q' => "ไอมีเสมหะเป็นหนอง\nและมีกลิ่นเหม็น?"],
                'B7_5'    => ['frame' => '7.5',   'type' => 'S', 'q' => "ไอมีเสมหะมาก หลังเป็นไข้หวัด/\nไข้หวัดใหญ่?"],
                'B8'      => ['frame' => '8',     'type' => 'S', 'q' => "ใช้เครื่องตรวจปอด พบเสียง\nหายใจผิดปกติอื่นๆ เช่น เสียง\nหายใจค่อย เสียงอี้ด (rhonchi)?"],
                'B9'      => ['frame' => '9',     'type' => 'S', 'q' => "มีประวัติเป็นโรคเบาหวาน\nแต่ขาดการรักษา?"],
                'B10'     => ['frame' => '10',    'type' => 'S', 'q' => "มีประวัติเป็นโรคไต เบาหวาน\nหรือความดันโลหิตสูงเรื้อรัง?"],
                'B11'     => ['frame' => '11',    'type' => 'S', 'q' => "ตกเลือดรุนแรง?\nท้องเดินรุนแรง?\nอาเจียนรุนแรง? เป็นไข้เรื้อรัง?\nซีด? หรือ กินไม่ได้หลายวัน?"],
                'B12'     => ['frame' => '12',    'type' => 'S', 'q' => "มือจีบเกร็งทั้ง 2 ข้าง\nและมีเรื่องกลุ้มใจก่อนหอบ\nหรือมีอาการปวดรุนแรง?"],
                'B13'     => ['frame' => '13',    'type' => 'S', 'q' => "รู้สึกเหนื่อยง่ายเวลาออกแรง\nเพียงเล็กน้อย (ไม่มีอาการหายใจ\nหอบ)?"],
                'B13_1'   => ['frame' => '13.1',  'type' => 'S', 'q' => "ไอเรื้อรังในผู้ที่สูบบุหรี่\nจัด?"],
                'B13_2'   => ['frame' => '13.2',  'type' => 'S', 'q' => "ชีพจรมากกว่า 100 ครั้ง/นาที\nหรือเต้นไม่สม่ำเสมอ?"],
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
                'B1'     => [['refer_diagram_3', null], [null, 'B2']],
                'B2'     => [['myocardial_infarction_pulmonary_embolism', null], [null, 'B3']],
                'B3'     => [['heart_failure_renal_failure', null], [null, 'B4']],
                'B4'     => [[null, 'B4_1'], [null, 'B5']],
                'B4_1'   => [[null, 'B4_1_1'], [null, 'B4_2']],
                'B4_1_1' => [['hemothorax', null], ['empyema_pleural_effusion', null]],
                'B4_2'   => [['pneumothorax', null], ['severe_unknown_cause_4_2', null]],
                'B5'     => [['foreign_body_aspiration', null], [null, 'B6']],
                'B6'     => [[null, 'B6_1'], [null, 'B7']],
                'B6_1'   => [['pulmonary_edema_fluid_overload', null], [null, 'B6_2']],
                'B6_2'   => [['heart_failure', null], [null, 'B7']],
                'B7'     => [[null, 'B7_1'], [null, 'B8']],
                'B7_1'   => [['anaphylaxis_drug_sting_allergy', null], [null, 'B7_2']],
                'B7_2'   => [['asthma', null], [null, 'B7_3']],
                'B7_3'   => [['emphysema', null], [null, 'B7_4']],
                'B7_4'   => [['bronchiectasis', null], [null, 'B7_5']],
                'B7_5'   => [['acute_bronchitis', null], [null, 'B8']],
                'B8'     => [['abnormal_breath_sounds_severe', null], [null, 'B9']],
                'B9'     => [['dka', null], [null, 'B10']],
                'B10'    => [['chronic_renal_failure', null], [null, 'B11']],
                'B11'    => [['acidosis_shock_septicemia', null], [null, 'B12']],
                'B12'    => [['hyperventilation_syndrome', null], [null, 'B13']],
                'B13'    => [[null, 'B13_1'], ['dyspnea_severe_warning', null]],
                'B13_1'  => [['emphysema_smoker', null], [null, 'B13_2']],
                'B13_2'  => [['refer_diagram_41', null], ['general_effort_fatigue_care', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'refer_diagram_3' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 3 ไข้ร่วมกับหอบ กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00003'],
                ],
                'myocardial_infarction_pulmonary_embolism' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุร้ายแรง เช่น กล้ามเนื้อหัวใจตาย (96)/ภาวะสิ่งหลุดอุดตันหลอดเลือดแดงปอด (ดู "โรคที่ 99.1")
NOTE,
                    'refs'       => ['96', '99.1'],
                    'diagrams'   => [],
                ],
                'heart_failure_renal_failure' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะหัวใจวาย (98)/ไตวาย (134)
• ฉีดฟูโรซีไมด์ (ย21.1)
⊕ ด่วน
NOTE,
                    'refs'       => ['98', '134'],
                    'diagrams'   => [],
                ],
                'hemothorax' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
เลือดออกในโพรงเยื่อหุ้มปอด
⊕ ด่วน
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'empyema_pleural_effusion' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะมีหนอง/น้ำในโพรงเยื่อหุ้มปอด (20)
⊕ ด่วน
NOTE,
                    'refs'       => ['20'],
                    'diagrams'   => [],
                ],
                'pneumothorax' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ปอดทะลุ (22)
⊕ ด่วน
NOTE,
                    'refs'       => ['22'],
                    'diagrams'   => [],
                ],
                'severe_unknown_cause_4_2' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุร้ายแรงอื่น ๆ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'foreign_body_aspiration' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
สำลักสิ่งแปลกปลอม/หลอดลมอุดกั้นจากสิ่งแปลกปลอม (23)
• ปฐมพยาบาล
⊕ ด่วน
NOTE,
                    'refs'       => ['23'],
                    'diagrams'   => [],
                ],
                'pulmonary_edema_fluid_overload' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะปอดบวมน้ำ
• หยุดให้น้ำเกลือ
• ฉีดฟูโรซีไมด์ (ย21.1) 1-2 หลอด เข้าหลอดเลือดดำ
⊕ ด่วน ถ้าไม่ดีขึ้น
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'heart_failure' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะหัวใจวาย (98)
• ฉีดฟูโรซีไมด์ (ย21.1)
⊕ ด่วน
NOTE,
                    'refs'       => ['98'],
                    'diagrams'   => [],
                ],
                'anaphylaxis_drug_sting_allergy' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
อาการแพ้ยา/แพ้พิษผึ้งหรือต่อ (222)
• ฉีดอะดรีนาลีน (ย11) ยาแก้แพ้ (ย7) และสเตียรอยด์ (ย12)
• ยากระตุ้นบีตา 2 (ย10.3) ชนิดสูด
⊕ ถ้าไม่หายหอบใน 30 นาที
NOTE,
                    'refs'       => ['222'],
                    'diagrams'   => [],
                ],
                'asthma' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
หืด (24)
• ให้ยากระตุ้นบีตา 2 (ย10.3) สูดหรือฉีด
⊕ ถ้าไม่หายหอบใน 30 นาที
NOTE,
                    'refs'       => ['24'],
                    'diagrams'   => [],
                ],
                'emphysema' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ถุงลมปอดโป่งพอง (16)
• ถ้าเสมหะเป็นหนองให้อะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.1)
• ดื่มน้ำอุ่นมากๆ
• ยายายหลอดลม (ย10) ถ้ามีเสียงวี้ด
⊕ ด่วน ถ้าอาการหอบไม่ดีขึ้นใน 6 ชั่วโมง
NOTE,
                    'refs'       => ['16'],
                    'diagrams'   => [],
                ],
                'bronchiectasis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
หลอดลมพอง (17)
• ถ้าเสมหะเป็นหนองให้อะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.1)
• ดื่มน้ำอุ่นมากๆ
• ยายายหลอดลม (ย10) ถ้ามีเสียงวี้ด
⊕ ด่วน ถ้าอาการหอบไม่ดีขึ้นใน 6 ชั่วโมง
NOTE,
                    'refs'       => ['17'],
                    'diagrams'   => [],
                ],
                'acute_bronchitis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
หลอดลมอักเสบเฉียบพลัน (15)
• ถ้าเสมหะเป็นหนองให้อะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.1)
• ดื่มน้ำอุ่นมากๆ
• ยายายหลอดลม (ย10) ถ้ามีเสียงวี้ด
⊕ ด่วน ถ้าอาการหอบไม่ดีขึ้นใน 6 ชั่วโมง
NOTE,
                    'refs'       => ['15'],
                    'diagrams'   => [],
                ],
                'abnormal_breath_sounds_severe' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจมีสาเหตุร้ายแรง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'dka' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะเลือดเป็นกรดจากเบาหวาน (117)
• ให้น้ำเกลือนอร์มัล
⊕ ด่วน
NOTE,
                    'refs'       => ['117'],
                    'diagrams'   => [],
                ],
                'chronic_renal_failure' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะไตวายเรื้อรัง (134)
⊕ ด่วน
NOTE,
                    'refs'       => ['134'],
                    'diagrams'   => [],
                ],
                'acidosis_shock_septicemia' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ภาวะเลือดเป็นกรด/ช็อก (91)/โลหิตเป็นพิษ (228)
• ให้น้ำเกลือแก้ช็อก
⊕ ด่วน
NOTE,
                    'refs'       => ['91', '228'],
                    'diagrams'   => [],
                ],
                'hyperventilation_syndrome' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
กลุ่มอาการระบายลมหายใจเกิน (89)
• ไดอะซีแพม (ย17.1)
• หายใจในกรวยกระดาษ
⊕ ถ้าไม่ดีขึ้นใน 6 ชั่วโมง
NOTE,
                    'refs'       => ['89'],
                    'diagrams'   => [],
                ],
                'dyspnea_severe_warning' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน ถ้ามีอาการหายใจหอบ หรือปากเขียวเล็บเขียว
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'emphysema_smoker' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ถุงลมปอดโป่งพอง (16)
• ให้การรักษาดังกรอบที่ 7.4.1
NOTE,
                    'refs'       => ['16'],
                    'diagrams'   => [],
                ],
                'refer_diagram_41' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 41 ใจสั่น กรอบที่ 5.1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00041'],
                ],
                'general_effort_fatigue_care' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือเจ็บหน้าอกร้าวขึ้นคอ/เป็นเบาหวานหรือความดันโลหิตสูง/มีความวิตกกังวล
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
                    'medical_reference' => 'แผนภูมิที่ 39',
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

        $this->command->info('สร้างแผนภูมิที่ 39 (หอบ/เหนื่อยง่าย - DYSPNEA) กรอบ 1-13 สำเร็จ');
    }
}
