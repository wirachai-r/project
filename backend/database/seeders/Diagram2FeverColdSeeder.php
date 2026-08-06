<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram2FeverColdSeeder extends Seeder
{
    private const DIAGRAM_ID = '00002';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '11.1', '12', '13'];
            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 2 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ไข้ร่วมกับน้ำมูกหรือไอ',
                'diagram_name_en' => 'Fever with Rhinorrhea or Cough',
                'description' => 'ตัวร้อน อุณหภูมิของร่างกายสูงกว่า 37.2°C โดยการวัดทางปาก และมีน้ำมูกไหลหรือไอ',
                'status' => '1',
                'updated_at' => $now,
            ]);

            $boxes = [
                'B1'    => ['frame' => '1',    'type' => 'S', 'q' => 'หอบ (หายใจลำบาก)?'],
                'B2'    => ['frame' => '2',    'type' => 'S', 'q' => 'เจ็บหน้าอกมาก? หรือ เจ็บแปลบที่หน้าอกเวลาหายใจเข้าลึก?'],
                'B3'    => ['frame' => '3',    'type' => 'S', 'q' => 'น้ำหนักลดฮวบ? หรือ มีไข้เกิน 7 วัน?'],
                'B4'    => ['frame' => '4',    'type' => 'S', 'q' => 'ไอเป็นเลือด?'],
                'B5'    => ['frame' => '5',    'type' => 'S', 'q' => 'ไอมีเสมหะเหลืองหรือเขียว? หรือ ใช้เครื่องฟังปอดมีเสียงกรอบแกรบ (crepitation) หรือเสียงฮี้ด (rhonchi)?'],
                'B6'    => ['frame' => '6',    'type' => 'S', 'q' => 'ไอเสียงก้อง? และหายใจเข้าลึกมีเสียงฮี้ด (stridor)?'],
                'B7'    => ['frame' => '7',    'type' => 'S', 'q' => 'ปวดหู? หรือ หูอื้อ?'],
                'B8'    => ['frame' => '8',    'type' => 'S', 'q' => 'กดเจ็บบริเวณไซนัส?'],
                'B9'    => ['frame' => '9',    'type' => 'S', 'q' => 'ทอนซิลโตแดงหรือเป็นหนอง?'],
                'B10'   => ['frame' => '10',   'type' => 'S', 'q' => 'พบในเด็กที่ไม่ได้ฉีดวัคซีนดีพีที และมีประวัติอยู่ใกล้ชิดกับเด็กที่เป็นไอกรน?'],
                'B11'   => ['frame' => '11',   'type' => 'S', 'q' => 'ไข้สูงตลอดเวลา?'],
                'B11_1' => ['frame' => '11.1', 'type' => 'S', 'q' => 'มีน้ำตาไหล เยื่อบุตาแดง? มีผื่นขึ้น? หรือ พบจุด Koplik ในกระพุ้งแก้ม?'],
                'B12'   => ['frame' => '12',   'type' => 'S', 'q' => 'น้ำมูกข้นเหลืองหรือเขียว เกิน 24 ชั่วโมง?'],
                'B13'   => ['frame' => '13',   'type' => 'S', 'q' => 'ปวดเมื่อยมาก? หรือ มีประวัติสัมผัสผู้ป่วยไข้หวัดใหญ่?'],
            ];

            $nextBoxId = ((int) DB::table('question_boxes')->max('box_id')) + 1;
            foreach ($boxes as &$box) {
                $box['id'] = str_pad((string) $nextBoxId++, 10, '0', STR_PAD_LEFT);
            }
            unset($box);

            foreach ($boxes as $box) {
                DB::table('question_boxes')->updateOrInsert(['box_id' => $box['id']], [
                    'frame_number' => $box['frame'],
                    'question_text' => $box['q'],
                    'question_text_en' => null,
                    'question_type' => $box['type'],
                    'min_required' => $box['min'] ?? null,
                    'detail' => $box['detail'] ?? null,
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);
            }

            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'entry_box_id' => $boxes['B1']['id'],
            ]);

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
                if ($ruleKey) $terminalChoices[$ruleKey][] = ['box_id' => $boxes[$boxKey]['id'], 'choice_id' => $choiceId];
            };

            // ทุกกรอบเป็น S (single choice) ไม่มี checklist ในแผนภูมินี้
            // กรอบ 14 (รักษาตามอาการ) ไม่ใช่คำถาม จึงผูกเป็น terminal note ของ B13 "ไม่" โดยตรง
            $binary = [
                'B1'    => [['refer_dyspnea', null], [null, 'B2']],
                'B2'    => [['chest_pain_severe', null], [null, 'B3']],
                'B3'    => [['weight_loss_prolonged_fever', null], [null, 'B4']],
                'B4'    => [['refer_hemoptysis', null], [null, 'B5']],
                'B5'    => [['bronchitis_early_pneumonia', null], [null, 'B6']],
                'B6'    => [['croup', null], [null, 'B7']],
                'B7'    => [['otitis_media', null], [null, 'B8']],
                'B8'    => [['sinusitis', null], [null, 'B9']],
                'B9'    => [['tonsillitis', null], [null, 'B10']],
                'B10'   => [['pertussis', null], [null, 'B11']],
                'B11'   => [[null, 'B11_1'], [null, 'B12']],
                'B11_1' => [['measles', null], ['fever_symptomatic_care', null]],
                'B12'   => [['cold_with_complication', null], [null, 'B13']],
                'B13'   => [['influenza', null], ['common_cold_symptomatic', null]],
            ];
            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            $rules = [
                'refer_dyspnea' => [
                    'urgency'    => 'R',
                    'time_frame' => null,
                    'note'       => 'ดูแผนภูมิที่ 3 ไข้ร่วมกับหอบ กรอบที่ (1)',
                    'refs'       => [],
                    'diagrams'   => ['00003'],
                ],
                'chest_pain_severe' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 24 ชั่วโมง',
                    'note'       => implode("\n", [
                        'ภายใน 24 ชั่วโมง อาจเป็น',
                        '• ปอดอักเสบ (19)',
                        '• วัณโรคปอด (14)',
                        '• ภาวะมีหนองในโพรงเยื่อหุ้มปอด (20)',
                    ]),
                    'refs'       => ['19', '14', '20'],
                    'diagrams'   => [],
                ],
                'weight_loss_prolonged_fever' => [
                    'urgency'    => 'P',
                    'time_frame' => 'ภายใน 3 วัน',
                    'note'       => implode("\n", [
                        'ภายใน 3 วัน อาจเป็น',
                        '• วัณโรคปอด (14)',
                        '• มะเร็งปอด (237)',
                        '• สาเหตุร้ายแรงอื่นๆ',
                    ]),
                    'refs'       => ['14', '237'],
                    'diagrams'   => [],
                ],
                'refer_hemoptysis' => [
                    'urgency'    => 'P',
                    'time_frame' => null,
                    'note'       => 'ดูแผนภูมิที่ 38 ไอ กรอบที่ (3.1)',
                    'refs'       => [],
                    'diagrams'   => ['00038'],
                ],
                'bronchitis_early_pneumonia' => [
                    'urgency'    => 'Y',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'หลอดลมอักเสบเฉียบพลัน (15)/ปอดอักเสบ (19) ระยะแรก',
                        '• พาราเซตามอล (ย1.2)',
                        '• เพนิซิลลินวี (ย4.1) หรืออะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.4)',
                        '⊕ ถ้าไม่ดีขึ้นใน 48 ชั่วโมงหรือมีอาการหอบตามมา',
                    ]),
                    'refs'       => ['15', '19'],
                    'diagrams'   => [],
                ],
                'croup' => [
                    'urgency'    => 'Y',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'ครูป (11)',
                        '• พาราเซตามอล (ย1.2)',
                        '• ดื่มน้ำมากๆ',
                        '• สูดไอน้ำอุ่น',
                        '⊕ ถ้ามีอาการหอบ/กลืนลำบาก/ซึม/กระสับกระส่าย/ขาดน้ำ/มีแผ่นเยื่อสีเทาหรือเหลืองปนเทาในลำคอ',
                    ]),
                    'refs'       => ['11'],
                    'diagrams'   => [],
                ],
                'otitis_media' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'หูชั้นกลางอักเสบเฉียบพลัน (163)',
                        '• ยาลดไข้ (ย1)',
                        '• อะม็อกซีซิลลิน (ย4.2) หรือโคไตรม็อกซาโซล (ย4.7) หรืออีริโทรไมซิน (ย4.4)',
                        '⊕ ถ้าไม่ดีขึ้นใน 3 วัน',
                    ]),
                    'refs'       => ['163'],
                    'diagrams'   => [],
                ],
                'sinusitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => 'ไซนัสอักเสบเฉียบพลัน (26)',
                    'refs'       => ['26'],
                    'diagrams'   => [],
                ],
                'tonsillitis' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'ทอนซิลอักเสบ (8)',
                        '• ยาลดไข้ (ย1)',
                        '• เพนิซิลลินวี (ย4.1) หรืออะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.4)',
                        '⊕ ถ้าไม่ดีขึ้นใน 3 วัน ถ้าดีขึ้นกินยาปฏิชีวนะจนครบ 10 วัน',
                    ]),
                    'refs'       => ['8'],
                    'diagrams'   => [],
                ],
                'pertussis' => [
                    'urgency'    => 'Y',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'ไอกรน (13) ระยะแรก',
                        '• ยาลดไข้ (ย1)',
                        '• อีริโทรไมซิน (ย4.4)',
                        '⊕ ถ้าไข้ไม่ลดใน 4 วัน หรือมีอาการหอบ',
                    ]),
                    'refs'       => ['13'],
                    'diagrams'   => [],
                ],
                'measles' => [
                    'urgency'    => 'Y',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'หัด (3)',
                        '• พาราเซตามอล (ย1.2)',
                        '⊕ ถ้าไม่ดีขึ้นใน 4 วัน หรือหอบ ชัก/ไม่ค่อยรู้สึกตัว/ท้องเดินรุนแรง',
                    ]),
                    'refs'       => ['3'],
                    'diagrams'   => [],
                ],
                'fever_symptomatic_care' => [
                    'urgency'    => 'Y',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'รักษาตามอาการ',
                        '• พาราเซตามอล (ย1.2)',
                        '⊕ ถ้าไม่ดีขึ้นใน 4 วัน หรือหอบ/ตับโต/ปวดท้องมาก/อาเจียนมาก/สงสัยไข้เลือดออก (225)',
                    ]),
                    'refs'       => ['225'],
                    'diagrams'   => [],
                ],
                'cold_with_complication' => [
                    'urgency'    => 'Y',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'ไข้หวัด (1) ที่มีภาวะแทรกซ้อน',
                        '• พาราเซตามอล (ย1.2)',
                        '• เพนิซิลลินวี (ย4.1) หรืออะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.4)',
                        '⊕ ถ้าไข้ไม่ลดใน 4 วัน หรือหอบ/เจ็บหน้าอกมากหรือมีประวัติสัมผัสสัตว์ปีกที่ป่วยหรือตาย/สัมผัสผู้ป่วยไข้หวัดนก (240)',
                    ]),
                    'refs'       => ['1', '240'],
                    'diagrams'   => [],
                ],
                'influenza' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => 'ไข้หวัดใหญ่ (2)',
                    'refs'       => ['2'],
                    'diagrams'   => [],
                ],
                'common_cold_symptomatic' => [
                    'urgency'    => 'G',
                    'time_frame' => null,
                    'note'       => implode("\n", [
                        'ไข้หวัด (1)',
                        '• นอนพักผ่อน',
                        '• ห้ามอาบน้ำเย็น',
                        '• พาราเซตามอล (ย1.2)',
                        '• ถ้ามีน้ำมูกมาก เด็กอายุต่ำกว่า 5 ปี ใช้ลูกยางดูดหรือคอยเช็ดออก เด็กโตหรือผู้ใหญ่ ให้คลอร์เฟนิรามีน (ย7.1) เพียง 2-3 วัน เมื่อน้ำมูกแห้งแล้วหยุดกิน',
                        '• ถ้าไอเพียงเล็กน้อย ให้จิบน้ำอุ่นบ่อยๆ',
                        '• ถ้าไอมาก ให้ดื่มน้ำอุ่นมากๆ จิบน้ำผึ้งผสมมะนาว (มะนาว 1 ส่วน น้ำผึ้ง 4 ส่วน) หรือยาแก้ไอ (ย9)',
                        '• ดื่มน้ำมากๆ',
                        '• ถ้าเบื่ออาหาร กินน้ำหวาน ข้าวต้ม',
                        '• ถ้ามีน้ำมูกเพียงเล็กน้อย คอยเช็ดออก',
                        '• ถ้าน้ำมูกเปลี่ยนเป็นสีเหลืองหรือเขียวเกิน 24 ชั่วโมง ให้เพนิซิลลินวี (ย4.1) หรืออะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.4)',
                        '• ใช้ผ้าชุบน้ำเช็ดตัวบ่อยๆ',
                        '• งดสูบบุหรี่',
                        '⊕ ถ้าไข้ไม่ลดใน 4 วัน หรือหอบ/เจ็บหน้าอกมาก หรือมีประวัติสัมผัสสัตว์ปีกที่ป่วยหรือตาย/สัมผัสผู้ป่วยไข้หวัดนก (240)',
                    ]),
                    'refs'       => ['1', '240'],
                    'diagrams'   => [],
                ],
            ];

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
                    'medical_reference' => 'แผนภูมิที่ 2',
                    'status' => '1',
                    'diagram_id' => self::DIAGRAM_ID,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]);

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

                DB::table('rule_diseases')->where('rule_id', $ruleId)->delete();
                foreach ($rule['refs'] as $order => $reference) {
                    $diseaseId = DB::table('diseases')->where('reference', $reference)->value('disease_id');
                    if ($diseaseId) DB::table('rule_diseases')->insert([
                        'rule_id' => $ruleId,
                        'disease_id' => $diseaseId,
                        'display_order' => $order,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('rule_next_diagrams')->where('rule_id', $ruleId)->delete();
                foreach ($rule['diagrams'] as $order => $diagramId) DB::table('rule_next_diagrams')->insert([
                    'rule_id' => $ruleId,
                    'diagram_id' => $diagramId,
                    'display_order' => $order,
                    'prompt_text' => 'ต้องการประเมินอาการนี้ต่อหรือไม่',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $ruleNumber++;
            }
        });

        $this->command->info('สร้างแผนภูมิที่ 2 (ไข้ร่วมกับน้ำมูกหรือไอ) กรอบ 1-13 (รวม 11.1) สำเร็จ');
    }
}
