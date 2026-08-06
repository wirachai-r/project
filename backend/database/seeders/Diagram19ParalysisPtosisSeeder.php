<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram19ParalysisPtosisSeeder extends Seeder
{
    private const DIAGRAM_ID = '00019';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = [
                '1', '2', '2.1', '2.2', '2.3', '2.4', '2.5', '2.6',
                '3', '3.1', '4', '4.1', '4.2', '4.3', '4.4', '4.5',
                '5', '5.1', '6', '6.1', '6.2', '6.3', '6.4', '6.5'
            ];

            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 19 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 19
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'อัมพาต (PARALYSIS)/หนังตาตก (PTOSIS)',
                'diagram_name_en' => 'Paralysis / Ptosis',
                'description' => 'อัมพาต/แขนขาอ่อนแรง หมายถึงอาการกล้ามเนื้ออ่อนแรง ขยับเขยื้อนไม่ได้หรือได้น้อยกว่าปกติ มักจะเป็นที่แขนขา ทำให้เดินไม่ได้หรือทำงานไม่ได้ บางรายแขนขาอาจแข็งแรงดี แต่มีอาการอัมพาตของกล้ามเนื้อใบหน้า หรือเปลือกตาก็ได้ ถ้ามีอาการอ่อนแรง (อัมพาต) ของกล้ามเนื้อที่ทำหน้าที่ลืมตา จะทำให้หนังตาตก ตาปรือ คล้ายคนง่วงนอน เราเรียกอาการนี้ว่า หนังตาตก สาเหตุที่พบบ่อย อัมพาตครึ่งซีก (76) อัมพาตเบลล์ (77) ถ้าอาการไม่ชัดเจน ควรส่งโรงพยาบาลโดยเร็ว',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 19
            $boxes = [
                'B1'    => ['frame' => '1',    'type' => 'S', 'q' => 'หมดสติ?'],
                'B2'    => ['frame' => '2',    'type' => 'S', 'q' => 'อัมพาตครึ่งล่าง (เฉพาะขา 2 ข้าง)? หรือ อัมพาตหมดทั้งแขนขา (แขนขาทั้ง 4 ข้าง)?'],
                'B2_1'  => ['frame' => '2.1',  'type' => 'S', 'q' => 'เป็นหลังถูกงูกัด?'],
                'B2_2'  => ['frame' => '2.2',  'type' => 'S', 'q' => 'ได้รับบาดเจ็บที่คอหรือหลัง?'],
                'B2_3'  => ['frame' => '2.3',  'type' => 'S', 'q' => 'มีไข้? แขนขาอ่อนแรงเกิดขึ้นหลังเป็นโรคหัดเยอรมัน หรือหลังเป็นไข้คล้ายไข้หวัด? หรือ แรกเริ่มมีอาการชาและอ่อนแรงที่ปลายเท้า แล้วลุกกล้ามขึ้นมาที่ขาภายในเวลารวดเร็ว?'],
                'B2_4'  => ['frame' => '2.4',  'type' => 'S', 'q' => 'มีประวัติเป็นโรคพยาธิจี๊ด?'],
                'B2_5'  => ['frame' => '2.5',  'type' => 'S', 'q' => 'ค่อยๆ เกิดขึ้นเป็นแรมเดือน ในผู้สูงอายุ?'],
                'B2_6'  => ['frame' => '2.6',  'type' => 'S', 'q' => 'มีอาการเป็นครั้งคราว?'],
                'B3'    => ['frame' => '3',    'type' => 'S', 'q' => 'อัมพาตครึ่งซีก (แขนขาอ่อนแรงซีกหนึ่ง) แบบเกิดขึ้นฉับพลัน?'],
                'B3_1'  => ['frame' => '3.1',  'type' => 'S', 'q' => 'เป็นอยู่นานไม่เกิน 30 นาที แล้วหายได้เอง?'],
                'B4'    => ['frame' => '4',    'type' => 'S', 'q' => 'แขน ขา มือ หรือเท้าไม่มีแรง? หรือ เดินกะเพลก?'],
                'B4_1'  => ['frame' => '4.1',  'type' => 'S', 'q' => 'ขาข้างหนึ่งอ่อนปวียกเปียกพับหลังมีอาการคล้ายไข้หวัด?'],
                'B4_2'  => ['frame' => '4.2',  'type' => 'S', 'q' => 'ขาบวมและชา?'],
                'B4_3'  => ['frame' => '4.3',  'type' => 'S', 'q' => 'ปวดหลังและร้าวลงมาตามเท้า? หรือ ปวดคอและร้าวลงแขน?'],
                'B4_4'  => ['frame' => '4.4',  'type' => 'S', 'q' => 'มีอาการเป็นครั้งคราว?'],
                'B4_5'  => ['frame' => '4.5',  'type' => 'S', 'q' => 'กินยาขับปัสสาวะเป็นประจำ?'],
                'B5'    => ['frame' => '5',    'type' => 'S', 'q' => 'ปากเบี้ยว? และ ปิดตาไม่มิดข้างหนึ่ง (แขนขาแข็งแรงเป็นปกติ)?'],
                'B5_1'  => ['frame' => '5.1',  'type' => 'S', 'q' => 'มีอาการหูอื้อ? หูตึงข้างหนึ่ง? หรือ เดินเซ?'],
                'B6'    => ['frame' => '6',    'type' => 'S', 'q' => 'หนังตาตก หรือตาปรือ (แขนขาแข็งแรงเป็นปกติ)?'],
                'B6_1'  => ['frame' => '6.1',  'type' => 'S', 'q' => 'เป็นหลังถูกงูกัด?'],
                'B6_2'  => ['frame' => '6.2',  'type' => 'S', 'q' => 'เป็นหลังกินอาหารบรรจุปี๊บ กระป๋อง ขวดแก้ว หรือภาชนะที่ปิดมิดชิด?'],
                'B6_3'  => ['frame' => '6.3',  'type' => 'S', 'q' => 'เป็นมาแต่กำเนิด?'],
                'B6_4'  => ['frame' => '6.4',  'type' => 'S', 'q' => 'ปวดตาและใบหน้าซีกหนึ่ง แต่ละครั้งนาน 15 นาที ถึง 4 ชั่วโมง? และตาข้างที่ปวดมีอาการตาแดง น้ำตาไหล รูม่านตาหดเล็ก?'],
                'B6_5'  => ['frame' => '6.5',  'type' => 'S', 'q' => 'เกิดขึ้นเป็นครั้งคราว? ตาปรือข้างเดียว? หรือ เห็นภาพซ้อน?'],
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
            $addChoice = function (string $boxKey, string $text, ?string $nextBoxKey, ?string $ruleKey, int $order, ?string $nextDiagramId = null) use (&$choiceNumber, &$terminalChoices, $boxes, $now) {
                $choiceId = str_pad((string) $choiceNumber++, 10, '0', STR_PAD_LEFT);
                DB::table('answer_choices')->updateOrInsert(['choice_id' => $choiceId], [
                    'choice_text' => $text,
                    'choice_text_en' => null,
                    'order' => $order,
                    'status' => '1',
                    'box_id' => $boxes[$boxKey]['id'],
                    'next_box_id' => $nextBoxKey ? $boxes[$nextBoxKey]['id'] : null,
                    'next_diagram_id' => $nextDiagramId,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);
                if ($ruleKey) {
                    $terminalChoices[$ruleKey][] = ['box_id' => $boxes[$boxKey]['id'], 'choice_id' => $choiceId];
                }
            };

            // แผนที่การตัดสินใจ (Binary Decision Tree)
            // รูปแบบ: 'BoxKey' => [[ Yes: ruleKey, nextBoxKey, nextDiagramId ], [ No: ruleKey, nextBoxKey, nextDiagramId ]]
            $binary = [
                'B1'    => [[null, null, '00016'], [null, 'B2', null]], // ใช่ -> ไปดูแผนภูมิที่ 16 หมดสติ กรอบ 1
                'B2'    => [[null, 'B2_1', null], [null, 'B3', null]],
                'B2_1'  => [['snake_bite_paralysis', null, null], [null, 'B2_2', null]],
                'B2_2'  => [['spinal_cord_injury', null, null], [null, 'B2_3', null]],
                'B2_3'  => [['polio_myelitis_gbs', null, null], [null, 'B2_4', null]],
                'B2_4'  => [['gnathostomiasis_spinal', null, null], [null, 'B2_5', null]],
                'B2_5'  => [['spinal_cord_tumor', null, null], [null, 'B2_6', null]],
                'B2_6'  => [['periodic_paralysis_myasthenia', null, null], ['severe_paralysis_toxin_botulism', null, null]],
                'B3'    => [[null, 'B3_1', null], [null, 'B4', null]],
                'B3_1'  => [['transient_ischemic_attack', null, null], ['hemiplegia_stroke', null, null]],
                'B4'    => [[null, 'B4_1', null], [null, 'B5', null]],
                'B4_1'  => [['polio_limb', null, null], [null, 'B4_2', null]],
                'B4_2'  => [['beriberi', null, null], [null, 'B4_3', null]],
                'B4_3'  => [['nerve_root_compression', null, null], [null, 'B4_4', null]],
                'B4_4'  => [['periodic_paralysis_mild', null, null], [null, 'B4_5', null]],
                'B4_5'  => [['hypokalemia_diuretic', null, null], ['other_limb_weakness_causes', null, null]],
                'B5'    => [[null, 'B5_1', null], [null, 'B6', null]],
                'B5_1'  => [['acoustic_neuroma', null, null], ['bells_palsy', null, null]],
                'B6'    => [[null, 'B6_1', null], ['ptosis_other_anxiety', null, null]],
                'B6_1'  => [['snake_bite_ptosis', null, null], [null, 'B6_2', null]],
                'B6_2'  => [['botulism_canned', null, null], [null, 'B6_3', null]],
                'B6_3'  => [['congenital_ptosis', null, null], [null, 'B6_4', null]],
                'B6_4'  => [['cluster_headache', null, null], [null, 'B6_5', null]],
                'B6_5'  => [['myasthenia_gravis_eye', null, null], ['ptosis_investigate_cause', null, null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1, $yes[2]);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2, $no[2]);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'snake_bite_paralysis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
งูเห่า/งูอ่างแกะ (221)
• ฉีดเซรุ่มแก้พิษงู
• ช่วยหายใจถ้าหยุดหายใจ
⊕ ด่วน
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'spinal_cord_injury' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
ไขสันหลังได้รับบาดเจ็บ (85)
⊕ ด่วน
• ช่วยหายใจถ้าหยุดหายใจ
NOTE,
                    'refs'       => ['85'],
                    'diagrams'   => [],
                ],
                'polio_myelitis_gbs' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
โปลิโอ (63)/ไขสันหลังอักเสบ (84)/กลุ่มอาการกิแลงบาร์เร (ดู "โรคที่ 63")
⊕ ด่วน
• ช่วยหายใจถ้าหยุดหายใจ
NOTE,
                    'refs'       => ['63', '84'],
                    'diagrams'   => [],
                ],
                'gnathostomiasis_spinal' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
ตัวจี๊ดเข้าไขสันหลัง (236)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['236'],
                    'diagrams'   => [],
                ],
                'spinal_cord_tumor' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
เนื้องอกไขสันหลัง (86)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['86'],
                    'diagrams'   => [],
                ],
                'periodic_paralysis_myasthenia' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
