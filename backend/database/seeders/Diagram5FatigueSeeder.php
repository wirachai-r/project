<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Diagram5FatigueSeeder extends Seeder
{
    private const DIAGRAM_ID = '00005';

    public function run(): void
    {
        $now = Carbon::now();

        DB::transaction(function () use ($now) {
            $frameNumbers = ['1', '2', '3', '4', '5', '5.1', '6', '7', '8', '9', '10', '11', '12'];
            if (DB::table('question_boxes')
                ->where('diagram_id', self::DIAGRAM_ID)
                ->whereIn('frame_number', $frameNumbers)
                ->exists()) {
                throw new RuntimeException(
                    'แผนภูมิที่ 5 ถูก seed แล้ว กรุณารัน Diagram5FatigueRollbackSeeder ก่อน seed ใหม่'
                );
            }

            DB::table('diagrams')->where('diagram_id', self::DIAGRAM_ID)->update([
                'diagram_name' => 'อ่อนเพลีย',
                'diagram_name_en' => 'Fatigue / Tiredness',
                'description' => 'มีความรู้สึกอ่อนเพลีย เหนื่อยง่าย ไม่กระปรี้กระเปร่าหรือดูซึมผิดปกติ',
                'status' => '1',
                'updated_at' => $now,
            ]);

            $boxes = [
                'B1' => ['frame' => '1', 'type' => 'S', 'q' => 'แขนขาอ่อนแรง หรือ อัมพาต?'],
                'B2' => ['frame' => '2', 'type' => 'S', 'q' => 'น้ำหนักลด? หรือ น้ำหนักเพิ่ม?'],
                'B3' => ['frame' => '3', 'type' => 'M', 'q' => 'มีอาการอย่างใดอย่างหนึ่งดังต่อไปนี้?', 'min' => 1],
                'B4' => ['frame' => '4', 'type' => 'S', 'q' => 'ความดันโลหิตช่วงบน ≥ 140 มม.ปรอท หรือช่วงล่าง ≥ 90 มม.ปรอท?'],
                'B5' => ['frame' => '5', 'type' => 'S', 'q' => 'ปวดเสียดชายโครงข้างขวา? หรือ ตับโต?'],
                'B5_1' => ['frame' => '5.1', 'type' => 'S', 'q' => 'มีจุดแดงรูปแมงมุมที่หน้าอก/ต้นแขน? หรือ ฝ่ามือแดง?'],
                'B6' => ['frame' => '6', 'type' => 'S', 'q' => 'ประจำเดือนขาดในหญิงอายุ 15-45 ปี?'],
                'B7' => ['frame' => '7', 'type' => 'S', 'q' => 'เพิ่งหายจากไข้หวัด ไข้หวัดใหญ่ หรือการเจ็บป่วยอื่นๆ?'],
                'B8' => ['frame' => '8', 'type' => 'S', 'q' => 'นอนไม่พอ? พักผ่อนไม่พอ? หรือ ตรากตรำงานหนัก?'],
                'B9' => ['frame' => '9', 'type' => 'S', 'q' => 'กลางวันง่วงนอนง่าย? และกลางคืนนอนกรนมาก?'],
                'B10' => ['frame' => '10', 'type' => 'S', 'q' => 'คิดมาก กังวลใจ เสียใจหรือกลุ้มใจ? นอนไม่หลับ? หรือ มีอารมณ์ซึมเศร้า?'],
                'B11' => ['frame' => '11', 'type' => 'S', 'q' => 'ออกร้อนซู่ซ่าตามผิวกาย หรือเหงื่อออกตอนกลางคืน ในหญิงวัยหมดประจำเดือน (40-55 ปี)?'],
                'B12' => ['frame' => '12', 'type' => 'S', 'q' => 'ดูแลตนเองเบื้องต้น', 'detail' => '• พักผ่อนให้มากขึ้น\n• หาเวลาทำงานอดิเรกที่ใจรัก\n• ออกกำลังกายเพิ่มขึ้นทีละน้อย\n⊕ ถ้าไม่หายเพลียใน 1-2 สัปดาห์ หรือมีอาการเปลี่ยนแปลงที่ไม่ดี เช่น น้ำหนักลด ซีด ดีซ่าน บวม ให้พบแพทย์'],
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

            $binary = [
                'B1' => [['refer_paralysis', null], [null, 'B2']],
                'B2' => [['refer_weight', null], [null, 'B3']],
                'B4' => [['hypertension', null], [null, 'B5']],
                'B5' => [[null, 'B5_1'], [null, 'B6']],
                'B5_1' => [['cirrhosis', null], ['liver_other', null]],
                'B6' => [['pregnancy', null], [null, 'B7']],
                'B7' => [['recovery', null], [null, 'B8']],
                'B8' => [['insufficient_rest', null], [null, 'B9']],
                'B9' => [['sleep_apnea', null], [null, 'B10']],
                'B10' => [['mental_health', null], [null, 'B11']],
                'B11' => [['menopause', null], [null, 'B12']],
            ];
            foreach ($binary as $boxKey => [$yes, $no]) {
                $addChoice($boxKey, 'ใช่', $yes[1], $yes[0], 1);
                $addChoice($boxKey, 'ไม่', $no[1], $no[0], 2);
            }

            $checklist = ['ไข้', 'ซีด', 'ดีซ่าน', 'ใจสั่น', 'เหงื่อออกมาก', 'หอบ', 'บวม', 'เจ็บหน้าอก', 'ปวดท้อง', 'ท้องเดิน', 'ปวดศีรษะ', 'เวียนศีรษะ', 'อาเจียน'];
            foreach ($checklist as $index => $label) $addChoice('B3', $label, null, 'associated_symptoms', $index + 1);
            DB::table('question_boxes')->where('box_id', $boxes['B3']['id'])->update([
                'yes_next_box_id' => null,
                'no_next_box_id' => $boxes['B4']['id'],
            ]);
            $addChoice('B12', 'รับทราบ', null, 'self_care', 1);

            $rules = [
                'refer_paralysis' => ['Y', 'ดูแผนภูมิที่ 19 อัมพาต', [], ['00019']],
                'refer_weight' => ['G', 'ดูแผนภูมิที่ 6 น้ำหนักลด และ 7 น้ำหนักมากหรืออ้วน', [], ['00006', '00007']],
                'associated_symptoms' => ['Y', 'ดูแผนภูมิตามอาการที่พบร่วม', [], []],
                'hypertension' => ['Y', 'ควบคุมความดันโลหิต ลดอาหารเค็ม และใช้ยาลดความดันตามคำแนะนำ', ['92'], []],
                'cirrhosis' => ['Y', 'ตับแข็ง: รักษาตามอาการ ห้ามดื่มแอลกอฮอล์', ['44'], []],
                'liver_other' => ['Y', 'ภายใน 1 สัปดาห์ อาจเป็นตับอักเสบ/มะเร็งตับ/สาเหตุอื่น', ['38', '45'], []],
                'pregnancy' => ['G', 'ตรวจปัสสาวะ แนะนำฝากครรภ์ และระวังการใช้ยา', ['154'], []],
                'recovery' => ['G', 'ระยะฟื้นไข้: บำรุงด้วยอาหารที่มีประโยชน์ นอนหลับให้เพียงพอ', [], []],
                'insufficient_rest' => ['G', 'ร่างกายพักผ่อนไม่พอ ควรพักและนอนหลับให้เพียงพอ', [], []],
                'sleep_apnea' => ['Y', 'ภาวะหยุดหายใจขณะหลับ: ควรพบแพทย์ภายใน 1 เดือน/ความดันโลหิตสูง', ['31.1'], []],
                'mental_health' => ['G', 'โรควิตกกังวล/โรคกังวลทั่วไป/โรคอารมณ์แปรปรวน/โรคซึมเศร้า รักษาตามอาการ', ['88', '88.2'], []],
                'menopause' => ['G', 'โรคของหญิงวัยหมดประจำเดือน: รักษาตามอาการ', ['129'], []],
                'self_care' => ['G', $boxes['B12']['detail'], [], []],
            ];

            $ruleNumber = ((int) DB::table('diagnosis_rules')->max('rule_id')) + 1;
            $conditionNumber = ((int) DB::table('rule_conditions')->max('condition_id')) + 1;
            foreach ($rules as $key => [$urgency, $note, $refs, $nextDiagrams]) {
                $ruleId = str_pad((string) $ruleNumber, 10, '0', STR_PAD_LEFT);
                DB::table('diagnosis_rules')->updateOrInsert(['rule_id' => $ruleId], [
                    'urgency_level' => $urgency,
                    'time_frame' => null,
                    'time_frame_en' => null,
                    'note' => $note,
                    'note_en' => null,
                    'medical_reference' => 'แผนภูมิที่ 5',
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
                foreach ($refs as $order => $reference) {
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
                foreach ($nextDiagrams as $order => $diagramId) DB::table('rule_next_diagrams')->insert([
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

        $this->command->info('สร้างแผนภูมิที่ 5 (อ่อนเพลีย) กรอบ 1-12 สำเร็จ');
    }
}
