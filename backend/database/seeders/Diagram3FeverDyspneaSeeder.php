<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram3FeverDyspneaSeeder extends Seeder
{
    private const DIAGRAM_ID = '00003';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = ['1', '2', '3', '4', '4.1', '5', '5.1', '5.2'];
            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 3 ถูก seed แล้ว กรุณารัน Rollback ก่อน seed ใหม่'
                );
            }

            // 1. อัปเดตข้อมูลหัวแผนภูมิที่ 3
            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'ไข้ร่วมกับหอบ',
                'diagram_name_en' => 'Fever with Dyspnea',
                'description' => 'ตัวร้อน อุณหภูมิของร่างกายสูงกว่า 37.2°C โดยการวัดทางปาก และมีอาการหอบ (หายใจขัด หายใจลำบาก จมูกบาน คอบุ๋ม ซี่โครงบุ๋ม) หรือหายใจเร็วกว่าปกติ',
                'status' => '1',
                'updated_at' => $now,
            ]);

            // 2. กำหนดรายการคำถาม (Boxes) ในแผนภูมิที่ 3
            $boxes = [
                'B1'   => ['frame' => '1',   'type' => 'S', 'q' => 'ปอดข้างหนึ่งเคาะทึบ (dullness) และใช้เครื่องฟังปอดไม่ได้ยินเสียงหายใจ (absence of breath sound)?'],
                'B2'   => ['frame' => '2',   'type' => 'S', 'q' => 'น้ำหนักลดฮวบ? หรือ มีไข้เกิน 7 วัน?'],
                'B3'   => ['frame' => '3',   'type' => 'S', 'q' => 'ใช้เครื่องฟังปอดมีเสียงกรอบแกรบ (crepitation)? หรือ เสียงหายใจค่อย (decreased breath sound) เฉพาะแห่ง?'],
                'B4'   => ['frame' => '4',   'type' => 'S', 'q' => 'ไอเสียงก้อง? และ หายใจเข้ามีเสียงดังฮี้ด (stridor)?'],
                'B4_1' => ['frame' => '4.1', 'type' => 'S', 'q' => 'มีแผ่นเยื่อสีเทา/เหลืองปนเทาในลำคอ?'],
                'B5'   => ['frame' => '5',   'type' => 'S', 'q' => 'ใช้เครื่องฟังปอดมีเสียงวี้ด (wheezing)? หรือ พ่นหรือฉีดยาขยายหลอดลมแล้วทุเลา?'],
                'B5_1' => ['frame' => '5.1', 'type' => 'S', 'q' => 'มีประวัติไอเรื้อรัง เป็นโรคหืด ถุงลมปอดโป่งพอง หรือหลอดลมพองมาก่อน?'],
                'B5_2' => ['frame' => '5.2', 'type' => 'S', 'q' => 'พบในเด็กอายุต่ำกว่า 2 ปี?'],
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
                'B1'   => [['empyema_pleural_effusion', null], [null, 'B2']],
                'B2'   => [['tb_cancer_melioidosis', null], [null, 'B3']],
                'B3'   => [['pneumonia_bronchiolitis', null], [null, 'B4']],
                'B4'   => [[null, 'B4_1'], [null, 'B5']],
                'B4_1' => [['diphtheria', null], ['croup_urgent', null]],
                'B5'   => [[null, 'B5_1'], ['severe_lung_infection', null]],
                'B5_1' => [['asthma_copd_bronchiectasis', null], [null, 'B5_2']],
                'B5_2' => [['acute_bronchiolitis', null], ['acute_bronchitis', null]],
            ];

            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่ใช่', $no[1], $no[0], 2);
            }

            // 4. กำหนดกฎการวินิจฉัยและการรักษา (Diagnosis Rules)
            $rules = [
                'empyema_pleural_effusion' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 30-60 นาที',
                    'note'       => "ภาวะมีหนองหรือน้ำในโพรงเยื่อหุ้มปอด (20)\n⊕ ด่วน",
                    'refs'       => ['20'],
                ],
                'tb_cancer_melioidosis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 30-60 นาที',
                    'note'       => implode("\n", [
                        '⊕ ด่วน อาจเป็น',
                        '• วัณโรคปอด (14)',
                        '• มะเร็งปอด (237)',
                        '• เมลิออยโดซิส (229.2)',
                        '• สาเหตุร้ายแรงอื่นๆ',
                    ]),
                    'refs'       => ['14', '237', '229.2'],
                ],
                'pneumonia_bronchiolitis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 30-60 นาที',
                    'note'       => implode("\n", [
                        'ปอดอักเสบ (19)/หลอดลมฝอยอักเสบ (18)',
                        '⊕ ด่วน',
                    ]),
                    'refs'       => ['19', '18'],
                ],
                'diphtheria' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 30-60 นาที',
                    'note'       => "คอตีบ (10)\n⊕ ด่วน",
                    'refs'       => ['10'],
                ],
                'croup_urgent' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 30-60 นาที',
                    'note'       => "ครูป (11)\n⊕ ด่วน",
                    'refs'       => ['11'],
                ],
                'severe_lung_infection' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 30-60 นาที',
                    'note'       => implode("\n", [
                        '⊕ ด่วน อาจเป็นภาวะติดเชื้อรุนแรงในปอด',
                        '(ดู "โรคที่ 18, 19, 20")/แอนแทรกซ์ (229.3)/เมลิออยโดซิส (229.2)/ซาร์ส (239)/ไข้หวัดนก (240) ภาวะสิ่งหลุดอุดตันหลอดเลือดแดงปอด (ดู "โรคที่ 99.1")/เยื่อหุ้มหัวใจอักเสบ (pericarditis)',
                    ]),
                    'refs'       => ['18', '19', '20', '229.3', '229.2', '239', '240', '99.1'],
                ],
                'asthma_copd_bronchiectasis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 30-60 นาที',
                    'note'       => implode("\n", [
                        'หืด (24)/ถุงลมปอดโป่งพอง (16)/หลอดลมพอง (17) ที่มีการติดเชื้อแทรกซ้อน',
                        '• พาราเซตามอล (ย1.2)',
                        '• ยาขยายหลอดลม (ย10.3)',
                        '• อะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.4)',
                        '⊕ ถ้าไม่ดีขึ้นใน 24 ชั่วโมง หรือหอบมาก/ตัวเขียว/ขาดน้ำ',
                    ]),
                    'refs'       => ['24', '16', '17'],
                ],
                'acute_bronchiolitis' => [
                    'urgency'    => 'R',
                    'time_frame' => 'ภายใน 30-60 นาที',
                    'note'       => implode("\n", [
                        'หลอดลมฝอยอักเสบ (18)',
                        '⊕ ด่วน',
                        '• พาราเซตามอล (ย1.2)',
                        '• ยาขยายหลอดลม (ย10.3)',
                        '• อะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.4)',
                        '⊕ ถ้าไม่ดีขึ้นใน 24 ชั่วโมง หรือหอบมาก/ตัวเขียว/ขาดน้ำ',
                    ]),
                    'refs'       => ['18'],
                ],
                'acute_bronchitis' => [
                    'urgency'    => 'Y',
                    'time_frame' => 'ภายใน 30-60 นาที',
                    'note'       => implode("\n", [
                        'หลอดลมอักเสบเฉียบพลัน (15)',
                        '• พาราเซตามอล (ย1.2)',
                        '• ยาขยายหลอดลม (ย10.3)',
                        '• อะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.4)',
                        '⊕ ถ้าไม่ดีขึ้นใน 24 ชั่วโมง หรือหอบมาก/ตัวเขียว/ขาดน้ำ',
                    ]),
                    'refs'       => ['15'],
                ],
            ];

            // 5. บันทึก Rules, Conditions, Disease references ลงในฐานข้อมูล
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
                    'medical_reference' => 'แผนภูมิที่ 3',
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

                $ruleNumber++;
            }
        });

        $this->command->info('สร้างแผนภูมิที่ 3 (ไข้ร่วมกับหอบ) กรอบ 1-5.2 สำเร็จ');
    }
}