อัมพาตครั้งคราว (78)/ไมแอสทีเนียเกรวิส (79)
⊕ ภายใน 24 ชั่วโมง
⊕ ด่วน ถ้ามีอาการหายใจลำบาก
NOTE,
                    'refs'       => ['78', '79'],
                    'diagrams'   => [],
                ],
                'severe_paralysis_toxin_botulism' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
⊕ ด่วน ถ้ามีอาการหายใจลำบาก หรืออัมพาตหมดทั้งแขนขา 4 ข้าง
อาจเป็นโบทูลิซึม (67.1)/พิษปลาปักเป้า/แมงดาทะเล (219.1)/พิษปลาทะเล (219.2)/พิษหอยทะเล (219.3)
NOTE,
                    'refs'       => ['67.1', '219.1', '219.2', '219.3'],
                    'diagrams'   => [],
                ],
                'transient_ischemic_attack' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => <<<NOTE
โรคสมองขาดเลือดชั่วขณะ/ทีไอเอ (76)
⊕ ภายใน 24 ชั่วโมง
NOTE,
                    'refs'       => ['76'],
                    'diagrams'   => [],
                ],
                'hemiplegia_stroke' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
โรคลมอัมพาต/อัมพาตครึ่งซีก (76)
⊕ ด่วน
NOTE,
                    'refs'       => ['76'],
                    'diagrams'   => [],
                ],
                'polio_limb' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
