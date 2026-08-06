<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram41PalpitationSweatingSeeder extends Seeder
{
    private const DIAGRAM_ID = '00041';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '1.1', '1.2', '1.3', '1.4',
                '2', '2.1', '2.2', '2.3', '2.4', '2.5', '2.6', '2.7', '2.8',
                '3', '3.1', '3.2', '3.2.1', '3.2.2', '3.2.3'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 41 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 41
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ใจสั่น (PALPITATION)/เหงื่อออกตามมือเท้า (SWEATING)',
                'diagram_name_en' => 'Palpitation / Sweating',
                'description' => 'มีความรู้สึกใจเต้นเร็วหรือแรงกว่าปกติ เต้นไม่สม่ำเสมอหรือวูบหายเป็นครั้งคราว หรือรู้สึกว่ามีเหงื่อออกตามมือเท้ามากกว่าปกติ สาเหตุที่พบบ่อย โรควิตกกังวล/โรคกังวลทั่วไป (88) โรคหัวใจเต้นผิดจังหวะ (97) ภาวะต่อมไทรอยด์ทำงานเกิน (121) ความดันโลหิตสูง (92) ถ้าอาการไม่ชัดเจน ในรายที่มีความวิตกกังวลอาจให้ยาทางจิตประสาท (ย17)',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 41
            $boxes = [
                'B1'      => ['frame' => '1',     'type' => 'S', 'q' => "ชีพจรเต้นไม่สม่ำเสมอ\nและแรงไม่เท่ากันตลอด?"],
                'B1_1'    => ['frame' => '1.1',   'type' => 'S', 'q' => "เจ็บหน้าอกรุนแรง?\nหอบเหนื่อย? แขนขาข้าง\nหนึ่งอ่อนแรงฉับพลัน?\nเป็นลม? หรือ หมดสติ?"],
                'B1_2'    => ['frame' => '1.2',   'type' => 'S', 'q' => "น้ำหนักลดฮวบ?\nและ ชีพจร > 120 ครั้ง/นาที?"],
                'B1_3'    => ['frame' => '1.3',   'type' => 'S', 'q' => "ใช้เครื่องฟังตรวจหัวใจมีเสียงฟู่\n(murmur)?"],
                'B1_4'    => ['frame' => '1.4',   'type' => 'S', 'q' => "ชีพจร 80-180 ครั้ง/นาที หรือ\n< 50 ครั้ง/นาที จังหวะไม่สม่ำเสมอ?"],
                'B2'      => ['frame' => '2',     'type' => 'S', 'q' => "ชีพจร > 100 ครั้ง/นาที เต้น\nสม่ำเสมอและแรงเท่ากันตลอด?"],
                'B2_1'    => ['frame' => '2.1',   'type' => 'S', 'q' => "ตกเลือดรุนแรง? ปวด\nท้องรุนแรง? อาเจียนรุน\nแรง? ท้องเดินรุนแรง?\nเจ็บหน้าอกรุนแรง? ถ่าย\nดำ? หรือตัวเย็น เป็นลม\nหน้ามืด เวลาลุกขึึ้นนั่ง\nหรือยืน?"],
                'B2_2'    => ['frame' => '2.2',   'type' => 'S', 'q' => "หายใจหอบเหนื่อย?\nและ เท้าบวม?"],
                'B2_3'    => ['frame' => '2.3',   'type' => 'S', 'q' => 'มีไข้? หรือ ซีด?'],
                'B2_4'    => ['frame' => '2.4',   'type' => 'S', 'q' => "ผู้ป่วยกิน/ฉียารักษาเบาหวาน\nมีอาการใจสั่นหลังอดข้าว/\nกินข้าวผิดเวลา?"],
                'B2_5'    => ['frame' => '2.5',   'type' => 'S', 'q' => "ความดันโลหิตช่วงบน >= 140\nหรือช่วงล่าง >= 90 มม.ปรอท?"],
                'B2_6'    => ['frame' => '2.6',   'type' => 'M', 'q' => "มีอาการอย่างน้อย 2 อย่าง\nดังต่อไปนี้\n[ ] น้ำหนักลดฮวบ?\n[ ] ขี้ร้อน (เหงื่อมาก)?\n[ ] มือสั่น?  [ ] ตาโปน?\n[ ] คอพอก?"],
                'B2_7'    => ['frame' => '2.7',   'type' => 'S', 'q' => "ชีพจร 160-220 ครั้ง/นาที สม่ำเสมอ\nเกิดขึ้นฉับพลัน และทุเลาได้เอง\nฉับพลัน นานครั้งละไม่กี่นาทีถึง\nหลายชั่วโมง?"],
                'B2_8'    => ['frame' => '2.8',   'type' => 'S', 'q' => "หลังออกกำลังกาย? ตื่นเต้นตกใจ?\nดื่มชา กาแฟ? ดื่มแอลกอฮอล์?\nสูบบุหรี่? หรือ ฉียา กินยา*?"],
                'B3'      => ['frame' => '3',     'type' => 'S', 'q' => 'ชีพจร 60-100 ครั้ง/นาที?'],
                'B3_1'    => ['frame' => '3.1',   'type' => 'S', 'q' => "ชีพจรเต้นรัวหรือวูบหาย\nเป็นบางจังหวะ (ชีพจร\nส่วนใหญ่เต้นปกติ)?"],
                'B3_2'    => ['frame' => '3.2',   'type' => 'S', 'q' => 'ชีพจรเต้นปกติ?'],
                'B3_2_1'  => ['frame' => '3.2.1', 'type' => 'S', 'q' => "มีประวัติว่าเพิ่งหาย\nจากอาการใจสั่นใจเต้น\nเร็วรุนแรงฉับพลัน\nมีอาการอยู่พักหนึ่งก็\nทุเลาไปได้เอง?"],
                'B3_2_2'  => ['frame' => '3.2.2', 'type' => 'S', 'q' => "มีความกลัว วิตกกังวล คิดมาก ซึม\nซึมเศร้า หรือนอนไม่หลับ? หรือ\nออกร้อนซู่ซ่าตามผิวกาย หรือ\nเหงื่อออกตอนกลางคืนในผู้หญิง\nวัยหมดประจำเดือน (40-55 ปี)?"],
                'B3_2_3'  => ['frame' => '3.2.3', 'type' => 'S', 'q' => "เหงื่อออกที่ฝ่ามือ ฝ่าเท้า พบในคน\nอ้วน วัยรุ่น หรือในช่วงที่มีประจำ\nเดือน?"],
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
                    'min_required' => $box['type'] === 'M' ? 2 : null,
                    'detail' => $box['frame'] === '2.8' ? '* ยาที่ทำให้ใจสั่น เช่น อะดรีนาลีน (ย11) ซูโดเอเฟดรีน (ย8.2) ทีโอฟิลลีน (ย10.2) ยากระตุ้นบีตา 2 (ย10.3) แอนติสปาสโมดิก (ย20) อะมิทริปไทลีน (ย17.2) ฮอร์โมนไทรอยด์ แอมเฟตามีน โคเคน เวราพามิล (verapamil)' : null,
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
                'B1'     => [[null, 'B1_1'], [null, 'B2']],
                'B1_1'   => [['arrhythmia_severe_emergency', null], [null, 'B1_2']],
                'B1_2'   => [['hyperthyroidism_pulse_120', null], [null, 'B1_3']],
                'B1_3'   => [['valvular_heart_disease_rheumatic', null], [null, 'B1_4']],
                'B1_4'   => [['atrial_flutter_bradycardia', null], ['arrhythmia_24h_warning', null]],
                'B2'     => [[null, 'B2_1'], [null, 'B3']],
                'B2_1'   => [['shock_severe_cause', null], [null, 'B2_2']],
                'B2_2'   => [['heart_failure_dyspnea', null], [null, 'B2_3']],
                'B2_3'   => [['refer_diagram_1_or_8', null], [null, 'B2_4']],
                'B2_4'   => [['hypoglycemia_diabetic', null], [null, 'B2_5']],
                'B2_5'   => [['hypertension', null], [null, 'B2_6']],
                'B2_6'   => [['hyperthyroidism_toxic_goiter', null], [null, 'B2_7']],
                'B2_7'   => [['paroxysmal_supraventricular_tachycardia', null], [null, 'B2_8']],
                'B2_8'   => [['substance_induced_palpitation', null], ['unexplained_tachycardia_care', null]],
                'B3'     => [[null, 'B3_1'], ['abnormal_pulse_3days_warning', null]],
                'B3_1'   => [['premature_contractions_pvc_pac', null], [null, 'B3_2']],
                'B3_2'   => [[null, 'B3_2_1'], [null, 'B3_2_2']],
                'B3_2_1' => [['psvt_resolved_history', null], [null, 'B3_2_2']],
                'B3_2_2' => [['anxiety_panic_menopause', null], [null, 'B3_2_3']],
                'B3_2_3' => [['physiological_sweating_harmless', null], ['unexplained_palpitation_psychogenic_care', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'arrhythmia_severe_emergency' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน อาจเป็นหัวใจเต้นผิดจังหวะ (97) ที่มีภาวะร้ายแรง เช่น กล้ามเนื้อหัวใจตาย (96)/หัวใจวาย (98)/ลิ่มเลือดหลุดอุดตันหลอดเลือดสมอง (76)
NOTE,
                    'refs'       => ['97', '96', '98', '76'],
                    'diagrams'   => [],
                ],
                'hyperthyroidism_pulse_120' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ภาวะต่อมไทรอยด์ทำงานเกิน (121)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['121'],
                    'diagrams'   => [],
                ],
                'valvular_heart_disease_rheumatic' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
โรคลิ้นหัวใจพิการ/โรคหัวใจรูมาติก (94)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['94'],
                    'diagrams'   => [],
                ],
                'atrial_flutter_bradycardia' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
หัวใจห้องบนเต้นแผ่วระรัว/หัวใจเต้นช้า (ดู "โรคที่ 97")
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['97'],
                    'diagrams'   => [],
                ],
                'arrhythmia_24h_warning' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'shock_severe_cause' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ช็อก (91)/สาเหตุร้ายแรงอื่น ๆ
