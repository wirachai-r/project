<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComprehensiveDiseaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // ============================================================
        // 1. กำหนดหมวดหมู่โรคทั้งหมด (Disease Categories)
        // ตามโครงสร้างสารบัญหลัก บทที่ 1 - 18
        // ============================================================
        $categories = [
            '000001' => [
                'name'    => 'โรคระบบทางเดินหายใจและโรคติดต่อโดยทางเดินหายใจ',
                'name_en' => 'Diseases of the Respiratory System and Respiratory Infections'
            ],
            '000002' => [
                'name'    => 'โรคระบบทางเดินอาหารและโรคติดต่อโดยทางเดินอาหาร',
                'name_en' => 'Diseases of the Digestive System and Gastrointestinal Infections'
            ],
            '000003' => [
                'name'    => 'โรคระบบประสาทและสมอง',
                'name_en' => 'Diseases of the Nervous System and Brain'
            ],
            '000004' => [
                'name'    => 'โรคระบบไหลเวียนโลหิตและโรคเลือด',
                'name_en' => 'Diseases of the Circulatory System and Hematologic Diseases'
            ],
            '000005' => [
                'name'    => 'โรคระบบกระดูกและกล้ามเนื้อ',
                'name_en' => 'Diseases of the Musculoskeletal System and Connective Tissue'
            ],
            '000006' => [
                'name'    => 'โรคระบบต่อมไร้ท่อและโภชนาการ',
                'name_en' => 'Endocrine, Nutritional and Metabolic Diseases'
            ],
            '000007' => [
                'name'    => 'โรคระบบทางเดินปัสสาวะ',
                'name_en' => 'Diseases of the Urinary System'
            ],
            '000008' => [
                'name'    => 'โรคระบบอวัยวะสืบพันธุ์ชาย',
                'name_en' => 'Diseases of the Male Reproductive System'
            ],
            '000009' => [
                'name'    => 'โรคระบบอวัยวะสืบพันธุ์หญิงและการตั้งครรภ์',
                'name_en' => 'Diseases of the Female Reproductive System and Pregnancy-related'
            ],
            '000010' => [
                'name'    => 'โรคหู',
                'name_en' => 'Diseases of the Ear and Mastoid Process'
            ],
            '000011' => [
                'name'    => 'โรคตา',
                'name_en' => 'Diseases of the Eye and Adnexa'
            ],
            '000012' => [
                'name'    => 'โรคผิวหนัง',
                'name_en' => 'Diseases of the Skin and Subcutaneous Tissue'
            ],
            '000013' => [
                'name'    => 'โรคติดต่อทางเพศสัมพันธ์',
                'name_en' => 'Sexually Transmitted Infections'
            ],
            '000014' => [
                'name'    => 'โรคที่เกิดจากอุบัติเหตุ สารพิษ และสัตว์พิษ',
                'name_en' => 'Injury, Poisoning and Certain Other Consequences of External Causes'
            ],
            '000015' => [
                'name'    => 'โรคติดเชื้อ',
                'name_en' => 'Infectious Diseases'
            ],
            '000016' => [
                'name'    => 'โรคพยาธิ',
                'name_en' => 'Parasitic Diseases'
            ],
            '000017' => [
                'name'    => 'โรคมะเร็ง',
                'name_en' => 'Neoplasms / Cancers'
            ],
            '000018' => [
                'name'    => 'โรคติดเชื้ออุบัติใหม่',
                'name_en' => 'Emerging Infectious Diseases'
            ],
        ];

        // บันทึกหมวดหมู่โรคลงฐานข้อมูล
        foreach ($categories as $catId => $catData) {
            DB::table('disease_categories')->insertOrIgnore([
                'disease_category_id' => $catId,
                'category_name'       => $catData['name'],
                'category_name_en'    => $catData['name_en'],
                'status'              => '1',
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }


        // ============================================================
        // 2. กำหนดรายชื่อโรคทั้งหมด แยกตามรหัสหมวดหมู่ (Diseases)
        // ดึงข้อมูลตรงตามสารบัญภาษาไทยในรูปภาพตัวเล่มคู่มือ
        // ============================================================
        $diseasesByGroup = [
            // บทที่ 1 โรคระบบทางเดินหายใจและโรคติดต่อโดยทางเดินหายใจ
            '000001' => [
                'common_cold'             => ['name' => 'ไข้หวัด', 'name_en' => 'Common Cold', 'urgency' => 'G'],
                'influenza'               => ['name' => 'ไข้หวัดใหญ่', 'name_en' => 'Influenza', 'urgency' => 'G'],
                'measles'                 => ['name' => 'หัด', 'name_en' => 'Measles', 'urgency' => 'G'],
                'german_measles'          => ['name' => 'หัดเยอรมัน/หัดหลบใน', 'name_en' => 'German Measles / Rubella', 'urgency' => 'G'],
                'roseola_infantum'        => ['name' => 'ไข้ผื่นกุหลาบในทารก/ส่าไข้', 'name_en' => 'Roseola Infantum', 'urgency' => 'G'],
                'chickenpox'              => ['name' => 'อีสุกอีใส', 'name_en' => 'Chickenpox / Varicella', 'urgency' => 'G'],
                'mumps'                   => ['name' => 'คางทูม', 'name_en' => 'Mumps', 'urgency' => 'G'],
                'pharyngitis'             => ['name' => 'คอหอยอักเสบ', 'name_en' => 'Pharyngitis', 'urgency' => 'G'],
                'tonsillitis'             => ['name' => 'ทอนซิลอักเสบ', 'name_en' => 'Acute Tonsillitis', 'urgency' => 'G'],
                'scarlet_fever'           => ['name' => 'อีดำอีแดง', 'name_en' => 'Scarlet Fever', 'urgency' => 'Y'],
                'diphtheria'              => ['name' => 'คอตีบ/ดิฟทีเรีย', 'name_en' => 'Diphtheria', 'urgency' => 'R'],
                'croup'                   => ['name' => 'ครูป', 'name_en' => 'Croup', 'urgency' => 'R'],
                'acute_laryngitis'        => ['name' => 'กล่องเสียงอักเสบ', 'name_en' => 'Acute Laryngitis', 'urgency' => 'G'],
                'whooping_cough'          => ['name' => 'ไอกรน', 'name_en' => 'Whooping Cough', 'urgency' => 'Y'],
                'pulmonary_tuberculosis'  => ['name' => 'วัณโรคปอด', 'name_en' => 'Pulmonary Tuberculosis', 'urgency' => 'Y'],
                'acute_bronchitis'        => ['name' => 'หลอดลมอักเสบเฉียบพลัน', 'name_en' => 'Acute Bronchitis', 'urgency' => 'G'],
                'chronic_bronchitis'      => ['name' => 'ภาวะปอดอุดกั้นเรื้อรัง/หลอดลมอักเสบเรื้อรัง/ถุงลมปอดโป่งพอง', 'name_en' => 'Chronic Bronchitis / Emphysema', 'urgency' => 'Y'],
                'asthma'                  => ['name' => 'หลอดลมพอง', 'name_en' => 'Bronchiectasis', 'urgency' => 'Y'],
                'bronchiolitis'           => ['name' => 'หลอดลมฝอยอักเสบ', 'name_en' => 'Acute Bronchiolitis', 'urgency' => 'Y'],
                'pneumonia'               => ['name' => 'ปอดอักเสบ/ปอดบวม', 'name_en' => 'Pneumonia', 'urgency' => 'Y'],
                'lung_abscess'            => ['name' => 'ภาวะมีหนองในโพรงเยื่อหุ้มปอด', 'name_en' => 'Lung Abscess / Empyema Thoracis', 'urgency' => 'R'],
                'pleurisy'                => ['name' => 'เยื่อหุ้มปอดอักเสบ', 'name_en' => 'Pleurisy', 'urgency' => 'Y'],
                'foreign_body_airway'     => ['name' => 'สำลักสิ่งแปลกปลอม/หลอดลมอุดกั้นจากสิ่งแปลกปลอม', 'name_en' => 'Foreign Body in Airway', 'urgency' => 'R'],
                'hiccup'                  => ['name' => 'สะอึก', 'name_en' => 'Hiccup', 'urgency' => 'G'],
                'allergic_rhinitis'       => ['name' => 'หวัดภูมิแพ้', 'name_en' => 'Allergic Rhinitis', 'urgency' => 'G'],
                'sinusitis'               => ['name' => 'ไซนัสอักเสบ', 'name_en' => 'Sinusitis', 'urgency' => 'G'],
                'peritonsillar_abscess'   => ['name' => 'เนื้อเยื่อรอบทอนซิลอักเสบเป็นหนอง', 'name_en' => 'Peritonsillar Abscess', 'urgency' => 'Y'],
                'nasal_polyps'            => ['name' => 'ติ่งเนื้อเมือกจมูก', 'name_en' => 'Nasal Polyps', 'urgency' => 'G'],
                'deviated_nasal_septum'   => ['name' => 'ผนังกั้นจมูกคด', 'name_en' => 'Deviated Nasal Septum', 'urgency' => 'G'],
                'epistaxis'               => ['name' => 'เลือดกำเดา', 'name_en' => 'Epistaxis / Nosebleed', 'urgency' => 'G'],
                'foreign_body_nose'       => ['name' => 'สิ่งแปลกปลอมเข้าจมูก', 'name_en' => 'Foreign Body in Nose', 'urgency' => 'G'],
                'sleep_apnea'             => ['name' => 'ภาวะหยุดหายใจขณะหลับ', 'name_en' => 'Sleep Apnea', 'urgency' => 'Y'],
            ],

            // บทที่ 2 โรคระบบทางเดินอาหารและโรคติดต่อโดยทางเดินอาหาร
            '000002' => [
                'diarrhea'                => ['name' => 'ท้องเดิน/อุจจาระร่วง', 'name_en' => 'Diarrhea', 'urgency' => 'Y'],
                'viral_diarrhea'          => ['name' => 'ท้องเดินจากไวรัส', 'name_en' => 'Viral Diarrhea', 'urgency' => 'G'],
                'bacterial_diarrhea'      => ['name' => 'ท้องเดินจากเชื้อไอการ์เดีย', 'name_en' => 'Giardiasis / Bacterial Diarrhea', 'urgency' => 'Y'],
                'irritable_bowel'         => ['name' => 'โรคสำไส้แปรปรวน', 'name_en' => 'Irritable Bowel Syndrome', 'urgency' => 'G'],
                'lactose_intolerance'     => ['name' => 'ภาวะพร่องแล็กเทส', 'name_en' => 'Lactose Intolerance', 'urgency' => 'G'],
                'food_poisoning'          => ['name' => 'อาหารเป็นพิษ', 'name_en' => 'Food Poisoning', 'urgency' => 'Y'],
                'cholera'                 => ['name' => 'อหิวาตกโรค', 'name_en' => 'Cholera', 'urgency' => 'R'],
                'amebic_dysentery'        => ['name' => 'บิด/บิดชิเกลลา/บิดอะมีบา', 'name_en' => 'Amebic / Bacillary Dysentery', 'urgency' => 'Y'],
                'typhoid_fever'           => ['name' => 'ไทฟอยด์/ไข้รากสาดน้อย', 'name_en' => 'Typhoid Fever', 'urgency' => 'Y'],
                'viral_hepatitis'         => ['name' => 'ตับอักเสบจากไวรัส', 'name_en' => 'Viral Hepatitis', 'urgency' => 'Y'],
                'liver_abscess'           => ['name' => 'ฝีตับอะมีบา', 'name_en' => 'Amebic Liver Abscess', 'urgency' => 'R'],
                'gallstones'              => ['name' => 'นิ่วในถุงน้ำดี/ถุงน้ำดีอักเสบ/ท่อน้ำตับอักเสบ', 'name_en' => 'Gallstones / Cholecystitis', 'urgency' => 'Y'],
                'biliary_atresia'         => ['name' => 'ดีซ่านสรีระในทารกแรกเกิด/ท่อน้ำดีตีบตันแต่กำเนิด', 'name_en' => 'Neonatal Jaundice / Biliary Atresia', 'urgency' => 'Y'],
                'cirrhosis'               => ['name' => 'ตับแข็ง', 'name_en' => 'Cirrhosis', 'urgency' => 'Y'],
                'liver_fluke'             => ['name' => 'โรคพยาธิใบไม้ตับ', 'name_en' => 'Liver Fluke Infestation', 'urgency' => 'G'],
                'appendicitis'            => ['name' => 'ไส้ติ่งอักเสบ', 'name_en' => 'Appendicitis', 'urgency' => 'R'],
                'peritonitis'             => ['name' => 'เยื่อบุช่องท้องอักเสบ', 'name_en' => 'Peritonitis', 'urgency' => 'R'],
                'pancreatitis'            => ['name' => 'ตับอ่อนอักเสบ', 'name_en' => 'Pancreatitis', 'urgency' => 'R'],
                'dyspepsia'               => ['name' => 'อาหารไม่ย่อย', 'name_en' => 'Dyspepsia', 'urgency' => 'G'],
                'gerd'                    => ['name' => 'โรคกรดไหลย้อน/เกิร์ด', 'name_en' => 'GERD', 'urgency' => 'G'],
                'gastritis'               => ['name' => 'กระเพาะอาหารอักเสบ', 'name_en' => 'Gastritis', 'urgency' => 'G'],
                'peptic_ulcer'            => ['name' => 'แผลเป็ปติก/กระเพาะอาหารทะลุ/แผลเป็ปติกทะลุ/กระเพาะอาหารแตก', 'name_en' => 'Peptic Ulcer / Perforation', 'urgency' => 'R'],
                'intestinal_obstruction'  => ['name' => 'กระเพาะหรือลำไส้อุดตัน', 'name_en' => 'Intestinal Obstruction', 'urgency' => 'R'],
                'vomiting_infants'        => ['name' => 'อาเจียนในเด็ก', 'name_en' => 'Vomiting in Infants / Children', 'urgency' => 'Y'],
                'gi_bleeding'             => ['name' => 'ตับ ม้าม หรือหัวใจฉีกขาด/เลือดตกใน', 'name_en' => 'Gastrointestinal Bleeding / Internal Bleeding', 'urgency' => 'R'],
                'hernia'                  => ['name' => 'ไส้เลื่อน', 'name_en' => 'Hernia', 'urgency' => 'Y'],
                'hemorrhoids'             => ['name' => 'ริดสีดวงทวาร/แผลปริที่ปากทวาร', 'name_en' => 'Hemorrhoids / Anal Fissure', 'urgency' => 'G'],
                'fistula_in_ano'          => ['name' => 'ฝีรอบทวารหนัก/ฝีก้นคอดรูสตู', 'name_en' => 'Fistula in ano / Perianal Abscess', 'urgency' => 'G'],
                'oral_ulcer'              => ['name' => 'แผลเปื่อยที่ปาก/แผลแอฟทัส/แผลเปื่อยที่เกิดจากการบาดเจ็บ', 'name_en' => 'Oral Ulcers / Aphthous Ulcers', 'urgency' => 'G'],
                'herpes_simplex_oral'     => ['name' => 'เริมในช่องปากชนิดเฉียบพลัน/เฮอร์แปงไจนา', 'name_en' => 'Acute Herpetic Stomatitis / Herpangina', 'urgency' => 'G'],
                'angular_cheilitis'       => ['name' => 'ปากนกกระจอก', 'name_en' => 'Angular Cheilitis', 'urgency' => 'G'],
                'oral_thrush'             => ['name' => 'โรคเชื้อราในช่องปาก/มุมปากเปื่อยจากเชื้อรา', 'name_en' => 'Oral Thrush / Candidiasis', 'urgency' => 'G'],
                'tooth_decay'             => ['name' => 'ปวดฟัน ฟันผุ เหงือกอักเสบ ฟันเหลืองดำ/ฟันตกกระ', 'name_en' => 'Tooth Decay / Dental Caries / Gingivitis', 'urgency' => 'G'],
            ],

            // บทที่ 3 โรคระบบประสาทและสมอง
            '000003' => [
                'polio_neuro'             => ['name' => 'โปลิโอ', 'name_en' => 'Poliomyelitis', 'urgency' => 'R'],
                'rabies_neuro'            => ['name' => 'โรคพิษสุนัขบ้า', 'name_en' => 'Rabies', 'urgency' => 'R'],
                'encephalitis_neuro'      => ['name' => 'สมองอักเสบ/โรคเรย์ซินโดรม', 'name_en' => 'Encephalitis / Reye\'s Syndrome', 'urgency' => 'R'],
                'meningitis_neuro'        => ['name' => 'เยื่อหุ้มสมองอักเสบ/ไข้กาฬหลังแอ่น', 'name_en' => 'Meningitis / Meningococcal Meningitis', 'urgency' => 'R'],
                'tetanus_neuro'           => ['name' => 'บาดทะยัก', 'name_en' => 'Tetanus', 'urgency' => 'R'],
                'botulism'                => ['name' => 'โบทูลิซึม', 'name_en' => 'Botulism', 'urgency' => 'R'],
                'febrile_seizure_neuro'   => ['name' => 'ชักจากไข้', 'name_en' => 'Febrile Seizure', 'urgency' => 'Y'],
                'neonatal_seizure'        => ['name' => 'ชักในทารกแรกเกิด', 'name_en' => 'Neonatal Seizure', 'urgency' => 'R'],
                'epilepsy'                => ['name' => 'โรคลมชัก ลมบ้าหมู', 'name_en' => 'Epilepsy', 'urgency' => 'Y'],
                'migraine'                => ['name' => 'ไมเกรน', 'name_en' => 'Migraine', 'urgency' => 'G'],
                'tension_headache'        => ['name' => 'ปวดศีรษะคลัสเตอร์/ปวดศีรษะจากความเครียด', 'name_en' => 'Tension / Cluster Headache', 'urgency' => 'G'],
                'vertigo'                 => ['name' => 'เวียนศีรษะ บ้านหมุน', 'name_en' => 'Vertigo', 'urgency' => 'G'],
                'syncope'                 => ['name' => 'เป็นลม', 'name_en' => 'Syncope / Fainting', 'urgency' => 'G'],
                'coma'                    => ['name' => 'หมดสติ', 'name_en' => 'Coma / Unconsciousness', 'urgency' => 'R'],
                'stroke'                  => ['name' => 'โรคหลอดเลือดสมอง/สมองขาดเลือดชั่วขณะ/อัมพาตครึ่งซีก/อัมพาตเบลล์/เบลล์พัลซี', 'name_en' => 'Stroke / TIA / Bell\'s Palsy', 'urgency' => 'R'],
                'hypokalemia_paralysis'   => ['name' => 'ภาวะโพแทสเซียมในเลือดต่ำ/อัมพาตครั้งคราว', 'name_en' => 'Hypokalemic Periodic Paralysis', 'urgency' => 'Y'],
                'myasthenia_gravis'       => ['name' => 'ไมแอสทีเนียแกรวิส', 'name_en' => 'Myasthenia Gravis', 'urgency' => 'Y'],
                'parkinson'               => ['name' => 'โรคพาร์กินสัน', 'name_en' => 'Parkinson\'s Disease', 'urgency' => 'G'],
                'cerebral_palsy'          => ['name' => 'สมองพิการ', 'name_en' => 'Cerebral Palsy', 'urgency' => 'G'],
                'head_injury'             => ['name' => 'ศีรษะได้รับบาดเจ็บ/เลือดออกในสมอง', 'name_en' => 'Head Injury / Intracranial Hemorrhage', 'urgency' => 'R'],
                'brain_abscess_neuro'     => ['name' => 'ฝีสมอง/เนื้องอกสมอง', 'name_en' => 'Brain Abscess / Brain Tumor', 'urgency' => 'R'],
                'transverse_myelitis'     => ['name' => 'ไขสันหลังอักเสบเฉียบพลัน/ไขสันหลังได้รับบาดเจ็บ/เนื้องอกไขสันหลัง', 'name_en' => 'Transverse Myelitis / Spinal Cord Injury', 'urgency' => 'R'],
                'peripheral_neuropathy'   => ['name' => 'ปลายประสาทอักเสบ', 'name_en' => 'Peripheral Neuropathy', 'urgency' => 'G'],
                'anxiety_disorder'        => ['name' => 'โรคจิตกังวล โรคกังวลทั่วไป/โรคแพนิก/โรคอารมณ์แปรปรวน โรคซึมเศร้า', 'name_en' => 'Anxiety / Panic / Depression Disorders', 'urgency' => 'G'],
                'hyperventilation'        => ['name' => 'กลุ่มอาการระบายลมหายใจเกิน', 'name_en' => 'Hyperventilation Syndrome', 'urgency' => 'G'],
                'school_phobia'           => ['name' => 'เด็กไม่อยากไปโรงเรียน/โรคกลัวโรงเรียน', 'name_en' => 'School Phobia', 'urgency' => 'G'],
            ],

            // บทที่ 4 โรคระบบไหลเวียนโลหิตและโรคเลือด
            '000004' => [
                'shock'                   => ['name' => 'ช็อก', 'name_en' => 'Shock', 'urgency' => 'R'],
                'hypertension'            => ['name' => 'ความดันโลหิตสูง', 'name_en' => 'Hypertension', 'urgency' => 'G'],
                'aneurysm'                => ['name' => 'หลอดเลือดแดงใหญ่โป่งพอง/ภาวะเลือดเซาะผนังหลอดเลือดแดงใหญ่', 'name_en' => 'Aortic Aneurysm / Dissection', 'urgency' => 'R'],
                'glaucoma_circ'           => ['name' => 'ความดันตาในท่าเขียน', 'name_en' => 'Ocular Hypertension', 'urgency' => 'G'],
                'rheumatic_heart'         => ['name' => 'ไข้รูมาติก/โรคหัวใจรูมาติก', 'name_en' => 'Rheumatic Fever / Heart Disease', 'urgency' => 'Y'],
                'endocarditis_circ'       => ['name' => 'เยื่อบุหัวใจอักเสบ', 'name_en' => 'Infective Endocarditis', 'urgency' => 'R'],
                'coronary_artery_disease' => ['name' => 'โรคหัวใจขาดเลือด/โรคหลอดเลือดหัวใจตีบ/โรคหัวใจขาดเลือดชั่วขณะ/โรคกล้ามเนื้อหัวใจตาย', 'name_en' => 'Coronary Artery Disease / Myocardial Infarction', 'urgency' => 'R'],
                'arrhythmia'              => ['name' => 'โรคหัวใจเต้นผิดจังหวะ', 'name_en' => 'Arrhythmia', 'urgency' => 'R'],
                'heart_failure'           => ['name' => 'หัวใจวาย/หัวใจล้มเหลว', 'name_en' => 'Heart Failure', 'urgency' => 'R'],
                'varicose_veins'          => ['name' => 'หลอดเลือดขอดที่ขา/ภาวะหลอดเลือดดำส่วนลึกมีลิ่มเลือด', 'name_en' => 'Varicose Veins / Deep Vein Thrombosis', 'urgency' => 'G'],
                'iron_deficiency_anemia'  => ['name' => 'โลหิตจางจากการขาดธาตุเหล็ก/โลหิตจางจากเม็ดเลือดแดงแตก/ภาวะพร่องเอนไซม์ จี-6-พีดี', 'name_en' => 'Iron Deficiency Anemia / G6PD Deficiency', 'urgency' => 'Y'],
                'aplastic_anemia'         => ['name' => 'เม็ดเลือดแดงแตกในทารกแรกเกิด/โลหิตจางจากไขกระดูกฝ่อ/ไอทีพี', 'name_en' => 'Aplastic Anemia / ITP', 'urgency' => 'Y'],
                'hemophilia'              => ['name' => 'ฮีโมฟีเลีย', 'name_en' => 'Hemophilia', 'urgency' => 'Y'],
                'thalassemia'             => ['name' => 'ทาลัสซีเมีย', 'name_en' => 'Thalassemia', 'urgency' => 'Y'],
                'leukemia_circ'           => ['name' => 'มะเร็งเม็ดเลือดขาว/มะเร็งต่อมน้ำเหลือง', 'name_en' => 'Leukemia / Lymphoma', 'urgency' => 'R'],
            ],

            // บทที่ 5 โรคระบบกระดูกและกล้ามเนื้อ
            '000005' => [
                'back_pain'               => ['name' => 'ปวดกล้ามเนื้อหลัง', 'name_en' => 'Back Pain / Muscle Strain', 'urgency' => 'G'],
                'herniated_disc'          => ['name' => 'รากประสาทถูกกด/หมอนรองกระดูกสันหลังเคลื่อน/โพรงกระดูกสันหลังแคบ', 'name_en' => 'Herniated Disc / Spinal Stenosis', 'urgency' => 'Y'],
                'spondylosis'             => ['name' => 'กระดูกคอเสื่อม/กระดูกงอกออกจากรากประสาท', 'name_en' => 'Cervical Spondylosis', 'urgency' => 'G'],
                'osteoarthritis'          => ['name' => 'ข้อเสื่อม/ข้อเข่าเสื่อ', 'name_en' => 'Osteoarthritis / Osteoarthritis of Knee', 'urgency' => 'G'],
                'rheumatoid_arthritis'    => ['name' => 'โรคปวดข้อรูมาตอยด์', 'name_en' => 'Rheumatoid Arthritis', 'urgency' => 'Y'],
                'chronic_gout'            => ['name' => 'ข้อสันหลังอักเสบเรื้อรัง/เอสแอลอี', 'name_en' => 'Ankylosing Spondylitis / SLE', 'urgency' => 'Y'],
                'septic_arthritis'        => ['name' => 'ข้ออักเสบชนิดติดเชื้อเฉียบพลัน', 'name_en' => 'Septic Arthritis', 'urgency' => 'R'],
                'sprain'                  => ['name' => 'ข้อเคล็ด/ข้อแพลง/เส้นเอ็นอักเสบ/ปลอกหุ้มเส้นเอ็นอักเสบ', 'name_en' => 'Sprains and Strains / Tendonitis', 'urgency' => 'G'],
                'plantar_fasciitis'       => ['name' => 'พังผืดส้นเท้าอักเสบ', 'name_en' => 'Plantar Fasciitis', 'urgency' => 'G'],
                'carpal_tunnel'           => ['name' => 'เส้นประสาทมือถูกพังผืดรัดแน่น/โรคคาร์พัลทูนเนล', 'name_en' => 'Carpal Tunnel Syndrome', 'urgency' => 'G'],
                'cramp'                   => ['name' => 'ตะคริว', 'name_en' => 'Muscle Cramps', 'urgency' => 'G'],
            ],

            // บทที่ 6 โรคระบบต่อมไร้ท่อและโภชนาการ
            '000006' => [
                'diabetes_mellitus'       => ['name' => 'เบาหวาน', 'name_en' => 'Diabetes Mellitus', 'urgency' => 'Y'],
                'diabetes_insipidus'      => ['name' => 'เบาจืด', 'name_en' => 'Diabetes Insipidus', 'urgency' => 'Y'],
                'dyslipidemia'            => ['name' => 'ไขมันในเลือดสูง/ไขมันในเลือดผิดปกติ', 'name_en' => 'Dyslipidemia', 'urgency' => 'G'],
                'hypoglycemia'            => ['name' => 'ภาวะน้ำตาลในเลือดต่ำ', 'name_en' => 'Hypoglycemia', 'urgency' => 'R'],
                'hypocalcemia'            => ['name' => 'ภาวะแคลเซียมในเลือดต่ำ', 'name_en' => 'Hypocalcemia', 'urgency' => 'Y'],
                'goiter'                  => ['name' => 'คอพอก/ต่อมไทรอยด์โต/คอพอกธรรมดา', 'name_en' => 'Goiter / Thyroid Megaly', 'urgency' => 'G'],
                'hyperthyroidism'         => ['name' => 'ภาวะต่อมไทรอยด์ทำงานเกิน/พิษจากไทรอยด์/คอพอกเป็นพิษ', 'name_en' => 'Hyperthyroidism / Thyrotoxicosis', 'urgency' => 'Y'],
                'thyroiditis'             => ['name' => 'ต่อมไทรอยด์อักเสบ', 'name_en' => 'Thyroiditis', 'urgency' => 'Y'],
                'thyroid_cancer'          => ['name' => 'มะเร็งไทรอยด์', 'name_en' => 'Thyroid Cancer', 'urgency' => 'R'],
                'hypothyroidism'          => ['name' => 'ภาวะขาดไทรอยด์/ต่อมไทรอยด์ทำงานน้อย', 'name_en' => 'Hypothyroidism', 'urgency' => 'Y'],
                'cushing_syndrome'        => ['name' => 'โรคคุชชิง', 'name_en' => 'Cushing\'s Syndrome', 'urgency' => 'Y'],
                'addison_disease'         => ['name' => 'โรคแอดดิสัน', 'name_en' => 'Addison\'s Disease', 'urgency' => 'Y'],
                'cretinism'               => ['name' => 'โรคครีติน', 'name_en' => 'Cretinism / Congenital Hypothyroidism', 'urgency' => 'Y'],
                'gout'                    => ['name' => 'โรคเกาต์', 'name_en' => 'Gout', 'urgency' => 'Y'],
                'menopause'               => ['name' => 'โรคของผู้หญิงวัยหมดประจำเดือน', 'name_en' => 'Menopause Syndrome', 'urgency' => 'G'],
                'osteoporosis'            => ['name' => 'กระดูกพรุน', 'name_en' => 'Osteoporosis', 'urgency' => 'G'],
                'malnutrition_children'   => ['name' => 'โรคขาดอาหารในเด็ก', 'name_en' => 'Malnutrition in Children', 'urgency' => 'Y'],
                'vitamin_a_deficiency'    => ['name' => 'โรคขาดวิตามินเอ/เกล็ดกระดี่ขึ้นตา', 'name_en' => 'Vitamin A Deficiency', 'urgency' => 'Y'],
                'beriberi'                => ['name' => 'โรคเหน็บชา/โรคขาดวิตามินบี 1', 'name_en' => 'Beriberi / Vitamin B1 Deficiency', 'urgency' => 'G'],
                'scurvy'                  => ['name' => 'ลักปิดลักเปิด', 'name_en' => 'Scurvy / Vitamin C Deficiency', 'urgency' => 'G'],
            ],

            // บทที่ 7 โรคระบบทางเดินปัสสาวะ
            '000007' => [
                'renal_failure'           => ['name' => 'ภาวะไตวาย', 'name_en' => 'Renal Failure', 'urgency' => 'R'],
                'nephrotic_syndrome'      => ['name' => 'โรคไตเนฟโรติก', 'name_en' => 'Nephrotic Syndrome', 'urgency' => 'Y'],
                'acute_glomerulonephritis'=> ['name' => 'หน่วยไตอักเสบเฉียบพลัน', 'name_en' => 'Acute Glomerulonephritis', 'urgency' => 'Y'],
                'acute_pyelonephritis'    => ['name' => 'กรวยไตอักเสบ', 'name_en' => 'Acute Pyelonephritis', 'urgency' => 'Y'],
                'renal_calculi'           => ['name' => 'นิ่วไต/นิ่วท่อไต', 'name_en' => 'Renal / Ureteric Calculi', 'urgency' => 'Y'],
                'bladder_calculi'         => ['name' => 'นิ่วกระเพาะปัสสาวะ', 'name_en' => 'Bladder Calculi', 'urgency' => 'G'],
                'cystitis'                => ['name' => 'กระเพาะปัสสาวะอักเสบ', 'name_en' => 'Cystitis', 'urgency' => 'G'],
                'urethral_stricture'      => ['name' => 'ท่อปัสสาวะตีบ', 'name_en' => 'Urethral Stricture', 'urgency' => 'Y'],
            ],

            // บทที่ 8 โรคระบบอวัยวะสืบพันธุ์ชาย
            '000008' => [
                'bph'                     => ['name' => 'ต่อมลูกหมากโต', 'name_en' => 'Benign Prostatic Hyperplasia (BPH)', 'urgency' => 'G'],
                'prostatitis'             => ['name' => 'ต่อมลูกหมากอักเสบ', 'name_en' => 'Prostatitis', 'urgency' => 'Y'],
                'phimosis'                => ['name' => 'หนังหุ้มปลายองคชาตตีบ', 'name_en' => 'Phimosis', 'urgency' => 'G'],
                'hydrocele'               => ['name' => 'ถุงน้ำที่ถุงอัณฑะ/กล่อนน้ำ', 'name_en' => 'Hydrocele', 'urgency' => 'G'],
                'orchitis'                => ['name' => 'หลอดเลือดอัณฑะขอด', 'name_en' => 'Orchitis / Varicocele', 'urgency' => 'Y'],
                'testicular_torsion'      => ['name' => 'อัณฑะบิดตัว', 'name_en' => 'Testicular Torsion', 'urgency' => 'R'],
            ],

            // บทที่ 9 โรคระบบอวัยวะสืบพันธุ์หญิงและการตั้งครรภ์
            '000009' => [
                'pelvic_inflammatory'     => ['name' => 'ปีกมดลูกอักเสบ/เยื่อบุโพรงมดลูกอักเสบ', 'name_en' => 'Pelvic Inflammatory Disease (PID)', 'urgency' => 'Y'],
                'leukorrhoea'             => ['name' => 'ตกขาวธรรมดา', 'name_en' => 'Leukorrhoea', 'urgency' => 'G'],
                'vaginitis'               => ['name' => 'ช่องคลอดอักเสบ', 'name_en' => 'Vaginitis', 'urgency' => 'G'],
                'vaginal_candidiasis'     => ['name' => 'ช่องคลอดอักเสบจากเชื้อรา', 'name_en' => 'Vaginal Candidiasis', 'urgency' => 'G'],
                'trichomoniasis'          => ['name' => 'ช่องคลอดอักเสบจากเชื้อทริโคโมแนส', 'name_en' => 'Trichomoniasis', 'urgency' => 'G'],
                'dysmenorrhea'            => ['name' => 'ปวดประจำเดือน', 'name_en' => 'Dysmenorrhea', 'urgency' => 'G'],
                'amenorrhea'              => ['name' => 'ประจำเดือนไม่มา/ประจำเดือนขาด', 'name_en' => 'Amenorrhea', 'urgency' => 'G'],
                'menorrhagia'             => ['name' => 'ดียูบี/ประจำเดือนออกมาก', 'name_en' => 'Dysfunctional Uterine Bleeding (DUB)', 'urgency' => 'Y'],
                'uterine_myoma'           => ['name' => 'เนื้องอกมดลูก', 'name_en' => 'Uterine Myoma', 'urgency' => 'Y'],
                'endometriosis'           => ['name' => 'เยื่อบุโพรงมดลูกเจริญผิดที่/ช็อกโกแลตซีสต์', 'name_en' => 'Endometriosis', 'urgency' => 'G'],
                'ovarian_tumor'           => ['name' => 'เนื้องอกรังไข่/ถุงน้ำรังไข่', 'name_en' => 'Ovarian Tumor / Cyst', 'urgency' => 'Y'],
                'pcos'                    => ['name' => 'กลุ่มอาการถุงน้ำรังไข่หลายใบ/พีซีโอเอส', 'name_en' => 'PCOS', 'urgency' => 'G'],
                'pregnancy'               => ['name' => 'ภาวะตั้งครรภ์', 'name_en' => 'Pregnancy', 'urgency' => 'G'],
                'hyperemesis_gravidarum'  => ['name' => 'แพ้ท้อง', 'name_en' => 'Hyperemesis Gravidarum / Morning Sickness', 'urgency' => 'G'],
                'preeclampsia'            => ['name' => 'ครรภ์เป็นพิษ', 'name_en' => 'Preeclampsia / Eclampsia', 'urgency' => 'R'],
                'abortion'                => ['name' => 'แท้งบุตร', 'name_en' => 'Abortion / Miscarriage', 'urgency' => 'R'],
                'ectopic_pregnancy'       => ['name' => 'ครรภ์นอกมดลูก', 'name_en' => 'Ectopic Pregnancy', 'urgency' => 'R'],
                'molar_pregnancy'         => ['name' => 'ครรภ์ไข่ปลาอุก', 'name_en' => 'Molar Pregnancy', 'urgency' => 'R'],
                'placenta_previa'         => ['name' => 'รกเกาะต่ำ', 'name_en' => 'Placenta Previa', 'urgency' => 'R'],
                'abruptio_placentae'      => ['name' => 'รกลอกตัวก่อนกำหนด', 'name_en' => 'Abruptio Placentae', 'urgency' => 'R'],
            ],

            // บทที่ 10 โรคหู
            '000010' => [
                'otitis_externa'          => ['name' => 'หูชั้นนอกอักเสบ', 'name_en' => 'Otitis Externa', 'urgency' => 'G'],
                'otomycosis'              => ['name' => 'โรคเชื้อราในช่องหู', 'name_en' => 'Otomycosis', 'urgency' => 'G'],
                'otitis_media_acute'      => ['name' => 'หูชั้นกลางอักเสบ', 'name_en' => 'Acute Otitis Media', 'urgency' => 'G'],
                'otitis_media_chronic'    => ['name' => 'หูชั้นในอักเสบเฉียบพลัน/เส้นประสาทการทรงตัวอักเสบ', 'name_en' => 'Chronic Otitis Media / Labyrinthitis', 'urgency' => 'Y'],
                'meniere_disease'         => ['name' => 'บ้านหมุนจากการเปลี่ยนท่า/บีพีพีวี/เนื้องอกประสาทหู', 'name_en' => 'Meniere\'s Disease / BPPV', 'urgency' => 'G'],
                'tinnitus'                => ['name' => 'โรคเมเนียร์/หูตึง/หูหนวก', 'name_en' => 'Tinnitus / Hearing Loss', 'urgency' => 'G'],
                'tympanic_perforation'    => ['name' => 'เยื่อแก้วหูทะลุ', 'name_en' => 'Tympanic Membrane Perforation', 'urgency' => 'G'],
                'earwax_impaction'        => ['name' => 'แผลถลอกในช่องหู/ขี้หูอุดตันรูหู', 'name_en' => 'Earwax Impaction', 'urgency' => 'G'],
                'foreign_body_ear'        => ['name' => 'สิ่งแปลกปลอมเข้าหู', 'name_en' => 'Foreign Body in Ear', 'urgency' => 'G'],
                'barotrauma_ear'          => ['name' => 'หูบาดเจ็บจากความกดดันอากาศ', 'name_en' => 'Barotrauma', 'urgency' => 'G'],
            ],

            // บทที่ 11 โรคตา
            '000011' => [
                'bacterial_conjunctivitis'=> ['name' => 'เยื่อบุตาขาวอักเสบจากเชื้อแบคทีเรีย', 'name_en' => 'Bacterial Conjunctivitis', 'urgency' => 'G'],
                'viral_conjunctivitis'    => ['name' => 'เยื่อบุตาขาวอักเสบจากไวรัส', 'name_en' => 'Viral Conjunctivitis', 'urgency' => 'G'],
                'allergic_conjunctivitis' => ['name' => 'เยื่อบุตาขาวอักเสบจากการแพ้', 'name_en' => 'Allergic Conjunctivitis', 'urgency' => 'G'],
                'gonococcal_conjunctivitis'=>['name' => 'ตาอักเสบจากเชื้อหนองใน', 'name_en' => 'Gonococcal Conjunctivitis', 'urgency' => 'Y'],
                'hordeolum'               => ['name' => 'ริดสีดวงตา/กุ้งยิง', 'name_en' => 'Hordeolum / Stye / Trachoma', 'urgency' => 'G'],
                'blepharitis'             => ['name' => 'หนังตาอักเสบ', 'name_en' => 'Blepharitis', 'urgency' => 'G'],
                'dacryocystitis'          => ['name' => 'ท่อน้ำตาอุดตัน/ถุงน้ำตาอักเสบ', 'name_en' => 'Dacryocystitis', 'urgency' => 'G'],
                'strabismus'              => ['name' => 'สายตาผิดปกติ/ตาเข', 'name_en' => 'Strabismus / Refractive Errors', 'urgency' => 'G'],
                'pterygium'               => ['name' => 'ต้อเนื้อ/ต้อลิ้นหมา', 'name_en' => 'Pterygium / Pinguecula', 'urgency' => 'G'],
                'cataract'                => ['name' => 'ต้อกระจก', 'name_en' => 'Cataract', 'urgency' => 'G'],
                'glaucoma'                => ['name' => 'ต้อหิน', 'name_en' => 'Glaucoma', 'urgency' => 'Y'],
                'corneal_ulcer'           => ['name' => 'จอตาลอก', 'name_en' => 'Corneal Ulcer / Retinal Detachment', 'urgency' => 'R'],
                'amd'                     => ['name' => 'จุดภาพชัดเสื่อมตามวัย', 'name_en' => 'Age-related Macular Degeneration (AMD)', 'urgency' => 'G'],
                'keratitis'               => ['name' => 'กระจกตาอักเสบ/แผลกระจกตา', 'name_en' => 'Keratitis / Corneal Abrasion', 'urgency' => 'Y'],
                'uveitis'                 => ['name' => 'ม่านตาอักเสบ', 'name_en' => 'Uveitis', 'urgency' => 'Y'],
                'subconjunctival_hemorrhage'=>['name' => 'เลือดออกใต้ตาขาว', 'name_en' => 'Subconjunctival Hemorrhage', 'urgency' => 'G'],
                'eye_injury'              => ['name' => 'ตาได้รับบาดเจ็บรุนแรง', 'name_en' => 'Eye Injury / Trauma', 'urgency' => 'R'],
                'foreign_body_eye'        => ['name' => 'สิ่งแปลกปลอมเข้าตา', 'name_en' => 'Foreign Body in Eye', 'urgency' => 'G'],
            ],

            // บทที่ 12 โรคผิวหนัง
            '000012' => [
                'herpes_simplex_skin'     => ['name' => 'เริม', 'name_en' => 'Herpes Simplex', 'urgency' => 'G'],
                'herpes_zoster'           => ['name' => 'งูสวัด', 'name_en' => 'Herpes Zoster / Shingles', 'urgency' => 'G'],
                'warts'                   => ['name' => 'หูด', 'name_en' => 'Warts', 'urgency' => 'G'],
                'condyloma'               => ['name' => 'หงอนไก่', 'name_en' => 'Condyloma Acuminata', 'urgency' => 'G'],
                'tinea'                   => ['name' => 'กลาก', 'name_en' => 'Tinea / Ringworm', 'urgency' => 'G'],
                'tinea_versicolor'        => ['name' => 'เกลื้อน', 'name_en' => 'Tinea Versicolor / Pityriasis', 'urgency' => 'G'],
                'candidiasis_skin'        => ['name' => 'โรคเชื้อราแคนดิดา', 'name_en' => 'Cutaneous Candidiasis', 'urgency' => 'G'],
                'bacterial_skin'          => ['name' => 'โรคติดเชื้อแบคทีเรียของผิวหนัง/ฝี/แผลพุพอง/แผลอักเสบ', 'name_en' => 'Bacterial Skin Infections / Abscess', 'urgency' => 'G'],
                'cellulitis'              => ['name' => 'เนื้อเยื่อใต้ผิวหนังชั้นลึกอักเสบ/ไฟลามทุ่ง', 'name_en' => 'Cellulitis / Erysipelas', 'urgency' => 'Y'],
                'mastitis'                => ['name' => 'ฝีเต้านม/ต่อมน้ำเหลืองอักเสบ', 'name_en' => 'Mastitis / Lymphadenitis', 'urgency' => 'Y'],
                'scabies'                 => ['name' => 'หิด', 'name_en' => 'Scabies', 'urgency' => 'G'],
                'pediculosis'             => ['name' => 'เหา', 'name_en' => 'Pediculosis / Lice', 'urgency' => 'G'],
                'leprosy'                 => ['name' => 'โรคเรื้อน', 'name_en' => 'Leprosy / Hansen\'s Disease', 'urgency' => 'Y'],
                'urticaria'               => ['name' => 'ลมพิษ', 'name_en' => 'Urticaria / Hives', 'urgency' => 'G'],
                'contact_dermatitis'      => ['name' => 'ผิวหนังอักเสบจากการสัมผัส/ผิวหนังอักเสบจากภูมิแพ้', 'name_en' => 'Contact / Atopic Dermatitis', 'urgency' => 'G'],
                'seborrheic_dermatitis'   => ['name' => 'ผิวหนังอักเสบชนิดเกล็ดรังแค/รังแค', 'name_en' => 'Seborrheic Dermatitis / Dandruff', 'urgency' => 'G'],
                'alopecia'                => ['name' => 'ผมร่วง ผมบาง', 'name_en' => 'Alopecia / Hair Loss', 'urgency' => 'G'],
                'pityriasis_alba'         => ['name' => 'กลากน้ำนม/โรคด่างขาว', 'name_en' => 'Pityriasis Alba / Vitiligo', 'urgency' => 'G'],
                'psoriasis'               => ['name' => 'ผื่นพีอาร์/สะเก็ดเงิน/โรคเกล็ดเงิน', 'name_en' => 'Psoriasis', 'urgency' => 'G'],
                'acne'                    => ['name' => 'สิว', 'name_en' => 'Acne Vulgaris', 'urgency' => 'G'],
                'chloasma'                => ['name' => 'ฝ้า', 'name_en' => 'Chloasma / Melasma', 'urgency' => 'G'],
                'keloid'                  => ['name' => 'คีลอยด์/แผลเป็น', 'name_en' => 'Keloid / Hypertrophic Scar', 'urgency' => 'G'],
                'corn'                    => ['name' => 'ตาปลา/หนังหนาด้าน', 'name_en' => 'Corns and Calluses', 'urgency' => 'G'],
                'steven_johnson'          => ['name' => 'กลุ่มอาการสตีเวนส์จอห์นสัน', 'name_en' => 'Stevens-Johnson Syndrome (SJS)', 'urgency' => 'R'],
            ],

            // บทที่ 13 โรคติดต่อทางเพศสัมพันธ์
            '000013' => [
                'gonorrhea'               => ['name' => 'หนองใน', 'name_en' => 'Gonorrhea', 'urgency' => 'Y'],
                'chlamydia'               => ['name' => 'หนองในเทียม', 'name_en' => 'Chlamydia / Non-gonococcal Urethritis', 'urgency' => 'Y'],
                'chancroid'               => ['name' => 'แผลริมอ่อน', 'name_en' => 'Chancroid', 'urgency' => 'Y'],
                'syphilis'                => ['name' => 'ซิฟิลิส', 'name_en' => 'Syphilis', 'urgency' => 'Y'],
                'lymphogranuloma'         => ['name' => 'ฝีมะม่วง', 'name_en' => 'Lymphogranuloma Venereum (LGV)', 'urgency' => 'Y'],
            ],

            // บทที่ 14 โรคที่เกิดจากอุบัติเหตุ สารพิษ และสัตว์พิษ
            '000014' => [
                'fracture'                => ['name' => 'กระดูกหัก/กระดูกซี่โครงหัก', 'name_en' => 'Fractures / Rib Fractures', 'urgency' => 'R'],
                'choking_foreign_body'    => ['name' => 'ก้างปลา/กระดูกติดคอ', 'name_en' => 'Foreign Body in Throat', 'urgency' => 'Y'],
                'drowning'                => ['name' => 'จมน้ำ', 'name_en' => 'Drowning', 'urgency' => 'R'],
                'electrocution'           => ['name' => 'ไฟฟ้าช็อต', 'name_en' => 'Electrocution / Electrical Injury', 'urgency' => 'R'],
                'scald_burn'              => ['name' => 'บาดแผลไฟไหม้น้ำร้อนลวก', 'name_en' => 'Burns and Scalds', 'urgency' => 'R'],
                'heat_stroke_accident'    => ['name' => 'โรคจากความร้อน/ภาวะตัวเย็นเกิน', 'name_en' => 'Heat Stroke / Hypothermia', 'urgency' => 'R'],
                'poisoning'               => ['name' => 'กินสารพิษหรือยาพิษ/พิษปลาปักเป้า พิษแมงดาท้วย', 'name_en' => 'Poisoning / Toxins', 'urgency' => 'R'],
                'marine_toxins'           => ['name' => 'พิษปลาทะเล/พิษหอยทะเล/พิษคางคก/พิษเห็ด', 'name_en' => 'Marine and Amphibian Toxins / Mushroom Poisoning', 'urgency' => 'R'],
                'lead_poisoning'          => ['name' => 'ตะกั่วเป็นพิษ', 'name_en' => 'Lead Poisoning', 'urgency' => 'Y'],
                'snake_bite'              => ['name' => 'งูกัด', 'name_en' => 'Snake Bite', 'urgency' => 'R'],
                'animal_bite'             => ['name' => 'สัตว์กัด/แมลงต่อย/ปลิงเข้าอวัยวะ', 'name_en' => 'Animal Bites / Insect Stings', 'urgency' => 'Y'],
            ],

            // บทที่ 15 โรคติดเชื้อ
            '000015' => [
                'malaria_inf'             => ['name' => 'มาลาเรีย', 'name_en' => 'Malaria', 'urgency' => 'Y'],
                'dengue_inf'              => ['name' => 'ไข้เลือดออก', 'name_en' => 'Dengue Hemorrhagic Fever', 'urgency' => 'Y'],
            ],

            // บทที่ 16 โรคพยาธิ
            '000016' => [
                'roundworm'               => ['name' => 'โรคพยาธิไส้เดือน', 'name_en' => 'Ascariasis / Roundworm', 'urgency' => 'G'],
                'threadworm'              => ['name' => 'โรคพยาธิเส้นด้าย', 'name_en' => 'Strongyloidiasis / Threadworm', 'urgency' => 'G'],
                'hookworm'                => ['name' => 'โรคพยาธิปากขอ', 'name_en' => 'Hookworm Infection', 'urgency' => 'G'],
                'whipworm'                => ['name' => 'โรคพยาธิแส้ม้า', 'name_en' => 'Trichuriasis / Whipworm', 'urgency' => 'G'],
                'trichinosis'             => ['name' => 'ทริคิโนซิส', 'name_en' => 'Trichinosis', 'urgency' => 'Y'],
                'gnathostomiasis'         => ['name' => 'โรคพยาธิตัวจี๊ด', 'name_en' => 'Gnathostomiasis', 'urgency' => 'G'],
            ],

            // บทที่ 17 โรคมะเร็ง
            '000017' => [
                'general_cancer'          => ['name' => 'มะเร็ง/มะเร็งผิวหนัง/มะเร็งเต้านม', 'name_en' => 'Cancer / Skin / Breast Cancer', 'urgency' => 'R'],
                'cervical_cancer'         => ['name' => 'มะเร็งปากมดลูก/มะเร็งเยื่อบุมดลูก/มะเร็งรังไข่', 'name_en' => 'Cervical / Endometrial / Ovarian Cancer', 'urgency' => 'R'],
                'lung_cancer'             => ['name' => 'มะเร็งปอด/มะเร็งกล่องเสียง/มะเร็งโพรงหลังจมูก', 'name_en' => 'Lung / Laryngeal / Nasopharyngeal Cancer', 'urgency' => 'R'],
                'oral_cancer'             => ['name' => 'มะเร็งทอนซิล/มะเร็งหลอดอาหาร/มะเร็งกระเพาะอาหาร', 'name_en' => 'Oral / Esophageal / Stomach Cancer', 'urgency' => 'R'],
                'colorectal_cancer'       => ['name' => 'มะเร็งลำไส้เล็ก/มะเร็งลำไส้ใหญ่และไส้ตรง', 'name_en' => 'Colorectal Cancer', 'urgency' => 'R'],
                'liver_pancreatic_cancer' => ['name' => 'มะเร็งตับอ่อน/มะเร็งต่อมลูกหมาก/มะเร็งกระเพาะปัสสาวะ', 'name_en' => 'Liver / Pancreatic / Prostate / Bladder Cancer', 'urgency' => 'R'],
                'testicular_cancer'       => ['name' => 'มะเร็งอัณฑะ/มะเร็งไต/มะเร็งกระดูก/มะเร็งลูกตาในเด็ก', 'name_en' => 'Testicular / Renal / Bone / Retinoblastoma', 'urgency' => 'R'],
            ],

            // บทที่ 18 โรคติดเชื้ออุบัติใหม่
            '000018' => [
                'aids'                    => ['name' => 'เอดส์', 'name_en' => 'AIDS / HIV', 'urgency' => 'Y'],
                'sars'                    => ['name' => 'ซาร์ส', 'name_en' => 'SARS', 'urgency' => 'R'],
                'avian_influenza'         => ['name' => 'ไข้หวัดนก/ไข้หวัดใหญ่สัตว์ปีก', 'name_en' => 'Avian Influenza', 'urgency' => 'R'],
            ],
        ];

        // ตัวนับสำหรับสร้างรหัส disease_id แบบตัวเลขรันลำดับ ต่อจาก ID สูงสุดที่มีในตาราง
        $diseaseSeq = (int) DB::table('diseases')->max('disease_id') + 1;

        // วนลูปบันทึกข้อมูลโรคทั้งหมดลงตารางฐานข้อมูลและผูกตามหมวดหมู่จริง
        foreach ($diseasesByGroup as $categoryId => $diseases) {
            foreach ($diseases as $key => $data) {

                // แปลงตัวเลขเป็น string ขนาด 10 หลัก (เช่น 1 -> '0000000001') ตามขนาดฟิลด์ char(10)
                $diseaseId = str_pad($diseaseSeq, 10, '0', STR_PAD_LEFT);

                DB::table('diseases')->insertOrIgnore([
                    'disease_id'          => $diseaseId,          // ใช้รหัสตัวเลข 10 หลัก
                    'disease_category_id' => $categoryId,          // ผูกกับรหัสหมวดหมู่ (เช่น '000001')
                    'disease_name'        => $data['name'],        // ชื่อโรคภาษาไทย
                    'disease_name_en'     => $data['name_en'],     // ชื่อโรคภาษาอังกฤษ
                    'status'              => '1',                  // ค่าเริ่มต้นตามโครงสร้างตาราง
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);

                $diseaseSeq++; // แก้ไขตรงนี้: เปลี่ยนจาก $diseaseAutoId++ เป็น $diseaseSeq++ เพื่อให้ค่าบวกเพิ่มในรอบถัดไป
            }
        }
    }
}
