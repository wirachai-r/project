<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Diagram1FeverSeeder
 * แผนภูมิที่ 1 — ไข้ (FEVER)
 *
 * การแก้ไข:
 * - ลบ B17 ออกจาก $boxes (ไม่ใช่คำถาม แต่เป็น terminal node)
 * - เพิ่ม rule 'viral_fever' (urgency=W) แทน
 * - เปลี่ยน choices ที่ชี้ไป B17 → next_box_id = null (จบ flow → evaluate rules)
 * - กรอบ B15 ตอบ "ใช่" → next_box_id = null (redirect ไปดูแผนภูมิอื่น ไม่มี box ใน diagram นี้)
 */
class Diagram1FeverSeeder extends Seeder
{
    private const DIAGRAM_ID = '00001';

    public function run(): void
    {
        $now = Carbon::now();

        // ============================================================
        // 0. Helper: ดึง disease_id จากชื่อภาษาอังกฤษ
        // ============================================================
        $diseaseMap = DB::table('diseases')
            ->pluck('disease_id', 'disease_name_en')
            ->toArray();

        $getDiseaseId = function (string $nameEn) use ($diseaseMap): ?string {
            foreach ($diseaseMap as $key => $id) {
                if (strtolower(trim($key)) === strtolower(trim($nameEn))) {
                    return $id;
                }
            }
            return null;
        };

        // ============================================================
        // 1. Diagram
        // ============================================================
        DB::table('diagrams')->insertOrIgnore([
            'diagram_id'      => self::DIAGRAM_ID,
            'diagram_name'    => 'ไข้',
            'diagram_name_en' => 'Fever',
            'description'     => 'อุณหภูมิร่างกายสูงกว่า 37.2°C (วัดทางปาก) หรือ 37.7°C (วัดทางทวารหนัก/หน้าผาก)',
            'status'          => '1',
            'entry_box_id'    => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // ============================================================
        // 2. Question Boxes
        // หมายเหตุ: B17 ถูกลบออกแล้ว เพราะเป็น terminal node ไม่ใช่คำถาม
        //           เมื่อ next_box_id = null และ next_diagram_id = null
        //           ระบบจะ evaluate rules โดยอัตโนมัติ
        // ============================================================
        $boxes = [
            // กรอบที่ 1 (เริ่ม)
            'B1'    => ['q' => 'ไม่ค่อยรู้สึกตัว? ปวดศีรษะมาก? อาเจียนรุนแรง? หรือ ชัก?',                                                                              'q_en' => 'Altered consciousness? Severe headache? Severe vomiting? Or seizure?'],
            // กรอบที่ 1.1
            'B1_1'  => ['q' => 'คอแข็ง? หรือ กระหม่อมโป่งตึงในเด็กเล็ก?',                                                                                              'q_en' => 'Neck stiffness? Or bulging fontanelle in young children?'],
            // กรอบที่ 1.2
            'B1_2'  => ['q' => 'เคยเข้าไปในดงมาลาเรีย หรือได้รับการถ่ายเลือดภายในระยะหลายเดือนที่ผ่านมา?',                                                             'q_en' => 'History of travel to malaria-endemic area or blood transfusion within past months?'],
            // กรอบที่ 1.3
            'B1_3'  => ['q' => 'เคยถูกสุนัขหรือแมวกัด หรือข่วน และมีอาการกลัวน้ำกลัวลม?',                                                                              'q_en' => 'History of dog/cat bite or scratch with hydrophobia or aerophobia?'],
            // กรอบที่ 1.4
            'B1_4'  => ['q' => 'หลังเผชิญคลื่นความร้อน หรือทำงาน/ออกกำลังท่ามกลางอากาศร้อน?',                                                                          'q_en' => 'After heat wave exposure or exercise in hot weather?'],
            // กรอบที่ 1.5
            'B1_5'  => ['q' => 'รู้สึกตัวดี?',                                                                                                                           'q_en' => 'Alert and well?'],
            // กรอบที่ 1.6
            'B1_6'  => ['q' => 'ขากรรไกรเกร็งแน่น หรือ ชักบ่อยเมื่อถูกเสียง/แสงสว่าง/เสียงดัง?',                                                                      'q_en' => 'Trismus? Or spasms triggered by sound, light, or noise?'],
            // กรอบที่ 1.7
            'B1_7'  => ['q' => 'พบในเด็ก 6 เดือน - 5 ปี ชักชั่วขณะหนึ่ง (ไม่เกิน 15 นาที) แล้วหยุดชักได้เอง?',                                                       'q_en' => 'Child 6mo–5yr with brief seizure (<15 min) that stopped spontaneously?'],
            // กรอบที่ 2
            'B2'    => ['q' => 'มีภาวะช็อก (เหงื่อออก ตัวเย็น กระสับกระส่าย ซีพจรเบาเร็ว และความดันเลือดตก)?',                                                        'q_en' => 'Signs of shock (sweating, cold extremities, restlessness, weak rapid pulse, hypotension)?'],
            // กรอบที่ 3
            'B3'    => ['q' => 'แขนขาอ่อนแรง หรืออัมพาตเกิดขึ้นฉับพลัน?',                                                                                              'q_en' => 'Sudden limb weakness or paralysis?'],
            // กรอบที่ 4
            'B4'    => ['q' => 'มีไข้นานเกิน 1 เดือน?',                                                                                                                  'q_en' => 'Fever lasting more than 1 month?'],
            // กรอบที่ 4.1
            'B4_1'  => ['q' => 'ไอ? และน้ำหนักลดฮวบ?',                                                                                                                   'q_en' => 'Cough? And significant weight loss?'],
            // กรอบที่ 4.2
            'B4_2'  => ['q' => 'ปวดข้อมือ 2 ข้าง? ผมร่วง? หรือมีผื่นปีกผีเสื้อที่แก้ม?',                                                                               'q_en' => 'Bilateral wrist pain? Hair loss? Or butterfly rash on cheeks?'],
            // กรอบที่ 4.3
            'B4_3'  => ['q' => 'จับไข้หนาวสั่นวนรอบ? และเคยเข้าไปในดงมาลาเรีย?',                                                                                       'q_en' => 'Cyclical fever with rigors? And history of malaria-endemic area?'],
            // กรอบที่ 4.4
            'B4_4'  => ['q' => 'มีจุดแดงที่เยื่อบุตา/ใต้เล็บ? เสียงหัวใจฟู่ (murmur)? และฉีดยาเสพติด?',                                                               'q_en' => 'Petechiae on conjunctiva/nail beds? Cardiac murmur? And IV drug use?'],
            // กรอบที่ 4.5
            'B4_5'  => ['q' => 'มีจุดแดงจ้ำเขียวขึ้นตามตัว? หรือมีก้อนบวมที่คอหรือรักแร้?',                                                                            'q_en' => 'Petechiae/purpura? Or swollen lymph nodes at neck or axilla?'],
            // กรอบที่ 5
            'B5'    => ['q' => 'มีไข้เกิน 7 วัน? หรือหนาวสั่นมาก (ฟันกระทบ/หนาวผ้าห่มฯ)?',                                                                            'q_en' => 'Fever > 7 days? Or severe rigors?'],
            // กรอบที่ 5.1
            'B5_1'  => ['q' => 'พบรอยแผลเหมือนถูกบุหรี่จี้? หรือผื่นขึ้นที่หลัง มีไข้ 5 วัน? และมีประวัติเคยเข้าไปในป่า/ดงทุ่งหญ้า/ไร่สวน ภายใน 3 สัปดาห์?',      'q_en' => 'Eschar? Or rash on back with 5-day fever? And forest/farm exposure within 3 weeks?'],
            // กรอบที่ 5.2
            'B5_2'  => ['q' => 'จับไข้หนาวสั่นวันละครั้งหรือวันเว้นวัน? และมีประวัติเดินทางเข้าดงมาลาเรีย/รับถ่ายเลือดภายในหลายเดือน?',                              'q_en' => 'Cyclic fever (daily/every-other-day)? And history of malaria-endemic area or blood transfusion?'],
            // กรอบที่ 5.3
            'B5_3'  => ['q' => 'เจ็บที่สีข้าง? และปัสสาวะขุ่น?',                                                                                                        'q_en' => 'Flank tenderness? And cloudy urine?'],
            // กรอบที่ 5.4
            'B5_4'  => ['q' => 'ปวดกล้ามเนื้อมาก? ตาแดงหรือตาเหลือง? และอาชีพที่ต้องอยู่ในน้ำ/แวดล้อมที่มีการระบาดของเลปโตสไปโรซิส?',                              'q_en' => 'Severe myalgia? Conjunctival suffusion or jaundice? And water exposure in leptospirosis area?'],
            // กรอบที่ 5.5
            'B5_5'  => ['q' => 'ไข้สูงตลอดเวลา ม้ามโต? หรืออยู่ในแวดล้อมที่มีการระบาดของไทฟอยด์?',                                                                    'q_en' => 'Continuous high fever with splenomegaly? Or in typhoid-endemic area?'],
            // กรอบที่ 6
            'B6'    => ['q' => 'หอบหรือหายใจไม่สะดวก? หรือปวดท้องรุนแรง/กดเจ็บท้องมาก?',                                                                              'q_en' => 'Difficulty breathing? Or severe/tender abdominal pain?'],
            // กรอบที่ 7
            'B7'    => ['q' => 'ซีด? ดีซ่าน? จุดแดง/จ้ำเขียว? ปวดข้อรุนแรง/ข้อบวมแดงร้อน? หรือปวดหลังรุนแรง?',                                                      'q_en' => 'Pallor? Jaundice? Petechiae/purpura? Severe joint pain/swelling? Or severe back pain?'],
            // กรอบที่ 8
            'B8'    => ['q' => 'เจ็บหน้าอกแปลบเวลาหายใจเข้าลึกๆ? เจ็บหน้าอกมาก? หรือเสียงปอดกรอบแกรบ (crepitation)?',                                               'q_en' => 'Pleuritic chest pain? Severe chest pain? Or crepitation on lung auscultation?'],
            // กรอบที่ 9
            'B9'    => ['q' => 'มีรอยแผลเหมือนถูกบุหรี่จี้?',                                                                                                            'q_en' => 'Eschar (cigarette burn-like lesion)?'],
            // กรอบที่ 9.1
            'B9_1'  => ['q' => 'มีประวัติเดินทางเข้าไปในป่า ดงทุ่งหญ้า หรือไร่สวน ภายในระยะ 3 สัปดาห์ที่ผ่านมา?',                                                    'q_en' => 'History of exposure to forest/grassland/farm within 3 weeks?'],
            // กรอบที่ 10
            'B10'   => ['q' => 'เท้าบวม 2 ข้าง?',                                                                                                                        'q_en' => 'Bilateral foot/leg edema?'],
            // กรอบที่ 10.1
            'B10_1' => ['q' => 'ปัสสาวะสีแดง/สีเหมือนน้ำล้างเนื้อ?',                                                                                                    'q_en' => 'Red/tea-colored urine (hematuria)?'],
            // กรอบที่ 11
            'B11'   => ['q' => 'ทอนซิลโตแดงหรือเป็นหนอง?',                                                                                                               'q_en' => 'Enlarged/red tonsils or tonsillar exudate?'],
            // กรอบที่ 11.1
            'B11_1' => ['q' => 'มีผื่นแดงขึ้นทั่วตัวหลังมีไข้ 1-2 วัน? และตรวจพบลิ้นสตรอเบอร์รี่?',                                                                   'q_en' => 'Full-body rash after 1-2 days of fever? And strawberry tongue?'],
            // กรอบที่ 12
            'B12'   => ['q' => 'เหงือกบวมอักเสบเป็นหนอง?',                                                                                                               'q_en' => 'Gingival abscess/swelling?'],
            // กรอบที่ 13
            'B13'   => ['q' => 'มีการอักเสบที่ผิวหนัง?',                                                                                                                  'q_en' => 'Skin infection/inflammation?'],
            // กรอบที่ 14
            'B14'   => ['q' => 'มีไข้สูง อ่อนเพลีย คลื่นไส้ อาเจียน ปวดศีรษะ เวียนศีรษะ หลังเผชิญคลื่นความร้อน หรือทำงาน/ออกกำลังท่ามกลางอากาศร้อน?',             'q_en' => 'High fever, malaise, nausea, vomiting, headache, dizziness after heat exposure?'],
            // กรอบที่ 15
            'B15'   => ['q' => 'มีน้ำมูกหรือไอ? ผื่น/แผลที่ปาก? ต่อมบวม/คอบวม? ถ่ายเป็นน้ำรุนแรง/เป็นบิด? หรือปัสสาวะแสบขัด?',                                    'q_en' => 'Runny nose or cough? Rash/mouth sores? Swollen glands/neck? Diarrhea/dysentery? Or dysuria?'],
            // กรอบที่ 16
            'B16'   => ['q' => 'ไข้สูงตลอดเวลา?',                                                                                                                        'q_en' => 'Continuous/persistent high fever?'],
            // กรอบที่ 16.1
            'B16_1' => ['q' => 'ทดสอบทูร์นิเคต์ให้ผลบวก?',                                                                                                              'q_en' => 'Positive tourniquet test?'],
            // กรอบที่ 16.2
            'B16_2' => ['q' => 'หน้าแดง เปลือกตาแดง?',                                                                                                                   'q_en' => 'Flushed face or red eyelids?'],
            // กรอบที่ 16.3
            'B16_3' => ['q' => 'ในเด็กอายุต่ำกว่า 3 ปี?',                                                                                                                'q_en' => 'Child under 3 years old?'],
            // กรอบที่ 16.4
            'B16_4' => ['q' => 'ตับโตหรือม้ามโต? หรืออยู่ในแวดล้อมที่มีการระบาดของไทฟอยด์?',                                                                           'q_en' => 'Hepatomegaly or splenomegaly? Or in typhoid-endemic area?'],
            // หมายเหตุ: B17 ถูกลบออก เพราะเป็น terminal node "รักษาตามอาการ"
            // เมื่อ next_box_id = null, next_diagram_id = null → ระบบ evaluate rules อัตโนมัติ
            // rule 'viral_fever' (urgency=W) จะถูก match และแสดงคำแนะนำรักษาตามอาการแทน
        ];

        $boxIds  = [];
        $boxSeq  = (int) DB::table('question_boxes')->max('box_id') + 1;

        foreach ($boxes as $key => $box) {
            $boxId = str_pad($boxSeq, 10, '0', STR_PAD_LEFT);
            DB::table('question_boxes')->insertOrIgnore([
                'box_id'           => $boxId,
                'question_text'    => $box['q'],
                'question_text_en' => $box['q_en'],
                'question_type'    => 'S',
                'status'           => '1',
                'diagram_id'       => self::DIAGRAM_ID,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
            $boxIds[$key] = $boxId;
            $boxSeq++;
        }

        // Set entry box
        DB::table('diagrams')
            ->where('diagram_id', self::DIAGRAM_ID)
            ->update(['entry_box_id' => $boxIds['B1']]);

        // ============================================================
        // 3. Answer Choices + Flow
        //
        // next_box_id logic:
        //   'BoxKey'           → ไปกรอบถัดไปใน diagram เดิม
        //   '@diagram:XXXXX'   → กระโดดไป diagram อื่น (next_diagram_id)
        //   null               → จบ flow → evaluate rules
        //
        // การเปลี่ยนแปลงจากเวอร์ชันเก่า:
        //   B16  ตอบ "ไม่" → เปลี่ยนจาก 'B17' → null (viral_fever rule)
        //   B16_4 ตอบ "ไม่" → เปลี่ยนจาก 'B17' → null (viral_fever rule)
        //   B15  ตอบ "ใช่" → คงเป็น null (redirect ไปดูแผนภูมิอื่น ไม่มี box ใน diagram นี้)
        // ============================================================
        $choiceSeq = (int) DB::table('answer_choices')->max('choice_id') + 1;
        $choiceIds = [];

        // format: [box_key => [ [rule_key, text, text_en, next_target, order], ... ]]
        $flow = [
            'B1' => [
                [null,                'ใช่', 'Yes', 'B1_1', 1],
                [null,                'ไม่', 'No',  'B2',   2],
            ],
            'B1_1' => [
                ['meningitis',        'ใช่', 'Yes', null,   1],  // → เยื่อหุ้มสมองอักเสบ
                [null,                'ไม่', 'No',  'B1_2', 2],
            ],
            'B1_2' => [
                ['cerebral_malaria',  'ใช่', 'Yes', null,   1],  // → มาลาเรียขึ้นสมอง
                [null,                'ไม่', 'No',  'B1_3', 2],
            ],
            'B1_3' => [
                ['rabies',            'ใช่', 'Yes', null,   1],  // → พิษสุนัขบ้า
                [null,                'ไม่', 'No',  'B1_4', 2],
            ],
            'B1_4' => [
                ['heat_stroke',       'ใช่', 'Yes', null,   1],  // → โรคลมจากความร้อน
                [null,                'ไม่', 'No',  'B1_5', 2],
            ],
            'B1_5' => [
                ['encephalitis',      'ไม่', 'No',  null,   1],  // → สมองอักเสบ/เล็ปโต
                [null,                'ใช่', 'Yes', 'B1_6', 2],
            ],
            'B1_6' => [
                ['tetanus',           'ใช่', 'Yes', null,   1],  // → บาดทะยัก
                [null,                'ไม่', 'No',  'B1_7', 2],
            ],
            'B1_7' => [
                ['febrile_seizure',   'ใช่', 'Yes', null,   1],  // → ชักจากไข้
                ['severe_other',      'ไม่', 'No',  null,   2],  // → ด่วน อาจมีสาเหตุร้ายแรงอื่นๆ
            ],
            'B2' => [
                [null,                'ใช่', 'Yes', '@diagram:00017', 1], // → ดูแผนภูมิที่ 17 ช็อก
                [null,                'ไม่', 'No',  'B3',   2],
            ],
            'B3' => [
                ['polio',             'ใช่', 'Yes', null,   1],  // → โปลิโอ/ไขสันหลังอักเสบ
                [null,                'ไม่', 'No',  'B4',   2],
            ],
            'B4' => [
                [null,                'ใช่', 'Yes', 'B4_1', 1],
                [null,                'ไม่', 'No',  'B5',   2],
            ],
            'B4_1' => [
                ['tuberculosis',      'ใช่', 'Yes', null,   1],  // → วัณโรคปอด/มะเร็งปอด/เมลิออยโดซิส
                [null,                'ไม่', 'No',  'B4_2', 2],
            ],
            'B4_2' => [
                ['sle',               'ใช่', 'Yes', null,   1],  // → เอสแอลอี
                [null,                'ไม่', 'No',  'B4_3', 2],
            ],
            'B4_3' => [
                ['chronic_malaria',   'ใช่', 'Yes', null,   1],  // → มาลาเรียเรื้อรัง
                [null,                'ไม่', 'No',  'B4_4', 2],
            ],
            'B4_4' => [
                ['endocarditis',      'ใช่', 'Yes', null,   1],  // → เยื่อบุหัวใจอักเสบเรื้อรัง
                [null,                'ไม่', 'No',  'B4_5', 2],
            ],
            'B4_5' => [
                ['leukemia',          'ใช่', 'Yes', null,   1],  // → มะเร็งเม็ดเลือดขาว/มะเร็งต่อมน้ำเหลือง
                ['aids_other',        'ไม่', 'No',  null,   2],  // → มะเร็ง/มาลาเรียเรื้อรัง/เอดส์ ฯลฯ
            ],
            'B5' => [
                [null,                'ใช่', 'Yes', 'B5_1', 1],
                [null,                'ไม่', 'No',  'B6',   2],
            ],
            'B5_1' => [
                ['scrub_typhus',      'ใช่', 'Yes', null,   1],  // → สครับไทฟัส
                [null,                'ไม่', 'No',  'B5_2', 2],
            ],
            'B5_2' => [
                ['malaria',           'ใช่', 'Yes', null,   1],  // → มาลาเรีย
                [null,                'ไม่', 'No',  'B5_3', 2],
            ],
            'B5_3' => [
                ['pyelonephritis',    'ใช่', 'Yes', null,   1],  // → กรวยไตอักเสบเฉียบพลัน
                [null,                'ไม่', 'No',  'B5_4', 2],
            ],
            'B5_4' => [
                ['leptospirosis',     'ใช่', 'Yes', null,   1],  // → เล็ปโตสไปโรซิส
                [null,                'ไม่', 'No',  'B5_5', 2],
            ],
            'B5_5' => [
                ['typhoid',           'ใช่', 'Yes', null,   1],  // → ไทฟอยด์
                ['other_serious',     'ไม่', 'No',  null,   2],  // → เอดส์/อื่นๆ
            ],
            'B6' => [
                [null,                'ใช่', 'Yes', '@diagram:00003', 1], // → ดูแผนภูมิที่ 3 (หอบ/ปวดท้อง)
                [null,                'ไม่', 'No',  'B7',   2],
            ],
            'B7' => [
                [null,                'ใช่', 'Yes', null,   1],  // → ดูแผนภูมิตามอาการที่พบ (terminal)
                [null,                'ไม่', 'No',  'B8',   2],
            ],
            'B8' => [
                ['pneumonia',         'ใช่', 'Yes', null,   1],  // → ปอดอักเสบ
                [null,                'ไม่', 'No',  'B9',   2],
            ],
            'B9' => [
                [null,                'ใช่', 'Yes', 'B9_1', 1],  // มีแผลบุหรี่จี้ → ถามต่อ
                [null,                'ไม่', 'No',  'B10',  2],
            ],
            'B9_1' => [
                ['scrub_typhus_b9_1', 'ใช่', 'Yes', null,   1],  // → สครับไทฟัส
                ['anthrax',           'ไม่', 'No',  null,   2],  // → แอนแทรกซ์/อื่นๆ
            ],
            'B10' => [
                [null,                'ใช่', 'Yes', 'B10_1', 1],
                [null,                'ไม่', 'No',  'B11',  2],
            ],
            'B10_1' => [
                ['glomerulonephritis','ใช่', 'Yes', null,   1],  // → หน่วยไตอักเสบเฉียบพลัน
                ['edema_other',       'ไม่', 'No',  null,   2],  // → ตรวจหาสาเหตุเพิ่มเติม
            ],
            'B11' => [
                [null,                'ใช่', 'Yes', 'B11_1', 1],
                [null,                'ไม่', 'No',  'B12',  2],
            ],
            'B11_1' => [
                ['scarlet_fever',     'ใช่', 'Yes', null,   1],  // → อีดำอีแดง
                ['tonsillitis',       'ไม่', 'No',  null,   2],  // → ทอนซิลอักเสบ
            ],
            'B12' => [
                ['gingivitis',        'ใช่', 'Yes', null,   1],  // → เหงือกอักเสบเป็นหนอง
                [null,                'ไม่', 'No',  'B13',  2],
            ],
            'B13' => [
                ['skin_infection',    'ใช่', 'Yes', null,   1],  // → ฝี/แผล/ไฟลามทุ่ง
                [null,                'ไม่', 'No',  'B14',  2],
            ],
            'B14' => [
                ['heat_exhaustion',   'ใช่', 'Yes', null,   1],  // → ภาวะหมดแรงจากความร้อน
                [null,                'ไม่', 'No',  'B15',  2],
            ],
            'B15' => [
                // ตอบ "ใช่" → ไปดูแผนภูมิอื่นตามอาการ (ไม่มี box ใน diagram นี้ → null)
                [null,                'ใช่', 'Yes', null,   1],  // → ดูแผนภูมิตามอาการที่พบร่วม
                [null,                'ไม่', 'No',  'B16',  2],
            ],
            'B16' => [
                [null,                'ใช่', 'Yes', 'B16_1', 1],
                // ✅ แก้ไข: เปลี่ยนจาก 'B17' → null (จบ flow → evaluate viral_fever rule)
                ['viral_fever',       'ไม่', 'No',  null,   2],
            ],
            'B16_1' => [
                ['dengue',            'ใช่', 'Yes', null,   1],  // → ไข้เลือดออก
                [null,                'ไม่', 'No',  'B16_2', 2],
            ],
            'B16_2' => [
                ['dengue_flush',      'ใช่', 'Yes', null,   1],  // → ไข้เลือดออก (ระยะแรก)
                [null,                'ไม่', 'No',  'B16_3', 2],
            ],
            'B16_3' => [
                ['roseola',           'ใช่', 'Yes', null,   1],  // → ไข้ผื่นกุหลาบในทารก
                [null,                'ไม่', 'No',  'B16_4', 2],
            ],
            'B16_4' => [
                ['typhoid_b16',       'ใช่', 'Yes', null,   1],  // → ไทฟอยด์
                // ✅ แก้ไข: เปลี่ยนจาก 'B17' → null (จบ flow → evaluate viral_fever rule)
                ['viral_fever',       'ไม่', 'No',  null,   2],
            ],
        ];

        foreach ($flow as $boxKey => $choices) {
            foreach ($choices as [$ruleKey, $text, $textEn, $nextTarget, $order]) {
                $choiceId = str_pad($choiceSeq, 10, '0', STR_PAD_LEFT);

                $nextBoxId     = null;
                $nextDiagramId = null;

                if ($nextTarget) {
                    if (str_starts_with($nextTarget, '@diagram:')) {
                        // กระโดดไป diagram อื่น (เอาแค่ diagram แรกถ้ามีหลาย)
                        $diagIds       = explode(',', str_replace('@diagram:', '', $nextTarget));
                        $nextDiagramId = trim($diagIds[0]);
                    } else {
                        $nextBoxId = $boxIds[$nextTarget] ?? null;
                    }
                }

                DB::table('answer_choices')->insertOrIgnore([
                    'choice_id'       => $choiceId,
                    'choice_text'     => $text,
                    'choice_text_en'  => $textEn,
                    'order'           => $order,
                    'status'          => '1',
                    'box_id'          => $boxIds[$boxKey],
                    'next_box_id'     => $nextBoxId,
                    'next_diagram_id' => $nextDiagramId,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);

                if ($ruleKey) {
                    // รองรับ rule_key เดียวกันถูกใช้หลาย box (เช่น viral_fever ใช้ใน B16 และ B16_4)
                    if (!isset($choiceIds[$ruleKey])) {
                        $choiceIds[$ruleKey] = [];
                    }
                    $choiceIds[$ruleKey][] = [
                        'choice_id' => $choiceId,
                        'box_id'    => $boxIds[$boxKey],
                    ];
                }
                $choiceSeq++;
            }
        }

        // ============================================================
        // 4. Diagnosis Rules + Rule Conditions
        //
        // urgency: R=Red, P=Pink, Y=Yellow, G=Green, W=White
        //
        // การเปลี่ยนแปลงจากเวอร์ชันเก่า:
        //   + เพิ่ม 'viral_fever' (urgency=W) แทน B17
        //     → ถูก match เมื่อตอบ "ไม่" ที่ B16 หรือ B16_4
        //     → แสดงคำแนะนำ: รักษาตามอาการ นอนพักผ่อน ดื่มน้ำ ยาลดไข้ ฯลฯ
        //
        //   + viral_fever มี 2 conditions (OR logic):
        //     - B16  ตอบ "ไม่"
        //     - B16_4 ตอบ "ไม่"
        //     เพื่อให้ match ได้ทั้ง 2 เส้นทาง
        // ============================================================
        $rules = [
            'meningitis' => [
                'urgency'    => 'R',
                'time_frame' => 'ด่วน',
                'note'       => 'ฉีดหรือสวนไดอะซีแพม (ย17.1) ถ้าชักไม่หยุด',
                'diseases'   => [
                    ['Meningitis / Brain Abscess', 1],
                    ['Cerebral Hemorrhage / Subarachnoid Hemorrhage', 2],
                    ['Brain Abscess', 3],
                ],
            ],
            'cerebral_malaria' => [
                'urgency'    => 'R',
                'time_frame' => 'ด่วน',
                'note'       => 'ถ้าชักไม่หยุด ฉีดหรือสวนไดอะซีแพม (ย17.1)',
                'diseases'   => [
                    ['Cerebral Malaria', 1],
                ],
            ],
            'rabies' => [
                'urgency'    => 'R',
                'time_frame' => null,
                'note'       => null,
                'diseases'   => [
                    ['Rabies', 1],
                ],
            ],
            'heat_stroke' => [
                'urgency'    => 'R',
                'time_frame' => 'ด่วน',
                'note'       => 'ปฐมพยาบาล ฉีดหรือสวนไดอะซีแพม (ย17.1) ถ้าชัก',
                'diseases'   => [
                    ['Heat Stroke', 1],
                ],
            ],
            'encephalitis' => [
                'urgency'    => 'R',
                'time_frame' => null,
                'note'       => 'สมองอักเสบ/เล็ปโตสไปโรซิส/อื่นๆ',
                'diseases'   => [
                    ['Encephalitis', 1],
                    ['Leptospirosis', 2],
                ],
            ],
            'tetanus' => [
                'urgency'    => 'R',
                'time_frame' => 'ด่วน',
                'note'       => 'ฉีดหรือสวนไดอะซีแพม (ย17.1)',
                'diseases'   => [
                    ['Tetanus', 1],
                ],
            ],
            'febrile_seizure' => [
                'urgency'    => 'Y',
                'time_frame' => null,
                'note'       => 'ยาลดไข้-พาราเซตามอล (ย1.2) รักษาโรคที่เป็นร่วม ถ้าชักนานเกิน 15 นาที/ชักซ้ำใน 24 ชั่วโมง/เป็นการชักครั้งแรก → ด่วน',
                'diseases'   => [
                    ['Febrile Seizure', 1],
                ],
            ],
            'severe_other' => [
                'urgency'    => 'R',
                'time_frame' => 'ด่วน',
                'note'       => 'ไม่ค่อยรู้สึกตัว ชัก แต่ไม่ใช่ชักจากไข้ → อาจมีสาเหตุร้ายแรงอื่นๆ',
                'diseases'   => [],
            ],
            'polio' => [
                'urgency'    => 'R',
                'time_frame' => 'ภายใน 24 ชั่วโมง',
                'note'       => 'อาจเป็นโปลิโอ (63)/ไขสันหลังอักเสบ (84) กลุ่มอาการกิเลนบาร์เร (ดู "โรคที่ 63")',
                'diseases'   => [
                    ['Poliomyelitis', 1],
                    ['Guillain-Barré Syndrome', 2],
                ],
            ],
            'tuberculosis' => [
                'urgency'    => 'Y',
                'time_frame' => 'ภายใน 3 วัน',
                'note'       => 'อาจเป็นวัณโรคปอด (14)/มะเร็งปอด (237)/เมลิออยโดซิส (229.2)',
                'diseases'   => [
                    ['Pulmonary Tuberculosis', 1],
                    ['Lung Cancer', 2],
                    ['Melioidosis', 3],
                ],
            ],
            'sle' => [
                'urgency'    => 'Y',
                'time_frame' => 'ภายใน 3 วัน',
                'note'       => null,
                'diseases'   => [
                    ['Systemic Lupus Erythematosus', 1],
                ],
            ],
            'chronic_malaria' => [
                'urgency'    => 'Y',
                'time_frame' => 'ภายใน 3 วัน',
                'note'       => null,
                'diseases'   => [
                    ['Malaria', 1],
                ],
            ],
            'endocarditis' => [
                'urgency'    => 'Y',
                'time_frame' => 'ภายใน 3 วัน',
                'note'       => null,
                'diseases'   => [
                    ['Infective Endocarditis', 1],
                ],
            ],
            'leukemia' => [
                'urgency'    => 'R',
                'time_frame' => 'ภายใน 3 วัน',
                'note'       => 'อาจเป็นมะเร็งเม็ดเลือดขาว (106)/มะเร็งต่อมน้ำเหลือง (106.1)/อื่นๆ',
                'diseases'   => [
                    ['Leukemia', 1],
                    ['Lymphoma', 2],
                ],
            ],
            'aids_other' => [
                'urgency'    => 'Y',
                'time_frame' => 'ภายใน 3 วัน',
                'note'       => 'อาจเป็นมะเร็ง/มาลาเรียเรื้อรัง/เอดส์/อื่นๆ',
                'diseases'   => [],
            ],
            'scrub_typhus' => [
                'urgency'    => 'Y',
                'time_frame' => null,
                'note'       => 'ดอกซีไซคลิน (ย4.5.1) หรือไรแฟมพิซิน (ย4.14) ถ้าไม่ดีขึ้นใน 3 วัน หรือไม่ค่อยรู้สึกตัว/หอบ/ถ่ายอุจจาระดำ → ด่วน',
                'diseases'   => [
                    ['Scrub Typhus', 1],
                ],
            ],
            'malaria' => [
                'urgency'    => 'Y',
                'time_frame' => null,
                'note'       => 'ยารักษามาลาเรีย (ย5) ถ้าไม่ดีขึ้นใน 3 วัน หรือไม่ค่อยรู้สึกตัว/ชัก/หอบ/ซีด/ดีซ่าน → ด่วน',
                'diseases'   => [
                    ['Malaria', 1],
                ],
            ],
            'pyelonephritis' => [
                'urgency'    => 'Y',
                'time_frame' => null,
                'note'       => 'โคไตรม็อกซาโซล (ย4.7) หรืออะม็อกซีซิลลิน (ย4.2) หรือไซโพรฟล็อกซาซิน (ย4.11.2) ถ้าไม่ดีขึ้นใน 3 วัน หรือซีด/ดีซ่าน/มีจุดแดงจ้ำเขียว → ด่วน',
                'diseases'   => [
                    ['Acute Pyelonephritis', 1],
                ],
            ],
            'leptospirosis' => [
                'urgency'    => 'Y',
                'time_frame' => 'ภายใน 24 ชั่วโมง',
                'note'       => null,
                'diseases'   => [
                    ['Leptospirosis', 1],
                ],
            ],
            'typhoid' => [
                'urgency'    => 'Y',
                'time_frame' => null,
                'note'       => 'โคไตรม็อกซาโซล (ย4.7) หรือคลอแรมเฟนิคอล (ย4.6) หรืออะม็อกซีซิลลิน (ย4.2) ถ้าไม่ดีขึ้นใน 4 วัน หรือหอบ/คอแข็ง/ปวดท้องรุนแรง/ซีด/ดีซ่าน → ด่วน',
                'diseases'   => [
                    ['Typhoid Fever', 1],
                ],
            ],
            'other_serious' => [
                'urgency'    => 'Y',
                'time_frame' => 'ภายใน 3 วัน',
                'note'       => 'ไข้นาน > 7 วัน แต่ไม่พบสาเหตุชัดเจน → เอดส์/อื่นๆ',
                'diseases'   => [],
            ],
            'pneumonia' => [
                'urgency'    => 'Y',
                'time_frame' => null,
                'note'       => 'เพนิซิลลินวี (ย4.1) หรืออะม็อกซีซิลลิน (ย4.2) หรืออีริโทรไมซิน (ย4.4) ถ้าหอบ/มีไข้เกิน 4 วัน/อาการไม่ดีขึ้นใน 48 ชั่วโมง → ด่วน',
                'diseases'   => [
                    ['Pneumonia', 1],
                ],
            ],
            'scrub_typhus_b9_1' => [
                'urgency'    => 'Y',
                'time_frame' => null,
                'note'       => 'ดอกซีไซคลิน (ย4.5.1) หรือไรแฟมพิซิน (ย4.14)',
                'diseases'   => [
                    ['Scrub Typhus', 1],
                ],
            ],
            'anthrax' => [
                'urgency'    => 'Y',
                'time_frame' => 'ภายใน 3 วัน',
                'note'       => 'อาจเป็นแอนแทรกซ์ (229.3)/อื่นๆ',
                'diseases'   => [
                    ['Anthrax', 1],
                ],
            ],
            'glomerulonephritis' => [
                'urgency'    => 'Y',
                'time_frame' => 'ภายใน 3 วัน',
                'note'       => null,
                'diseases'   => [
                    ['Acute Glomerulonephritis', 1],
                ],
            ],
            'edema_other' => [
                'urgency'    => 'Y',
                'time_frame' => 'ภายใน 3 วัน',
                'note'       => 'บวม 2 ข้าง ปัสสาวะปกติ → ตรวจหาสาเหตุเพิ่มเติม',
                'diseases'   => [],
            ],
            'scarlet_fever' => [
                'urgency'    => 'Y',
                'time_frame' => null,
                'note'       => 'เพนิซิลลิน วี (ย4.1) หรืออีริโทรไมซิน (ย4.4) ถ้าไม่ดีขึ้นใน 3 วัน → ด่วน',
                'diseases'   => [
                    ['Scarlet Fever', 1],
                ],
            ],
            'tonsillitis' => [
                'urgency'    => 'G',
                'time_frame' => null,
                'note'       => 'เพนิซิลลิน วี (ย4.1) หรืออีริโทรไมซิน (ย4.4) ถ้าไม่ดีขึ้นใน 3 วัน → ด่วน',
                'diseases'   => [
                    ['Acute Tonsillitis', 1],
                ],
            ],
            'gingivitis' => [
                'urgency'    => 'G',
                'time_frame' => null,
                'note'       => null,
                'diseases'   => [
                    ['Gingival Abscess', 1],
                ],
            ],
            'skin_infection' => [
                'urgency'    => 'G',
                'time_frame' => null,
                'note'       => 'ไดคล็อกซาซิลลิน (ย4.3) หรืออีริโทรไมซิน (ย4.4) หรือไซโพรฟล็อกซาซิน (ย4.11.2) ถ้าไม่ดีขึ้นใน 3 วัน หรือเป็นเบาหวาน/สงสัยเมลิออยโดซิส/เป็นหลังกินหอยนางรม → ด่วน',
                'diseases'   => [
                    ['Skin Infection / Cellulitis', 1],
                    ['Abscess / Furuncle', 2],
                ],
            ],
            'heat_exhaustion' => [
                'urgency'    => 'Y',
                'time_frame' => 'ด่วน',
                'note'       => 'ปฐมพยาบาล',
                'diseases'   => [
                    ['Heat Exhaustion', 1],
                ],
            ],
            'dengue' => [
                'urgency'    => 'Y',
                'time_frame' => null,
                'note'       => 'รักษาตามอาการ ยาลดไข้ (ย1.2) ดูอาการใกล้ชิดทุกวัน ถ้าไม่ดีขึ้นใน 4 วัน หรือหอบ/ชัก/ไม่ค่อยรู้สึกตัว/ปวดท้องมาก/อาเจียน/กินไม่ได้/มีเลือดออก/ช็อก → ด่วน',
                'diseases'   => [
                    ['Dengue Hemorrhagic Fever', 1],
                ],
            ],
            'dengue_flush' => [
                'urgency'    => 'Y',
                'time_frame' => null,
                'note'       => 'ไข้เลือดออก (225) ระยะแรก หรือโรคติดเชื้อไวรัสอื่นๆ',
                'diseases'   => [
                    ['Dengue Hemorrhagic Fever', 1],
                ],
            ],
            'roseola' => [
                'urgency'    => 'G',
                'time_frame' => null,
                'note'       => 'รักษาตามอาการ',
                'diseases'   => [
                    ['Roseola Infantum', 1],
                ],
            ],
            'typhoid_b16' => [
                'urgency'    => 'Y',
                'time_frame' => null,
                'note'       => 'โคไตรม็อกซาโซล (ย4.7) หรือคลอแรมเฟนิคอล (ย4.6) หรืออะม็อกซีซิลลิน (ย4.2)',
                'diseases'   => [
                    ['Typhoid Fever', 1],
                ],
            ],

            // ✅ เพิ่มใหม่: แทน B17 (กรอบ "รักษาตามอาการ" ในหนังสือ)
            // urgency=W (White) = ไม่ด่วน รักษาตามอาการ
            // match เมื่อ: ตอบ "ไม่" ที่ B16 หรือ B16_4
            'viral_fever' => [
                'urgency'    => 'W',
                'time_frame' => null,
                'note'       => implode("\n", [
                    'รักษาตามอาการ (อาจเป็นโรคติดเชื้อไวรัสหรือสาเหตุอื่นๆ ในระยะแรกเริ่ม)',
                    '• นอนพักผ่อน',
                    '• ดื่มน้ำมากๆ',
                    '• ใช้ผ้าชุบน้ำเช็ดตัวบ่อยๆ',
                    '• ห้ามอาบน้ำเย็น',
                    '• ถ้าเบื่ออาหาร กินน้ำหวาน ข้าวต้ม',
                    '• ถ้าเจ็บคอให้น้ำเกลือกลั้วคอ',
                    '• ยาลดไข้-พาราเซตามอล (ย1.2)',
                    '⊕ ถ้าไม่ดีขึ้นใน 4 วัน หรือไข้เกิน 7 วัน หรือหนาวสั่นมาก ตับโต/ม้ามโต หรืออาการเปลี่ยนแปลงผิดปกติ (เช่น หายใจหอบ ปวดศีรษะมาก ปวดท้องมาก ท้องเดินรุนแรง อาเจียน กินไม่ได้ ซีด ดีซ่าน มีจุดแดงจ้ำเขียว น้ำหนักลดฮวบ เป็นต้น) หรือมีประวัติสัมผัสสัตว์ปีกที่ป่วยหรือตาย/สัมผัสผู้ป่วยไข้หวัดนก (240) หรือซาร์ส (239) → ด่วน',
                ]),
                'diseases'   => [],
            ],
        ];

        $ruleSeq      = (int) DB::table('diagnosis_rules')->max('rule_id') + 1;
        $conditionSeq = (int) DB::table('rule_conditions')->max('condition_id') + 1;

        foreach ($rules as $ruleKey => $rule) {
            if (!isset($choiceIds[$ruleKey])) continue;

            $ruleId = str_pad($ruleSeq, 10, '0', STR_PAD_LEFT);

            DB::table('diagnosis_rules')->insertOrIgnore([
                'rule_id'           => $ruleId,
                'urgency_level'     => $rule['urgency'],
                'medical_reference' => null,
                'note'              => $rule['note'],
                'time_frame'        => $rule['time_frame'] ?? null,
                'status'            => '1',
                'diagram_id'        => self::DIAGRAM_ID,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);

            // rule_conditions
            // viral_fever มี 2 entries (B16 และ B16_4) → ใช้ OR logic
            $conditions = $choiceIds[$ruleKey];
            foreach ($conditions as $idx => $cond) {
                DB::table('rule_conditions')->insertOrIgnore([
                    'condition_id'   => str_pad($conditionSeq, 10, '0', STR_PAD_LEFT),
                    'rule_id'        => $ruleId,
                    'box_id'         => $cond['box_id'],
                    'choice_id'      => $cond['choice_id'],
                    // condition แรก = AND (default), condition ที่ 2+ = OR
                    'logic_operator' => $idx === 0 ? 'AND' : 'OR',
                    'status'         => '1',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
                $conditionSeq++;
            }

            // rule_diseases (pivot)
            foreach ($rule['diseases'] as [$diseaseNameEn, $displayOrder]) {
                $diseaseId = $getDiseaseId($diseaseNameEn);
                if (!$diseaseId) continue;

                DB::table('rule_diseases')->insertOrIgnore([
                    'rule_id'       => $ruleId,
                    'disease_id'    => $diseaseId,
                    'display_order' => $displayOrder,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }

            $ruleSeq++;
        }

        $this->command->info('✅ Diagram1FeverSeeder สำเร็จ');
        $this->command->info('   🔘 question_boxes  : ' . count($boxes) . ' (ลบ B17 ออกแล้ว)');
        $this->command->info('   📋 diagnosis_rules : ' . count($rules));
        $this->command->info('   🔗 rule_conditions : ' . ($conditionSeq - 1));
        $this->command->info('');
        $this->command->info('   📌 การเปลี่ยนแปลงหลัก:');
        $this->command->info('      - ลบ B17 (กรอบ "รักษาตามอาการ") ออกจาก question_boxes');
        $this->command->info('      - B16  ตอบ "ไม่" → next_box_id = null → evaluate → viral_fever (W)');
        $this->command->info('      - B16_4 ตอบ "ไม่" → next_box_id = null → evaluate → viral_fever (W)');
        $this->command->info('      - viral_fever rule มี 2 conditions (B16 OR B16_4)');
    }
}
