<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeverDiagramSeeder extends Seeder
{
    /**
     * Seeder สำหรับแผนภูมิที่ 1: ไข้ (FEVER)
     * อ้างอิง: ตำราการตรวจรักษาโรคทั่วไป เล่ม 1 โดย นพ.สุรเกียรติ อาชานุภาพ
     * แผนภูมิที่ 1 หน้า 8-15
     */
    public function run(): void
    {
        $adminId = null; // ไม่มี admin ตอน seed ใช้ null

        // ============================================================
        // 1. SYMPTOM CATEGORY
        // ============================================================
        DB::table('symptom_categories')->insertOrIgnore([
            'symptom_category_id' => 'SC0001',
            'category_name'       => 'อาการทั่วไป',
            'category_name_en'    => 'General Symptoms',
            'description'         => 'อาการทั่วไปที่พบบ่อยในผู้ป่วยทุกกลุ่มอายุ',
            'status'              => '1',
            'created_by'          => $adminId,
            'updated_by'          => $adminId,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // ============================================================
        // 2. MAIN SYMPTOM
        // ============================================================
        DB::table('main_symptoms')->insertOrIgnore([
            'symptom_id'          => 'SYM000001',
            'symptom_name'        => 'ไข้',
            'symptom_name_en'     => 'Fever',
            'description'         => 'ตัวร้อน อุณหภูมิของร่างกายสูงกว่า 37.2°C (วัดทางปาก) หรือ 37.5°C (วัดทางรักแร้) หรือ 37.7°C (วัดทางหน้าผาก)',
            'status'              => '1',
            'symptom_category_id' => 'SC0001',
            'created_by'          => $adminId,
            'updated_by'          => $adminId,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // ============================================================
        // 3. DISEASE CATEGORY
        // ============================================================
        DB::table('disease_categories')->insertOrIgnore([
            'disease_category_id' => 'DC0001',
            'category_name'       => 'โรคติดเชื้อ',
            'category_name_en'    => 'Infectious Diseases',
            'description'         => 'โรคที่เกิดจากเชื้อโรค เช่น แบคทีเรีย ไวรัส เชื้อรา',
            'status'              => '1',
            'created_by'          => $adminId,
            'updated_by'          => $adminId,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        DB::table('disease_categories')->insertOrIgnore([
            'disease_category_id' => 'DC0002',
            'category_name'       => 'โรคระบบประสาท',
            'category_name_en'    => 'Neurological Diseases',
            'description'         => 'โรคที่เกี่ยวข้องกับระบบประสาทและสมอง',
            'status'              => '1',
            'created_by'          => $adminId,
            'updated_by'          => $adminId,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // ============================================================
        // 4. DISEASES (โรคที่พบในแผนภูมิไข้)
        // ============================================================
        $diseases = [
            [
                'disease_id'          => 'DIS000001',
                'disease_name'        => 'เยื่อหุ้มสมองอักเสบ',
                'disease_name_en'     => 'Meningitis',
                'description'         => 'การอักเสบของเยื่อหุ้มสมอง มักมีอาการคอแข็ง ปวดศีรษะรุนแรง และไข้สูง',
                'cause'               => 'เชื้อแบคทีเรีย ไวรัส หรือเชื้อรา',
                'symptom_description' => 'ไข้สูง คอแข็ง ปวดศีรษะมาก กลัวแสง คลื่นไส้อาเจียน',
                'prevention'          => 'ฉีดวัคซีนป้องกันเยื่อหุ้มสมองอักเสบ',
                'status'              => '1',
                'disease_category_id' => 'DC0002',
            ],
            [
                'disease_id'          => 'DIS000002',
                'disease_name'        => 'เลือดออกในสมอง',
                'disease_name_en'     => 'Intracranial Hemorrhage',
                'description'         => 'ภาวะเลือดออกในสมอง เป็นภาวะฉุกเฉินที่ต้องรีบรักษา',
                'cause'               => 'ความดันโลหิตสูง อุบัติเหตุ หลอดเลือดโป่งพอง',
                'symptom_description' => 'ปวดศีรษะรุนแรงฉับพลัน ซึม หมดสติ อัมพาต',
                'prevention'          => 'ควบคุมความดันโลหิต',
                'status'              => '1',
                'disease_category_id' => 'DC0002',
            ],
            [
                'disease_id'          => 'DIS000003',
                'disease_name'        => 'ฝีสมอง',
                'disease_name_en'     => 'Brain Abscess',
                'description'         => 'หนองสะสมในสมอง เกิดจากการติดเชื้อแบคทีเรีย',
                'cause'               => 'การติดเชื้อแบคทีเรียแพร่กระจายมายังสมอง',
                'symptom_description' => 'ไข้ ปวดศีรษะ ซึม อาจมีอาการทางระบบประสาท',
                'prevention'          => 'รักษาการติดเชื้อให้หายขาด',
                'status'              => '1',
                'disease_category_id' => 'DC0002',
            ],
            [
                'disease_id'          => 'DIS000004',
                'disease_name'        => 'มาลาเรียขึ้นสมอง',
                'disease_name_en'     => 'Cerebral Malaria',
                'description'         => 'มาลาเรียชนิดรุนแรงที่มีผลต่อสมอง',
                'cause'               => 'เชื้อพลาสโมเดียม ฟัลซิพารัม',
                'symptom_description' => 'ไข้สูง ซึม ชัก หมดสติ',
                'prevention'          => 'ป้องกันยุงกัด รับประทานยาป้องกัน',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000005',
                'disease_name'        => 'โรคพิษสุนัขบ้า',
                'disease_name_en'     => 'Rabies',
                'description'         => 'โรคติดเชื้อไวรัสจากสัตว์ที่มีผลต่อระบบประสาท',
                'cause'               => 'ไวรัสพิษสุนัขบ้า ติดต่อผ่านการกัดหรือข่วนของสัตว์ที่เป็นโรค',
                'symptom_description' => 'ไข้ กลัวน้ำ กลัวลม กระสับกระส่าย',
                'prevention'          => 'ฉีดวัคซีนป้องกัน หลังถูกสัตว์กัดให้รีบฉีดวัคซีนทันที',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000006',
                'disease_name'        => 'โรคลมแดด / โรคจากความร้อน',
                'disease_name_en'     => 'Heat Stroke',
                'description'         => 'ภาวะที่ร่างกายได้รับความร้อนสูงเกินไปจนควบคุมอุณหภูมิไม่ได้',
                'cause'               => 'สัมผัสความร้อนสูงในอากาศร้อน หรือออกกำลังกายหนักในอากาศร้อน',
                'symptom_description' => 'ตัวร้อนมาก ไม่มีเหงื่อ สับสน หมดสติ',
                'prevention'          => 'หลีกเลี่ยงความร้อน ดื่มน้ำมากๆ สวมเสื้อผ้าเบาบาง',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000007',
                'disease_name'        => 'สมองอักเสบ / เล็บโตสไปโรซิส',
                'disease_name_en'     => 'Encephalitis / Leptospirosis',
                'description'         => 'การอักเสบของเนื้อสมอง หรือการติดเชื้อเล็บโตสไปโรซิส',
                'cause'               => 'ไวรัส แบคทีเรีย หรือเชื้อเล็บโตสไปร่า จากการสัมผัสน้ำหรือดินที่ปนเปื้อน',
                'symptom_description' => 'ไข้ ปวดศีรษะ ซึม ปวดกล้ามเนื้อ ตาเหลือง',
                'prevention'          => 'หลีกเลี่ยงการสัมผัสน้ำท่วม ใส่รองเท้าบู้ตเมื่อลุยน้ำ',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000008',
                'disease_name'        => 'โปลิโอ',
                'disease_name_en'     => 'Poliomyelitis',
                'description'         => 'โรคติดเชื้อไวรัสที่ทำลายระบบประสาทและกล้ามเนื้อ',
                'cause'               => 'ไวรัสโปลิโอ ติดต่อทางอาหารและน้ำ',
                'symptom_description' => 'ไข้ อ่อนแรงกล้ามเนื้อแบบเฉียบพลัน',
                'prevention'          => 'รับวัคซีนโปลิโอ',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000009',
                'disease_name'        => 'ไข้ชักในเด็ก',
                'disease_name_en'     => 'Febrile Convulsion',
                'description'         => 'อาการชักที่เกิดจากไข้สูงในเด็กอายุ 6 เดือน - 5 ปี',
                'cause'               => 'ไข้สูงกระตุ้นให้เกิดการชัก',
                'symptom_description' => 'ไข้สูง ชักเกร็ง หรือกระตุก มักหยุดเองภายใน 15 นาที',
                'prevention'          => 'ลดไข้ทันทีเมื่อมีไข้สูง',
                'status'              => '1',
                'disease_category_id' => 'DC0002',
            ],
            [
                'disease_id'          => 'DIS000010',
                'disease_name'        => 'บาดทะยัก',
                'disease_name_en'     => 'Tetanus',
                'description'         => 'โรคติดเชื้อแบคทีเรียที่ทำให้กล้ามเนื้อเกร็ง',
                'cause'               => 'เชื้อ Clostridium tetani จากบาดแผล',
                'symptom_description' => 'กล้ามเนื้อเกร็ง โดยเฉพาะขากรรไกรแข็ง ไข้',
                'prevention'          => 'ฉีดวัคซีนป้องกันบาดทะยัก ดูแลบาดแผลให้สะอาด',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000011',
                'disease_name'        => 'วัณโรคปอด',
                'disease_name_en'     => 'Pulmonary Tuberculosis',
                'description'         => 'โรคติดเชื้อแบคทีเรียที่ปอด',
                'cause'               => 'เชื้อ Mycobacterium tuberculosis',
                'symptom_description' => 'ไข้ต่ำๆ ไอเรื้อรัง เสมหะมีเลือด น้ำหนักลด เหงื่อออกกลางคืน',
                'prevention'          => 'ฉีดวัคซีน BCG หลีกเลี่ยงผู้ป่วยวัณโรค',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000012',
                'disease_name'        => 'มะเร็งปอด',
                'disease_name_en'     => 'Lung Cancer',
                'description'         => 'มะเร็งที่เกิดในเนื้อเยื่อปอด',
                'cause'               => 'สูบบุหรี่ สารเคมี มลพิษอากาศ',
                'symptom_description' => 'ไอเรื้อรัง ไข้ น้ำหนักลด ไอเป็นเลือด',
                'prevention'          => 'เลิกสูบบุหรี่ หลีกเลี่ยงสารพิษ',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000013',
                'disease_name'        => 'เมลิออยโดซิส',
                'disease_name_en'     => 'Melioidosis',
                'description'         => 'โรคติดเชื้อแบคทีเรียจากดินและน้ำ พบมากในภาคตะวันออกเฉียงเหนือ',
                'cause'               => 'เชื้อ Burkholderia pseudomallei',
                'symptom_description' => 'ไข้ ปอดอักเสบ ฝีในอวัยวะต่างๆ',
                'prevention'          => 'ใส่รองเท้าและถุงมือเมื่อสัมผัสดินและน้ำ',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000014',
                'disease_name'        => 'ไข้วัว / สครับไทฟัส',
                'disease_name_en'     => 'Scrub Typhus',
                'description'         => 'โรคติดเชื้อจากไรอ่อนในทุ่งหญ้าและป่า',
                'cause'               => 'เชื้อ Orientia tsutsugamushi จากการถูกไรอ่อนกัด',
                'symptom_description' => 'ไข้สูง มีรอยแผลสะเก็ดที่ถูกไรกัด ต่อมน้ำเหลืองโต ผื่น',
                'prevention'          => 'ใส่เสื้อผ้าปิดมิดชิดในพื้นที่เสี่ยง ทายากันแมลง',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000015',
                'disease_name'        => 'ไข้เลือดออก',
                'disease_name_en'     => 'Dengue Hemorrhagic Fever',
                'description'         => 'โรคติดเชื้อไวรัสเดงกีจากยุงลาย',
                'cause'               => 'ไวรัสเดงกี ติดต่อโดยยุงลาย Aedes aegypti',
                'symptom_description' => 'ไข้สูงฉับพลัน 2-7 วัน ปวดศีรษะ ปวดกระบอกตา ปวดกล้ามเนื้อ มีผื่นหรือเลือดออก',
                'prevention'          => 'กำจัดแหล่งเพาะพันธุ์ยุง ป้องกันยุงกัด',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000016',
                'disease_name'        => 'ไข้มาลาเรีย',
                'disease_name_en'     => 'Malaria',
                'description'         => 'โรคติดเชื้อโปรโตซัวจากยุงก้นปล่อง',
                'cause'               => 'เชื้อพลาสโมเดียม ติดต่อโดยยุงก้นปล่อง Anopheles',
                'symptom_description' => 'ไข้หนาวสั่น เป็นๆ หายๆ ปวดศีรษะ คลื่นไส้ อาเจียน',
                'prevention'          => 'ป้องกันยุงกัด รับประทานยาป้องกันมาลาเรียเมื่อเดินทางไปพื้นที่เสี่ยง',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000017',
                'disease_name'        => 'ไทฟอยด์',
                'disease_name_en'     => 'Typhoid Fever',
                'description'         => 'โรคติดเชื้อแบคทีเรียในลำไส้',
                'cause'               => 'เชื้อ Salmonella typhi จากอาหารและน้ำปนเปื้อน',
                'symptom_description' => 'ไข้สูงนานเกิน 1 สัปดาห์ ปวดท้อง ท้องผูกหรือท้องเสีย ผื่นจุดแดงที่หน้าอก',
                'prevention'          => 'รักษาสุขอนามัย รับประทานอาหารสุก น้ำสะอาด ฉีดวัคซีน',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000018',
                'disease_name'        => 'ไข้หวัดใหญ่ (อาการทั่วไปหรือไม่ทราบสาเหตุ)',
                'disease_name_en'     => 'Influenza / Viral Fever',
                'description'         => 'ไข้จากการติดเชื้อไวรัสทางเดินหายใจ หรือไข้ไม่ทราบสาเหตุชัดเจน',
                'cause'               => 'ไวรัสไข้หวัดใหญ่ หรือไวรัสอื่นๆ',
                'symptom_description' => 'ไข้ ปวดกล้ามเนื้อ ปวดศีรษะ คัดจมูก น้ำมูก ไอ เจ็บคอ',
                'prevention'          => 'ฉีดวัคซีนไข้หวัดใหญ่ประจำปี ล้างมือบ่อยๆ',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000019',
                'disease_name'        => 'เอสแอลอี (โรคพุ่มพวง)',
                'disease_name_en'     => 'Systemic Lupus Erythematosus (SLE)',
                'description'         => 'โรคภูมิคุ้มกันทำลายตัวเอง มีผลต่อหลายอวัยวะ',
                'cause'               => 'ความผิดปกติของระบบภูมิคุ้มกัน',
                'symptom_description' => 'ไข้ ผื่นรูปผีเสื้อที่หน้า ปวดข้อ ผมร่วง',
                'prevention'          => 'ยังไม่มีวิธีป้องกันที่ชัดเจน',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000020',
                'disease_name'        => 'ปอดอักเสบ',
                'disease_name_en'     => 'Pneumonia',
                'description'         => 'การอักเสบของเนื้อปอด',
                'cause'               => 'แบคทีเรีย ไวรัส หรือเชื้อรา',
                'symptom_description' => 'ไข้ ไอ เจ็บหน้าอก หายใจลำบาก เสมหะ',
                'prevention'          => 'ฉีดวัคซีน ล้างมือ หลีกเลี่ยงการสูบบุหรี่',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
            [
                'disease_id'          => 'DIS000021',
                'disease_name'        => 'แอนแทรกซ์',
                'disease_name_en'     => 'Anthrax',
                'description'         => 'โรคติดเชื้อจากสัตว์สู่คน พบได้จากการสัมผัสสัตว์หรือผลิตภัณฑ์สัตว์ที่เป็นโรค',
                'cause'               => 'เชื้อ Bacillus anthracis',
                'symptom_description' => 'ไข้ แผลเนื้อตายสีดำ หรือปอดอักเสบรุนแรง',
                'prevention'          => 'หลีกเลี่ยงการสัมผัสสัตว์ป่วยหรือซาก',
                'status'              => '1',
                'disease_category_id' => 'DC0001',
            ],
        ];

        foreach ($diseases as $disease) {
            DB::table('diseases')->insertOrIgnore(array_merge($disease, [
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // ============================================================
        // 5. DIAGRAM
        // ============================================================
        DB::table('diagrams')->insertOrIgnore([
            'diagram_id'      => 'DG001',
            'diagram_name'    => 'แผนภูมิที่ 1: ไข้',
            'diagram_name_en' => 'Diagram 1: Fever',
            'description'     => 'แผนภูมิวินิจฉัยและรักษาโรคไข้ อ้างอิง: ตำราการตรวจรักษาโรคทั่วไป เล่ม 1 แผนภูมิที่ 1 หน้า 8-15',
            'status'          => '1',
            'symptom_id'      => 'SYM000001',
            // entry_box_id จะ update ทีหลัง
            'created_by'      => $adminId,
            'updated_by'      => $adminId,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // ============================================================
        // 6. QUESTION BOXES
        // ============================================================
        $boxes = [
            // กรอบที่ 1 - เริ่มต้น
            [
                'box_id'           => 'BOX0000001',
                'question_text'    => 'ไม่ค่อยรู้สึกตัว? ปวดศีรษะมาก? อาเจียนรุนแรง? หรือ ชัก?',
                'question_text_en' => 'Altered consciousness? Severe headache? Forceful vomiting? Or seizure?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 1.1
            [
                'box_id'           => 'BOX0000002',
                'question_text'    => 'คอแข็ง? หรือ กระหม่อมโป่งตึง (ในเด็กเล็ก)?',
                'question_text_en' => 'Neck stiffness? Or bulging fontanelle (in infants)?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 1.2
            [
                'box_id'           => 'BOX0000003',
                'question_text'    => 'เคยไปในดงมาลาเรีย หรือได้รับการถ่ายเลือดภายในระยะหลายเดือนที่ผ่านมา?',
                'question_text_en' => 'Recent travel to malaria-endemic area or blood transfusion in past months?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 1.3
            [
                'box_id'           => 'BOX0000004',
                'question_text'    => 'เคยถูกสุนัขหรือแมวกัด หรือข่วน และมีอาการกลัวน้ำกลัวลม?',
                'question_text_en' => 'History of dog or cat bite/scratch and hydrophobia/aerophobia?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 1.4
            [
                'box_id'           => 'BOX0000005',
                'question_text'    => 'หลังเผชิญคลื่นความร้อน หรือทำงาน/ออกกำลังกายท่ามกลางอากาศร้อน?',
                'question_text_en' => 'After heat wave exposure or exercise in hot weather?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 1.5
            [
                'box_id'           => 'BOX0000006',
                'question_text'    => 'รู้สึกตัวดี?',
                'question_text_en' => 'Alert and oriented?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 1.6
            [
                'box_id'           => 'BOX0000007',
                'question_text'    => 'ขณะกำลังให้แสงเลเซอร์? หรือ ชักบ่อยเวลาส่มผัสถูกแสงวาบหรือเสียงดังๆ?',
                'question_text_en' => 'During laser exposure? Or frequent seizures triggered by flashing lights or loud sounds?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 1.7
            [
                'box_id'           => 'BOX0000008',
                'question_text'    => 'พบในเด็ก 6 เดือน - 5 ปี ชักชั่วขณะหนึ่ง (ไม่เกิน 15 นาที) แล้วหยุดชักได้เอง?',
                'question_text_en' => 'Child 6 months - 5 years with brief self-limiting seizure (under 15 minutes)?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 2
            [
                'box_id'           => 'BOX0000009',
                'question_text'    => 'มีภาวะช็อก (เหงื่อออก ตัวเย็น กระสับกระส่าย ชีพจรเบาเร็ว และความดันเลือดตก)?',
                'question_text_en' => 'Signs of shock (sweating, cold extremities, restlessness, weak rapid pulse, low BP)?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 3
            [
                'box_id'           => 'BOX0000010',
                'question_text'    => 'แขนขาอ่อนแรง หรืออัมพาตเกิดขึ้นฉับพลัน?',
                'question_text_en' => 'Sudden onset limb weakness or paralysis?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 4
            [
                'box_id'           => 'BOX0000011',
                'question_text'    => 'มีไข้นานเกิน 1 เดือน?',
                'question_text_en' => 'Fever lasting more than 1 month?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 4.1
            [
                'box_id'           => 'BOX0000012',
                'question_text'    => 'ไอ? และน้ำหนักลดฮวบ?',
                'question_text_en' => 'Cough and significant weight loss?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 4.2
            [
                'box_id'           => 'BOX0000013',
                'question_text'    => 'ปวดข้อมือ 2 ข้าง? ผมร่วง? หรือมีผื่นปีกผีเสื้อที่แก้ม?',
                'question_text_en' => 'Bilateral wrist joint pain? Hair loss? Or butterfly rash on cheeks?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 4.3
            [
                'box_id'           => 'BOX0000014',
                'question_text'    => 'มีไข้หนาวสั่นเป็นวันเว้นวัน? และเคยเข้าไปในดงมาลาเรีย?',
                'question_text_en' => 'Alternate day chills and fever? History of travel to malaria-endemic area?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 4.4
            [
                'box_id'           => 'BOX0000015',
                'question_text'    => 'มีจุดแดงที่เยื่อบุตา/ใต้เล็บ? ใช้เครื่องฟังหัวใจมีเสียงฟู่ (murmur)? และ ม้ามโต?',
                'question_text_en' => 'Petechiae in conjunctiva/nails? Cardiac murmur? And splenomegaly?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 4.5
            [
                'box_id'           => 'BOX0000016',
                'question_text'    => 'มีจุดแดงจ้ำเขียวขึ้นตามตัว? หรือมีก้อนบวมมีข้างคอ หรือวักแร้?',
                'question_text_en' => 'Petechiae/ecchymosis on skin? Or neck/axillary lymphadenopathy?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 5
            [
                'box_id'           => 'BOX0000017',
                'question_text'    => 'มีไข้เกิน 7 วัน? หรือ หนาวสั่นมาก (ห่มผ้าหนาๆ)?',
                'question_text_en' => 'Fever over 7 days? Or severe chills (heavy blankets needed)?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 5.1
            [
                'box_id'           => 'BOX0000018',
                'question_text'    => 'พบรอยแผลเหมือนถูกบุหรี่จี้ หรือผื่นขึ้นหลัง มีไข้ 5-7 วัน และมีประวัติเคยเข้าไปในทุ่งหญ้า หรือไร่สวน ภายในระยะ 3 สัปดาห์ที่ผ่านมา?',
                'question_text_en' => 'Eschar lesion or rash on back, fever 5-7 days, and recent history of being in grassland/farm within 3 weeks?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 5.2
            [
                'box_id'           => 'BOX0000019',
                'question_text'    => 'จับไข้หนาวสั่นวันละครั้ง หรือวันเว้นวัน? และมีประวัติเคยเข้าไปในดงมาลาเรีย หรือได้รับการถ่ายเลือดภายในระยะหลายเดือนที่ผ่านมา?',
                'question_text_en' => 'Daily or alternate day chills? History of malaria-endemic area exposure or recent blood transfusion?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 5.3
            [
                'box_id'           => 'BOX0000020',
                'question_text'    => 'เจาะเจ็บที่ซี่ข้าง? และปัสสาวะขุ่น?',
                'question_text_en' => 'Flank tenderness? And turbid urine?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 5.4
            [
                'box_id'           => 'BOX0000021',
                'question_text'    => 'บึ้นน้อยรู้สึกปวดมาก? ตาแดงหรือตาเหลือง? และมีอาชีพที่ต้องย่ำน้ำหรืออยู่ในแวดล้อมที่มีการระบาดของเล็บโตสไปโรซิส?',
                'question_text_en' => 'Severe muscle pain? Red/jaundiced eyes? Occupation involving water exposure or leptospirosis-endemic area?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 5.5
            [
                'box_id'           => 'BOX0000022',
                'question_text'    => 'ไข้สูงตลอดเวลา น้ำมือโต? หรืออยู่ในแวดล้อมที่มีการระบาดของไทฟอยด์?',
                'question_text_en' => 'Persistent high fever and splenomegaly? Or in typhoid-endemic area?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 6
            [
                'box_id'           => 'BOX0000023',
                'question_text'    => 'หอบหรือหายใจเร็วจนผิดปกติ? หรือปวดท้องรุนแรง/กดเจ็บท้องมาก?',
                'question_text_en' => 'Abnormal rapid breathing? Or severe abdominal pain/tenderness?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 7
            [
                'box_id'           => 'BOX0000024',
                'question_text'    => 'ชีด? ดีซ่าน? มีจุดแดง/จ้ำเขียวขึ้นตามตัว? ปวดข้อรุนแรง/ข้อบวมแดงร้อน? หรือปวดหลังรุนแรง?',
                'question_text_en' => 'Pallor? Jaundice? Petechiae/ecchymosis? Severe arthritis? Or severe back pain?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 8
            [
                'box_id'           => 'BOX0000025',
                'question_text'    => 'เจ็บหน้าอกแปลบเวลาหายใจเข้าลึกๆ? เจ็บหน้าอกมาก? หรือใช้เครื่องฟังปอดมีเสียงกรอบแกรบ (crepitation)?',
                'question_text_en' => 'Pleuritic chest pain on deep inspiration? Severe chest pain? Or lung crepitation on auscultation?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 9
            [
                'box_id'           => 'BOX0000026',
                'question_text'    => 'มีรอยแผลเหมือนถูกบุหรี่จี้?',
                'question_text_en' => 'Eschar (cigarette burn-like lesion)?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 9.1
            [
                'box_id'           => 'BOX0000027',
                'question_text'    => 'มีประวัติเคยเข้าไปในทุ่งหญ้า หรือไร่สวน ภายในระยะ 3 สัปดาห์ที่ผ่านมา?',
                'question_text_en' => 'History of being in grassland or farm within 3 weeks?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 10
            [
                'box_id'           => 'BOX0000028',
                'question_text'    => 'เท้าบวม 2 ข้าง?',
                'question_text_en' => 'Bilateral leg edema?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 10.1
            [
                'box_id'           => 'BOX0000029',
                'question_text'    => 'ปัสสาวะสีแดงเหมือนล้างเนื้อ?',
                'question_text_en' => 'Coca-cola colored urine?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 11
            [
                'box_id'           => 'BOX0000030',
                'question_text'    => 'ทอนซิลโตแดงหรือเป็นหนอง?',
                'question_text_en' => 'Enlarged red tonsils or tonsillar exudate?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 11.1
            [
                'box_id'           => 'BOX0000031',
                'question_text'    => 'มีผื่นแดงขึ้นทั่วตัวหลังมีไข้ 1-2 วัน? และตรวจพบผื่นสตรอเบอร์รี่?',
                'question_text_en' => 'Generalized rash 1-2 days after fever onset? Strawberry tongue?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 12
            [
                'box_id'           => 'BOX0000032',
                'question_text'    => 'เหงือกบวมอักเสบเป็นหนอง?',
                'question_text_en' => 'Purulent gingivitis?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 13
            [
                'box_id'           => 'BOX0000033',
                'question_text'    => 'มีการอักเสบที่ผิวหนัง?',
                'question_text_en' => 'Skin infection?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 14
            [
                'box_id'           => 'BOX0000034',
                'question_text'    => 'มีไข้สูง อ่อนเพลีย คลื่นไส้ ปวดศีรษะ เวียนศีรษะ หลังเผชิญคลื่นความร้อน หรือทำงาน/ออกกำลังกายท่ามกลางอากาศร้อน?',
                'question_text_en' => 'High fever, malaise, nausea, headache, dizziness after heat exposure or exertion in hot environment?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 15
            [
                'box_id'           => 'BOX0000035',
                'question_text'    => 'มีน้ำมูกหรือไอ? มีผื่นหรือตุ่มขึ้นตามตัว? ต่อมน้ำเหลืองโต? คอบวม? ถ่ายเป็นนรุนแรงหรือเป็นบิด? หรือปัสสาวะแสบขัด?',
                'question_text_en' => 'Rhinorrhea or cough? Rash or skin lesions? Lymphadenopathy? Sore throat? Severe diarrhea or dysentery? Or dysuria?',
                'question_type'    => 'M',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 16
            [
                'box_id'           => 'BOX0000036',
                'question_text'    => 'ไข้สูงตลอดเวลา?',
                'question_text_en' => 'Persistent high fever?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 16.1
            [
                'box_id'           => 'BOX0000037',
                'question_text'    => 'ทดสอบทูร์นิเคต์ให้ผลบวก?',
                'question_text_en' => 'Positive tourniquet test?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 16.2
            [
                'box_id'           => 'BOX0000038',
                'question_text'    => 'หน้าแดง เปลือกตาแดง?',
                'question_text_en' => 'Facial flushing or red eyelids?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 16.3
            [
                'box_id'           => 'BOX0000039',
                'question_text'    => 'ในเด็กอายุต่ำกว่า 3 ปี?',
                'question_text_en' => 'Child under 3 years of age?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
            // กรอบที่ 16.4
            [
                'box_id'           => 'BOX0000040',
                'question_text'    => 'ตับโตหรือม้ามโต? หรืออยู่ในแวดล้อมที่มีการระบาดของไทฟอยด์?',
                'question_text_en' => 'Hepatomegaly or splenomegaly? Or in typhoid outbreak area?',
                'question_type'    => 'S',
                'diagram_id'       => 'DG001',
            ],
        ];

        foreach ($boxes as $box) {
            DB::table('question_boxes')->insertOrIgnore(array_merge($box, [
                'status'     => '1',
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Set entry_box_id ของ diagram
        DB::table('diagrams')
            ->where('diagram_id', 'DG001')
            ->update(['entry_box_id' => 'BOX0000001']);

        // ============================================================
        // 7. ANSWER CHOICES (ใช่/ไม่ใช่ + next_box_id)
        // ============================================================
        $choices = [
            // BOX1: ไม่ค่อยรู้สึกตัว ฯลฯ
            ['choice_id' => 'CHO000001', 'box_id' => 'BOX0000001', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => 'BOX0000002'],
            ['choice_id' => 'CHO000002', 'box_id' => 'BOX0000001', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000003'],

            // BOX1.1: คอแข็ง?
            ['choice_id' => 'CHO000003', 'box_id' => 'BOX0000002', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → เยื่อหุ้มสมองอักเสบ/เลือดออกในสมอง/ฝีสมอง
            ['choice_id' => 'CHO000004', 'box_id' => 'BOX0000002', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000003'],

            // BOX1.2: เคยไปในดงมาลาเรีย?
            ['choice_id' => 'CHO000005', 'box_id' => 'BOX0000003', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → มาลาเรียขึ้นสมอง
            ['choice_id' => 'CHO000006', 'box_id' => 'BOX0000003', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000004'],

            // BOX1.3: ถูกสุนัขกัด + กลัวน้ำ?
            ['choice_id' => 'CHO000007', 'box_id' => 'BOX0000004', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → โรคพิษสุนัขบ้า
            ['choice_id' => 'CHO000008', 'box_id' => 'BOX0000004', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000005'],

            // BOX1.4: หลังเผชิญคลื่นความร้อน?
            ['choice_id' => 'CHO000009', 'box_id' => 'BOX0000005', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → โรคลมแดด
            ['choice_id' => 'CHO000010', 'box_id' => 'BOX0000005', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000006'],

            // BOX1.5: รู้สึกตัวดี?
            ['choice_id' => 'CHO000011', 'box_id' => 'BOX0000006', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 1, 'next_box_id' => null], // → สมองอักเสบ/เล็บโตสไปโรซิส
            ['choice_id' => 'CHO000012', 'box_id' => 'BOX0000006', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 2, 'next_box_id' => 'BOX0000007'],

            // BOX1.6: ขณะให้แสงเลเซอร์?
            ['choice_id' => 'CHO000013', 'box_id' => 'BOX0000007', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → บาดทะยัก
            ['choice_id' => 'CHO000014', 'box_id' => 'BOX0000007', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000008'],

            // BOX1.7: เด็ก 6 เดือน-5 ปี ชักเองหยุด?
            ['choice_id' => 'CHO000015', 'box_id' => 'BOX0000008', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → ไข้ชักในเด็ก
            ['choice_id' => 'CHO000016', 'box_id' => 'BOX0000008', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000009'],

            // BOX2: ช็อก?
            ['choice_id' => 'CHO000017', 'box_id' => 'BOX0000009', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → ช็อก ด่วน
            ['choice_id' => 'CHO000018', 'box_id' => 'BOX0000009', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000010'],

            // BOX3: แขนขาอ่อนแรงฉับพลัน?
            ['choice_id' => 'CHO000019', 'box_id' => 'BOX0000010', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → โปลิโอ/ไขสันหลังอักเสบ
            ['choice_id' => 'CHO000020', 'box_id' => 'BOX0000010', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000011'],

            // BOX4: ไข้นานเกิน 1 เดือน?
            ['choice_id' => 'CHO000021', 'box_id' => 'BOX0000011', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => 'BOX0000012'],
            ['choice_id' => 'CHO000022', 'box_id' => 'BOX0000011', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000017'],

            // BOX4.1: ไอ + น้ำหนักลด?
            ['choice_id' => 'CHO000023', 'box_id' => 'BOX0000012', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → วัณโรค/มะเร็งปอด/เมลิออยโดซิส
            ['choice_id' => 'CHO000024', 'box_id' => 'BOX0000012', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000013'],

            // BOX4.2: ปวดข้อ + ผมร่วง + ผื่นผีเสื้อ?
            ['choice_id' => 'CHO000025', 'box_id' => 'BOX0000013', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → SLE
            ['choice_id' => 'CHO000026', 'box_id' => 'BOX0000013', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000014'],

            // BOX4.3: ไข้หนาวสั่นเว้นวัน + ดงมาลาเรีย?
            ['choice_id' => 'CHO000027', 'box_id' => 'BOX0000014', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → มาลาเรียเรื้อรัง
            ['choice_id' => 'CHO000028', 'box_id' => 'BOX0000014', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000015'],

            // BOX4.4: จุดแดงเยื่อตา + murmur + ม้ามโต?
            ['choice_id' => 'CHO000029', 'box_id' => 'BOX0000015', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → เยื่อบุหัวใจอักเสบเรื้อรัง
            ['choice_id' => 'CHO000030', 'box_id' => 'BOX0000015', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000016'],

            // BOX4.5: จ้ำเขียว + ต่อมน้ำเหลืองโต?
            ['choice_id' => 'CHO000031', 'box_id' => 'BOX0000016', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → มะเร็งเม็ดเลือดขาว/มะเร็งต่อมน้ำเหลือง
            ['choice_id' => 'CHO000032', 'box_id' => 'BOX0000016', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => null], // → ภายใน 3 วัน อาจเป็นมะเร็ง/มาลาเรียเรื้อรัง/เมลิออยโดซิส ฯลฯ

            // BOX5: ไข้เกิน 7 วัน หรือหนาวสั่นมาก?
            ['choice_id' => 'CHO000033', 'box_id' => 'BOX0000017', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => 'BOX0000018'],
            ['choice_id' => 'CHO000034', 'box_id' => 'BOX0000017', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000023'],

            // BOX5.1: รอยแผลถูกบุหรี่จี้ + ทุ่งหญ้า?
            ['choice_id' => 'CHO000035', 'box_id' => 'BOX0000018', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → สครับไทฟัส
            ['choice_id' => 'CHO000036', 'box_id' => 'BOX0000018', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000019'],

            // BOX5.2: จับไข้หนาวสั่นวันละครั้ง + ดงมาลาเรีย?
            ['choice_id' => 'CHO000037', 'box_id' => 'BOX0000019', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → มาลาเรีย
            ['choice_id' => 'CHO000038', 'box_id' => 'BOX0000019', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000020'],

            // BOX5.3: เจาะเจ็บข้าง + ปัสสาวะขุ่น?
            ['choice_id' => 'CHO000039', 'box_id' => 'BOX0000020', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → กรวยไตอักเสบเฉียบพลัน
            ['choice_id' => 'CHO000040', 'box_id' => 'BOX0000020', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000021'],

            // BOX5.4: ปวดกล้ามเนื้อมาก + ตาแดง/เหลือง + เล็บโตสไปโรซิส?
            ['choice_id' => 'CHO000041', 'box_id' => 'BOX0000021', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → เล็บโตสไปโรซิส
            ['choice_id' => 'CHO000042', 'box_id' => 'BOX0000021', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000022'],

            // BOX5.5: ไข้สูงตลอด + ม้ามโต/ไทฟอยด์?
            ['choice_id' => 'CHO000043', 'box_id' => 'BOX0000022', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → ไทฟอยด์
            ['choice_id' => 'CHO000044', 'box_id' => 'BOX0000022', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => null], // → แอนแทรกซ์/เมลิออยโดซิส ฯลฯ

            // BOX6: หอบ + ปวดท้องรุนแรง?
            ['choice_id' => 'CHO000045', 'box_id' => 'BOX0000023', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → ด่วน ดูแผนภูมิ 3 และ 44
            ['choice_id' => 'CHO000046', 'box_id' => 'BOX0000023', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000024'],

            // BOX7: ชีด/ดีซ่าน/จ้ำเขียว/ข้อบวม/ปวดหลัง?
            ['choice_id' => 'CHO000047', 'box_id' => 'BOX0000024', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → ภายใน 24 ชั่วโมง
            ['choice_id' => 'CHO000048', 'box_id' => 'BOX0000024', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000025'],

            // BOX8: เจ็บหน้าอก + crepitation?
            ['choice_id' => 'CHO000049', 'box_id' => 'BOX0000025', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → ปอดอักเสบ
            ['choice_id' => 'CHO000050', 'box_id' => 'BOX0000025', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000026'],

            // BOX9: รอยแผลถูกบุหรี่จี้?
            ['choice_id' => 'CHO000051', 'box_id' => 'BOX0000026', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => 'BOX0000027'],
            ['choice_id' => 'CHO000052', 'box_id' => 'BOX0000026', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000028'],

            // BOX9.1: ไปทุ่งหญ้า 3 สัปดาห์?
            ['choice_id' => 'CHO000053', 'box_id' => 'BOX0000027', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → สครับไทฟัส
            ['choice_id' => 'CHO000054', 'box_id' => 'BOX0000027', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => null], // → แอนแทรกซ์

            // BOX10: เท้าบวม 2 ข้าง?
            ['choice_id' => 'CHO000055', 'box_id' => 'BOX0000028', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => 'BOX0000029'],
            ['choice_id' => 'CHO000056', 'box_id' => 'BOX0000028', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000030'],

            // BOX10.1: ปัสสาวะสีแดงเหมือนล้างเนื้อ?
            ['choice_id' => 'CHO000057', 'box_id' => 'BOX0000029', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → หน่วยไตอักเสบเฉียบพลันเลียบพัน
            ['choice_id' => 'CHO000058', 'box_id' => 'BOX0000029', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => null], // → ภายใน 3 วัน เพื่อตรวจหาสาเหตุ

            // BOX11: ทอนซิลโต/หนอง?
            ['choice_id' => 'CHO000059', 'box_id' => 'BOX0000030', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => 'BOX0000031'],
            ['choice_id' => 'CHO000060', 'box_id' => 'BOX0000030', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000032'],

            // BOX11.1: ผื่นแดงทั่วตัว + สตรอเบอร์รี่?
            ['choice_id' => 'CHO000061', 'box_id' => 'BOX0000031', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → อีดำอีแดง
            ['choice_id' => 'CHO000062', 'box_id' => 'BOX0000031', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => null], // → ทอนซิลอักเสบ

            // BOX12: เหงือกบวม?
            ['choice_id' => 'CHO000063', 'box_id' => 'BOX0000032', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → เหงือกอักเสบ
            ['choice_id' => 'CHO000064', 'box_id' => 'BOX0000032', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000033'],

            // BOX13: ผิวหนังอักเสบ?
            ['choice_id' => 'CHO000065', 'box_id' => 'BOX0000033', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → ฝี/แผลพุพอง/ไฟลามทุ่ง
            ['choice_id' => 'CHO000066', 'box_id' => 'BOX0000033', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000034'],

            // BOX14: ไข้สูง + ออกกำลังกายในที่ร้อน?
            ['choice_id' => 'CHO000067', 'box_id' => 'BOX0000034', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → ภาวะหมดแรงจากความร้อน
            ['choice_id' => 'CHO000068', 'box_id' => 'BOX0000034', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000035'],

            // BOX15: น้ำมูก/ผื่น/ต่อมน้ำเหลืองโต/ถ่ายเสีย/ปัสสาวะแสบ?
            ['choice_id' => 'CHO000069', 'box_id' => 'BOX0000035', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → ดูแผนภูมิตามอาการที่พบร่วม
            ['choice_id' => 'CHO000070', 'box_id' => 'BOX0000035', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000036'],

            // BOX16: ไข้สูงตลอด?
            ['choice_id' => 'CHO000071', 'box_id' => 'BOX0000036', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => 'BOX0000037'],
            ['choice_id' => 'CHO000072', 'box_id' => 'BOX0000036', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => null], // → รักษาตามอาการ ไข้หวัดทั่วไป

            // BOX16.1: ทูร์นิเคต์บวก?
            ['choice_id' => 'CHO000073', 'box_id' => 'BOX0000037', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → ไข้เลือดออก
            ['choice_id' => 'CHO000074', 'box_id' => 'BOX0000037', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000038'],

            // BOX16.2: หน้าแดง เปลือกตาแดง?
            ['choice_id' => 'CHO000075', 'box_id' => 'BOX0000038', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → ไข้เลือดออกระยะแรก
            ['choice_id' => 'CHO000076', 'box_id' => 'BOX0000038', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000039'],

            // BOX16.3: เด็กอายุต่ำกว่า 3 ปี?
            ['choice_id' => 'CHO000077', 'box_id' => 'BOX0000039', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → ไข้ผื่นกุหลาบในทารก
            ['choice_id' => 'CHO000078', 'box_id' => 'BOX0000039', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => 'BOX0000040'],

            // BOX16.4: ตับ/ม้ามโต หรือ ไทฟอยด์?
            ['choice_id' => 'CHO000079', 'box_id' => 'BOX0000040', 'choice_text' => 'ใช่', 'choice_text_en' => 'Yes', 'order' => 1, 'next_box_id' => null], // → ไทฟอยด์
            ['choice_id' => 'CHO000080', 'box_id' => 'BOX0000040', 'choice_text' => 'ไม่ใช่', 'choice_text_en' => 'No', 'order' => 2, 'next_box_id' => null], // → รักษาตามอาการ ไข้หวัดทั่วไป
        ];

        foreach ($choices as $choice) {
            DB::table('answer_choices')->insertOrIgnore(array_merge($choice, [
                'status'     => '1',
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // ============================================================
        // 8. DIAGNOSIS RULES + RULE CONDITIONS
        // ============================================================
        $rules = [
            // Rule 1: คอแข็ง → เยื่อหุ้มสมองอักเสบ
            [
                'rule_id'           => 'RULE000001',
                'rule_name'         => 'ไข้ + ไม่ค่อยรู้สึกตัว + คอแข็ง → เยื่อหุ้มสมองอักเสบ',
                'urgency_level'     => 'R',
                'diagram_id'        => 'DG001',
                'disease_id'        => 'DIS000001',
                'medical_reference' => 'แผนภูมิที่ 1 กรอบ 1.1 หน้า 8',
                'description'       => 'ไข้ร่วมกับไม่ค่อยรู้สึกตัวและคอแข็ง บ่งชี้เยื่อหุ้มสมองอักเสบ ต้องส่งโรงพยาบาลด่วน',
                'conditions' => [
                    ['box_id' => 'BOX0000001', 'choice_id' => 'CHO000001', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000002', 'choice_id' => 'CHO000003', 'logic_operator' => 'AND'],
                ],
            ],
            // Rule 2: ไม่ค่อยรู้สึกตัว + คอแข็ง → เลือดออกในสมอง
            [
                'rule_id'           => 'RULE000002',
                'rule_name'         => 'ไข้ + ไม่ค่อยรู้สึกตัว + คอแข็ง → เลือดออกในสมอง',
                'urgency_level'     => 'R',
                'diagram_id'        => 'DG001',
                'disease_id'        => 'DIS000002',
                'medical_reference' => 'แผนภูมิที่ 1 กรอบ 1.1 หน้า 8',
                'description'       => 'ไข้ร่วมกับระดับความรู้สึกตัวลดลงและคอแข็ง อาจเป็นเลือดออกในสมอง ต้องส่งโรงพยาบาลด่วน',
                'conditions' => [
                    ['box_id' => 'BOX0000001', 'choice_id' => 'CHO000001', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000002', 'choice_id' => 'CHO000003', 'logic_operator' => 'AND'],
                ],
            ],
            // Rule 3: มาลาเรียขึ้นสมอง
            [
                'rule_id'           => 'RULE000003',
                'rule_name'         => 'ไข้ + ซึม + ประวัติดงมาลาเรีย → มาลาเรียขึ้นสมอง',
                'urgency_level'     => 'R',
                'diagram_id'        => 'DG001',
                'disease_id'        => 'DIS000004',
                'medical_reference' => 'แผนภูมิที่ 1 กรอบ 1.2 หน้า 8',
                'description'       => 'ไข้ร่วมกับซึมและประวัติเข้าดงมาลาเรีย ต้องระวังมาลาเรียขึ้นสมอง ส่งโรงพยาบาลด่วน',
                'conditions' => [
                    ['box_id' => 'BOX0000001', 'choice_id' => 'CHO000001', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000002', 'choice_id' => 'CHO000004', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000003', 'choice_id' => 'CHO000005', 'logic_operator' => 'AND'],
                ],
            ],
            // Rule 4: โรคพิษสุนัขบ้า
            [
                'rule_id'           => 'RULE000004',
                'rule_name'         => 'ไข้ + ซึม + ถูกสุนัขกัด + กลัวน้ำ → โรคพิษสุนัขบ้า',
                'urgency_level'     => 'R',
                'diagram_id'        => 'DG001',
                'disease_id'        => 'DIS000005',
                'medical_reference' => 'แผนภูมิที่ 1 กรอบ 1.3 หน้า 8',
                'description'       => 'ไข้ร่วมกับซึมและประวัติถูกสัตว์กัดและกลัวน้ำกลัวลม บ่งชี้โรคพิษสุนัขบ้า',
                'conditions' => [
                    ['box_id' => 'BOX0000001', 'choice_id' => 'CHO000001', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000002', 'choice_id' => 'CHO000004', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000003', 'choice_id' => 'CHO000006', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000004', 'choice_id' => 'CHO000007', 'logic_operator' => 'AND'],
                ],
            ],
            // Rule 5: โรคลมแดด
            [
                'rule_id'           => 'RULE000005',
                'rule_name'         => 'ไข้ + ซึม + หลังเผชิญคลื่นความร้อน → โรคลมแดด',
                'urgency_level'     => 'R',
                'diagram_id'        => 'DG001',
                'disease_id'        => 'DIS000006',
                'medical_reference' => 'แผนภูมิที่ 1 กรอบ 1.4 หน้า 8',
                'description'       => 'ไข้ร่วมกับซึมหลังเผชิญความร้อน ต้องปฐมพยาบาลและส่งโรงพยาบาลด่วน',
                'conditions' => [
                    ['box_id' => 'BOX0000001', 'choice_id' => 'CHO000001', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000002', 'choice_id' => 'CHO000004', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000003', 'choice_id' => 'CHO000006', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000004', 'choice_id' => 'CHO000008', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000005', 'choice_id' => 'CHO000009', 'logic_operator' => 'AND'],
                ],
            ],
            // Rule 6: ไข้ชักในเด็ก
            [
                'rule_id'           => 'RULE000006',
                'rule_name'         => 'ไข้ + ชักเองหยุด + เด็ก 6 เดือน-5 ปี → ไข้ชักในเด็ก',
                'urgency_level'     => 'P',
                'diagram_id'        => 'DG001',
                'disease_id'        => 'DIS000009',
                'medical_reference' => 'แผนภูมิที่ 1 กรอบ 1.7 หน้า 9',
                'description'       => 'ไข้สูงในเด็กอายุ 6 เดือน - 5 ปี ชักและหยุดเองภายใน 15 นาที บ่งชี้ไข้ชักในเด็ก',
                'conditions' => [
                    ['box_id' => 'BOX0000001', 'choice_id' => 'CHO000001', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000008', 'choice_id' => 'CHO000015', 'logic_operator' => 'AND'],
                ],
            ],
            // Rule 7: สครับไทฟัส (ไข้ > 7 วัน + eschar + ทุ่งหญ้า)
            [
                'rule_id'           => 'RULE000007',
                'rule_name'         => 'ไข้ > 7 วัน + รอยแผล eschar + ไปทุ่งหญ้า → สครับไทฟัส',
                'urgency_level'     => 'P',
                'diagram_id'        => 'DG001',
                'disease_id'        => 'DIS000014',
                'medical_reference' => 'แผนภูมิที่ 1 กรอบ 5.1 หน้า 10',
                'description'       => 'ไข้เกิน 7 วัน มีรอยแผล eschar และประวัติเข้าทุ่งหญ้าภายใน 3 สัปดาห์ บ่งชี้สครับไทฟัส',
                'conditions' => [
                    ['box_id' => 'BOX0000017', 'choice_id' => 'CHO000033', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000018', 'choice_id' => 'CHO000035', 'logic_operator' => 'AND'],
                ],
            ],
            // Rule 8: มาลาเรีย (ไข้ > 7 วัน + หนาวสั่น + ดงมาลาเรีย)
            [
                'rule_id'           => 'RULE000008',
                'rule_name'         => 'ไข้ > 7 วัน + หนาวสั่นวันละครั้ง + ประวัติดงมาลาเรีย → มาลาเรีย',
                'urgency_level'     => 'P',
                'diagram_id'        => 'DG001',
                'disease_id'        => 'DIS000016',
                'medical_reference' => 'แผนภูมิที่ 1 กรอบ 5.2 หน้า 11',
                'description'       => 'ไข้เกิน 7 วัน จับไข้หนาวสั่นวันละครั้งหรือวันเว้นวัน มีประวัติดงมาลาเรีย บ่งชี้มาลาเรีย',
                'conditions' => [
                    ['box_id' => 'BOX0000017', 'choice_id' => 'CHO000033', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000018', 'choice_id' => 'CHO000036', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000019', 'choice_id' => 'CHO000037', 'logic_operator' => 'AND'],
                ],
            ],
            // Rule 9: ไทฟอยด์ (ไข้ > 7 วัน + ม้ามโต/ดงไทฟอยด์)
            [
                'rule_id'           => 'RULE000009',
                'rule_name'         => 'ไข้ > 7 วัน + ไข้สูงตลอด + ม้ามโต → ไทฟอยด์',
                'urgency_level'     => 'P',
                'diagram_id'        => 'DG001',
                'disease_id'        => 'DIS000017',
                'medical_reference' => 'แผนภูมิที่ 1 กรอบ 5.5 หน้า 11',
                'description'       => 'ไข้เกิน 7 วัน ไข้สูงตลอดเวลา มีม้ามโตหรืออยู่ในแหล่งระบาดไทฟอยด์ บ่งชี้ไทฟอยด์',
                'conditions' => [
                    ['box_id' => 'BOX0000017', 'choice_id' => 'CHO000033', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000022', 'choice_id' => 'CHO000043', 'logic_operator' => 'AND'],
                ],
            ],
            // Rule 10: ไข้เลือดออก (ไข้สูงตลอด + ทูร์นิเคต์บวก)
            [
                'rule_id'           => 'RULE000010',
                'rule_name'         => 'ไข้สูงตลอด + ทูร์นิเคต์บวก → ไข้เลือดออก',
                'urgency_level'     => 'P',
                'diagram_id'        => 'DG001',
                'disease_id'        => 'DIS000015',
                'medical_reference' => 'แผนภูมิที่ 1 กรอบ 16.1 หน้า 14',
                'description'       => 'ไข้สูงตลอดเวลาและทดสอบทูร์นิเคต์ให้ผลบวก บ่งชี้ไข้เลือดออก ต้องติดตามใกล้ชิด',
                'conditions' => [
                    ['box_id' => 'BOX0000036', 'choice_id' => 'CHO000071', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000037', 'choice_id' => 'CHO000073', 'logic_operator' => 'AND'],
                ],
            ],
            // Rule 11: ปอดอักเสบ (ไข้ + เจ็บหน้าอก + crepitation)
            [
                'rule_id'           => 'RULE000011',
                'rule_name'         => 'ไข้ + เจ็บหน้าอก + เสียงกรอบแกรบในปอด → ปอดอักเสบ',
                'urgency_level'     => 'P',
                'diagram_id'        => 'DG001',
                'disease_id'        => 'DIS000020',
                'medical_reference' => 'แผนภูมิที่ 1 กรอบ 8 หน้า 12',
                'description'       => 'ไข้ร่วมกับเจ็บหน้าอกและฟังปอดได้ยินเสียง crepitation บ่งชี้ปอดอักเสบ',
                'conditions' => [
                    ['box_id' => 'BOX0000025', 'choice_id' => 'CHO000049', 'logic_operator' => 'AND'],
                ],
            ],
            // Rule 12: ไข้หวัดใหญ่/ไข้ไม่ทราบสาเหตุ (ไข้ + ไม่มีสัญญาณอันตราย)
            [
                'rule_id'           => 'RULE000012',
                'rule_name'         => 'ไข้ + ไม่มีสัญญาณเตือน → ไข้หวัดใหญ่/ไข้ไม่ทราบสาเหตุ',
                'urgency_level'     => 'G',
                'diagram_id'        => 'DG001',
                'disease_id'        => 'DIS000018',
                'medical_reference' => 'แผนภูมิที่ 1 กรอบ 17 หน้า 15',
                'description'       => 'ไข้ไม่มีสัญญาณอันตราย น่าจะเป็นไข้หวัดไวรัสทั่วไป รักษาตามอาการ',
                'conditions' => [
                    ['box_id' => 'BOX0000001', 'choice_id' => 'CHO000002', 'logic_operator' => 'AND'],
                    ['box_id' => 'BOX0000036', 'choice_id' => 'CHO000072', 'logic_operator' => 'AND'],
                ],
            ],
        ];

        $conditionCounter = 1;
        foreach ($rules as $ruleData) {
            $conditions = $ruleData['conditions'];
            unset($ruleData['conditions']);

            DB::table('diagnosis_rules')->insertOrIgnore(array_merge($ruleData, [
                'status'     => '1',
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            foreach ($conditions as $condition) {
                $condId = 'COND' . str_pad($conditionCounter++, 6, '0', STR_PAD_LEFT);
                DB::table('rule_conditions')->insertOrIgnore([
                    'condition_id'   => $condId,
                    'rule_id'        => $ruleData['rule_id'],
                    'box_id'         => $condition['box_id'],
                    'choice_id'      => $condition['choice_id'],
                    'logic_operator' => $condition['logic_operator'],
                    'status'         => '1',
                    'created_by'     => $adminId,
                    'updated_by'     => $adminId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        $this->command->info('✅ FeverDiagramSeeder: สร้างข้อมูลแผนภูมิไข้สำเร็จ');
        $this->command->info('   - symptom_categories: 1 record');
        $this->command->info('   - main_symptoms: 1 record (ไข้)');
        $this->command->info('   - disease_categories: 2 records');
        $this->command->info('   - diseases: 21 records');
        $this->command->info('   - diagrams: 1 record (DG001)');
        $this->command->info('   - question_boxes: 40 records');
        $this->command->info('   - answer_choices: 80 records');
        $this->command->info('   - diagnosis_rules: 12 records');
        $this->command->info('   - rule_conditions: ~30 records');
    }
}