⊕ ด่วน (ดู "โรคที่ 32, 34, 35, 47, 48, 50, 51, 52, 54, 56, 91, 95, 157")
NOTE,
                    'refs'       => ['91', '32', '34', '35', '47', '48', '50', '51', '52', '54', '56', '95', '157'],
                    'diagrams'   => [],
                ],
                'heart_failure_dyspnea' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
หัวใจวาย (98)
• ฉีดฟูโรซีไมด์ (ย21.1)
⊕ ด่วน
NOTE,
                    'refs'       => ['98'],
                    'diagrams'   => [],
                ],
                'refer_diagram_1_or_8' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ดูแผนภูมิที่ 1 ไข้ กรอบที่ 1
หรือแผนภูมิที่ 8 ซีด กรอบที่ 1
NOTE,
                    'refs'       => [],
                    'diagrams'   => ['00001', '00008'],
                ],
                'hypoglycemia_diabetic' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ภาวะน้ำตาลในเลือดต่ำ (118)
• กินน้ำตาล/ของหวาน
⊕ ถ้าไม่ดีขึ้น/เป็นลมหมดสติ
NOTE,
                    'refs'       => ['118'],
                    'diagrams'   => [],
                ],
                'hypertension' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ความดันโลหิตสูง (92)
• ขั้นสูตรเพิ่มเติม
• ยาลดความดัน (ย22)
⊕ ถ้าควบคุมความดันไม่ได้/มีภาวะแทรกซ้อน/สงสัยเป็นความดันโลหิตสูงชนิดทุติยภูมิ
NOTE,
                    'refs'       => ['92'],
                    'diagrams'   => [],
                ],
                'hyperthyroidism_toxic_goiter' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