โปลิโอ (63)
⊕ ภายใน 3 วัน
NOTE,
                    'refs'       => ['63'],
                    'diagrams'   => [],
                ],
                'beriberi' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
โรคเหน็บชา (132)
• วิตามินบี 1 หรือบีรวม
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['132'],
                    'diagrams'   => [],
                ],
                'nerve_root_compression' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
รากประสาทถูกกด (106)/กระดูกคอกระดูกหลังทับประสาท (108.1)
⊕ ภายใน 1 สัปดาห์
NOTE,
                    'refs'       => ['106', '108.1'],
                    'diagrams'   => [],
                ],
                'periodic_paralysis_mild' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
อัมพาตครั้งคราว (78)/ไมแอสทีเนียเกรวิส (79)
⊕ ภายใน 3 วัน
⊕ ด่วน ถ้ามีอาการหายใจลำบาก
NOTE,
                    'refs'       => ['78', '79'],
                    'diagrams'   => [],
                ],
                'hypokalemia_diuretic' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ภาวะโพแทสเซียมในเลือดต่ำ (78)
• โพแทสเซียมคลอไรด์
⊕ ถ้าไม่ดีขึ้นใน 1 สัปดาห์
NOTE,
                    'refs'       => ['78'],
                    'diagrams'   => [],
                ],
                'other_limb_weakness_causes' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1 สัปดาห์