ภาวะต่อมไทรอยด์ทำงานเกิน/คอพอกเป็นพิษ (121)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['121'],
                    'diagrams'   => [],
                ],
                'paroxysmal_supraventricular_tachycardia' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
หัวใจห้องบนเต้นเร็วชนิดโรคกลับฉับพลัน (ดู "โรคที่ 97")
• ให้ยาปิดกั้นบีตา (ย22.2) ทันที ถ้าทุเลาให้กินยานี้ต่อแล้วแนะนำไปตรวจหาสาเหตุภายใน 1 สัปดาห์
⊕ ถ้าอาการไม่ทุเลา หรือมีอาการเจ็บแน่นหน้าอกร้าวขึ้นคอ/สงสัยเป็นโรคหัวใจขาดเลือด (96)
NOTE,
                    'refs'       => ['97', '96'],
                    'diagrams'   => [],
                ],
                'substance_induced_palpitation' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
สาเหตุจากสิ่งเหล่านี้
• พักผ่อน
• หยุดยา/สารกระตุ้น
• ถ้าเครียด/นอนไม่หลับให้ไดอะซีแพม (ย17.1)
⊕ ถ้าไม่หายใน 24 ชั่วโมง หรือเจ็บหน้าอกรุนแรง/หอบ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'unexplained_tachycardia_care' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
• ถ้าเครียด/นอนไม่หลับให้ไดอะซีแพม (ย17.1)
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์ หรือเจ็บแน่นหน้าอก/เหนื่อยง่าย/บวม/น้ำหนักลด
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'premature_contractions_pvc_pac' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
หัวใจห้องบนเต้นก่อนกำหนด (เต้นรัวเป็นบางจังหวะ) หรือหัวใจห้องล่างเต้นก่อนกำหนด (ชีพจรรวบหายเป็นบางจังหวะ) (ดู "โรคที่ 97")
• นอนหลับพักผ่อนให้เพียงพอ
• งดชา กาแฟ บุหรี่ แอลกอฮอล์ ยา กระตุ้น
⊕ ถ้าชีพจรเต้นรัวหรือวูบหายเเน่นๆ/มีอาการเจ็บแน่นหน้าอก/หอบเหนื่อย/ฟังหัวใจมีเสียงฟู่/เท้าบวม
NOTE,
                    'refs'       => ['97'],
                    'diagrams'   => [],
                ],
                'psvt_resolved_history' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์ อาจเป็นหัวใจห้องบนเต้นเร็วชนิดโรคกลับฉับพลัน (ดู "โรคที่ 97")
NOTE,
                    'refs'       => ['97'],
                    'diagrams'   => [],
                ],
                'anxiety_panic_menopause' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรควิตกกังวล/โรคกังวลทั่วไป (88)/โรคแพนิก (88.1)/โรคอารมณ์แปรปรวน/โรคซึมเศร้า (88.2)/โรคของหญิงวัยหมดประจำเดือน (129)
• ยาทางจิตประสาท (ย17)
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์
NOTE,
                    'refs'       => ['88', '88.1', '88.2', '129'],
                    'diagrams'   => [],
                ],
                'physiological_sweating_harmless' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ไม่มีอันตราย และไม่ต้องให้การรักษาแม้อย่างใด
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'unexplained_palpitation_psychogenic_care' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
• ยาทางจิตประสาท (ย17) ถ้าเครียด/นอนไม่หลับ
⊕ ถ้าไม่ดีขึ้นใน 2 สัปดาห์/ความดันโลหิตสูง
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'abnormal_pulse_3days_warning' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
⊕ ภายใน 3 วัน ถ้าชีพจร < 50 ครั้ง/นาที หรือ > 120 ครั้ง/นาที หรือเต้นจังหวะไม่สม่ำเสมอ หรือความดันโลหิตสูง
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
                    'medical_reference' => 'แผนภูมิที่ 41',
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

        $this->command->info('สร้างแผนภูมิที่ 41 (ใจสั่น/เหงื่อออกตามมือเท้า) กรอบ 1-3.2.3 สำเร็จ');
    }
}