อาจเป็นเนื้องอกสมอง (83)/เนื้องอกไขสันหลัง (86)/ปลายประสาทอักเสบ (87)/ตะกั่วเป็นพิษ (220)/อื่นๆ
NOTE,
                    'refs'       => ['83', '86', '87', '220'],
                    'diagrams'   => [],
                ],
                'acoustic_neuroma' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
เนื้องอกประสาทหู (164.2)
⊕ ภายใน 3 วัน
NOTE,
                    'refs'       => ['164.2'],
                    'diagrams'   => [],
                ],
                'bells_palsy' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
อัมพาตเบลล์/อัมพาตใบหน้าครึ่งซีก (77)
• เพร็ดนิโซโลน (ย12)
⊕ ถ้าไม่ดีขึ้นใน 2-3 สัปดาห์
NOTE,
                    'refs'       => ['77'],
                    'diagrams'   => [],
                ],
                'snake_bite_ptosis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
งูเห่า/งูอ่างแกะ (221)
• ฉีดเซรุ่มแก้พิษงู
⊕ ด่วน
NOTE,
                    'refs'       => ['221'],
                    'diagrams'   => [],
                ],
                'botulism_canned' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ด่วน',
                    'note'       => <<<NOTE
โบทูลิซึม (67.1)
⊕ ด่วน
NOTE,
                    'refs'       => ['67.1'],
                    'diagrams'   => [],
                ],
                'congenital_ptosis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
ความผิดปกติแต่กำเนิด
• ไม่มีอันตรายอะไร อาจแก้ไขได้ด้วยการผ่าตัด
NOTE,
                    'refs'       => [],
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
                'myasthenia_gravis_eye' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => <<<NOTE
ไมแอสทีเนียเกรวิส (79)
⊕ ภายใน 3 วัน
NOTE,
                    'refs'       => ['79'],
                    'diagrams'   => [],
                ],
                'ptosis_investigate_cause' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 1-2 สัปดาห์',
                    'note'       => <<<NOTE
⊕ ภายใน 1-2 สัปดาห์ เพื่อตรวจหาสาเหตุ
NOTE,
                    'refs'       => [],
                    'diagrams'   => [],
                ],
                'ptosis_other_anxiety' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => <<<NOTE
⊕ ถ้ามีความผิดปกติอื่นๆ หรือมีความวิตกกังวล
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
                    'medical_reference' => 'แผนภูมิที่ 19',
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

        $this->command->info('สร้างแผนภูมิที่ 19 (อัมพาต/หนังตาตก) กรอบ 1-6 สำเร็จ');
    }
}
