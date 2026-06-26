<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComprehensiveDiseaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ============================================================
        // 1. Disease Categories
        // ============================================================
        $categories = [
            '000001' => ['name' => 'โรคระบบทางเดินหายใจและโรคติดต่อโดยทางเดินหายใจ', 'name_en' => 'Diseases of the Respiratory System and Respiratory Infections'],
            '000002' => ['name' => 'โรคระบบทางเดินอาหารและโรคติดต่อโดยทางเดินอาหาร', 'name_en' => 'Diseases of the Digestive System and Gastrointestinal Infections'],
            '000003' => ['name' => 'โรคระบบประสาทและสมอง', 'name_en' => 'Diseases of the Nervous System and Brain'],
            '000004' => ['name' => 'โรคระบบไหลเวียนโลหิตและโรคเลือด', 'name_en' => 'Diseases of the Circulatory System and Hematologic Diseases'],
            '000005' => ['name' => 'โรคระบบกระดูกและกล้ามเนื้อ', 'name_en' => 'Diseases of the Musculoskeletal System and Connective Tissue'],
            '000006' => ['name' => 'โรคระบบต่อมไร้ท่อและโภชนาการ', 'name_en' => 'Endocrine, Nutritional and Metabolic Diseases'],
            '000007' => ['name' => 'โรคระบบทางเดินปัสสาวะ', 'name_en' => 'Diseases of the Urinary System'],
            '000008' => ['name' => 'โรคระบบอวัยวะสืบพันธุ์ชาย', 'name_en' => 'Diseases of the Male Reproductive System'],
            '000009' => ['name' => 'โรคระบบอวัยวะสืบพันธุ์หญิงและการตั้งครรภ์', 'name_en' => 'Diseases of the Female Reproductive System and Pregnancy-related'],
            '000010' => ['name' => 'โรคหู', 'name_en' => 'Diseases of the Ear and Mastoid Process'],
            '000011' => ['name' => 'โรคตา', 'name_en' => 'Diseases of the Eye and Adnexa'],
            '000012' => ['name' => 'โรคผิวหนัง', 'name_en' => 'Diseases of the Skin and Subcutaneous Tissue'],
            '000013' => ['name' => 'โรคติดต่อทางเพศสัมพันธ์', 'name_en' => 'Sexually Transmitted Infections'],
            '000014' => ['name' => 'โรคที่เกิดจากอุบัติเหตุ สารพิษ และสัตว์พิษ', 'name_en' => 'Injury, Poisoning and Certain Other Consequences of External Causes'],
            '000015' => ['name' => 'โรคติดเชื้อ', 'name_en' => 'Infectious Diseases'],
            '000016' => ['name' => 'โรคพยาธิ', 'name_en' => 'Parasitic Diseases'],
            '000017' => ['name' => 'โรคมะเร็ง', 'name_en' => 'Neoplasms / Cancers'],
            '000018' => ['name' => 'โรคติดเชื้ออุบัติใหม่', 'name_en' => 'Emerging Infectious Diseases'],
        ];

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
        // 2. Diseases — ตรงตามสารบัญในหนังสือ
        // reference = เลขข้อในสารบัญ
        // ชื่อที่ไม่มีเลข → รวมกับบรรทัดก่อนหน้าด้วย /
        // ============================================================
        $diseasesByGroup = [

            // -------------------------------------------------------
            // บทที่ 1 โรคระบบทางเดินหายใจฯ (ข้อ 1-31.1)
            // -------------------------------------------------------
            '000001' => [
                ['ref' => '1', 'name' => 'ไข้หวัด', 'name_en' => 'Common Cold'],
                ['ref' => '2', 'name' => 'ไข้หวัดใหญ่', 'name_en' => 'Influenza'],
                ['ref' => '3', 'name' => 'หัด', 'name_en' => 'Measles'],
                ['ref' => '4', 'name' => 'หัดเยอรมัน/เหือด', 'name_en' => 'German Measles / Rubella'],
                ['ref' => '5', 'name' => 'ไข้ผื่นกุหลาบในทารก/ส่าไข้', 'name_en' => 'Roseola Infantum'],
                ['ref' => '6', 'name' => 'อีสุกอีใส', 'name_en' => 'Chickenpox / Varicella'],
                ['ref' => '7', 'name' => 'คางทูม', 'name_en' => 'Mumps'],
                ['ref' => '8', 'name' => 'คอหอยอักเสบ/ทอนซิลอักเสบ', 'name_en' => 'Pharyngitis / Acute Tonsillitis'],
                ['ref' => '9', 'name' => 'อีดำอีแดง', 'name_en' => 'Scarlet Fever'],
                ['ref' => '10', 'name' => 'คอตีบ/ดิฟทีเรีย', 'name_en' => 'Diphtheria'],
                ['ref' => '11', 'name' => 'ครู้ป', 'name_en' => 'Croup'],
                ['ref' => '12', 'name' => 'กล่องเสียงอักเสบ', 'name_en' => 'Acute Laryngitis'],
                ['ref' => '13', 'name' => 'ไอกรน', 'name_en' => 'Whooping Cough'],
                ['ref' => '14', 'name' => 'วัณโรคปอด', 'name_en' => 'Pulmonary Tuberculosis'],
                ['ref' => '15', 'name' => 'หลอดลมอักเสบเฉียบพลัน/ภาวะปอดอุดกั้นเรื้อรัง/หลอดลมอักเสบเรื้อรัง/ถุงลมปอดโป่งพอง', 'name_en' => 'Acute Bronchitis / COPD / Chronic Bronchitis / Emphysema'],
                ['ref' => '16', 'name' => 'ภาวะปอดอุดกั้นเรื้อรัง/หลอดลมอักเสบเรื้อรัง/ถุงลมปอดโป่งพอง', 'name_en' => 'COPD / Chronic Bronchitis / Emphysema'],
                ['ref' => '17', 'name' => 'หลอดลมพอง', 'name_en' => 'Bronchiectasis'],
                ['ref' => '18', 'name' => 'หลอดลมฝอยอักเสบ', 'name_en' => 'Acute Bronchiolitis'],
                ['ref' => '19', 'name' => 'ปอดอักเสบ/ปอดบวม', 'name_en' => 'Pneumonia'],
                ['ref' => '20', 'name' => 'ภาวะมีหนองในโพรงเยื่อหุ้มปอด/ภาวะมีน้ำในโพรงเยื่อหุ้มปอด', 'name_en' => 'Empyema Thoracis / Pleural Effusion'],
                ['ref' => '21', 'name' => 'เยื่อหุ้มปอดอักเสบ', 'name_en' => 'Pleurisy'],
                ['ref' => '22', 'name' => 'ปอดทะลุ/ภาวะมีลมในโพรงเยื่อหุ้มปอด', 'name_en' => 'Pneumothorax'],
                ['ref' => '23', 'name' => 'สำลักสิ่งแปลกปลอม/หลอดลมอุดกั้นจากสิ่งแปลกปลอม', 'name_en' => 'Foreign Body in Airway'],
                ['ref' => '24', 'name' => 'หืด', 'name_en' => 'Asthma'],
                ['ref' => '25', 'name' => 'หวัดภูมิแพ้', 'name_en' => 'Allergic Rhinitis'],
                ['ref' => '26', 'name' => 'ไซนัสอักเสบ', 'name_en' => 'Sinusitis'],
                ['ref' => '27', 'name' => 'เนื้อเยื่อรอบทอนซิลอักเสบเป็นหนอง', 'name_en' => 'Peritonsillar Abscess'],
                ['ref' => '28', 'name' => 'ติ่งเนื้อเมือกจมูก', 'name_en' => 'Nasal Polyps'],
                ['ref' => '29', 'name' => 'ผนังกั้นจมูกคด', 'name_en' => 'Deviated Nasal Septum'],
                ['ref' => '30', 'name' => 'เลือดกำเดา', 'name_en' => 'Epistaxis / Nosebleed'],
                ['ref' => '31', 'name' => 'สิ่งแปลกปลอมเข้าจมูก', 'name_en' => 'Foreign Body in Nose'],
                ['ref' => '31.1', 'name' => 'ภาวะหยุดหายใจขณะหลับ', 'name_en' => 'Sleep Apnea'],
            ],

            // -------------------------------------------------------
            // บทที่ 2 โรคระบบทางเดินอาหารฯ (ข้อ 32-62)
            // -------------------------------------------------------
            '000002' => [
                ['ref' => '32', 'name' => 'ท้องเดิน/อุจจาระร่วง', 'name_en' => 'Diarrhea'],
                ['ref' => '32.1', 'name' => 'ท้องเดินจากไวรัส', 'name_en' => 'Viral Diarrhea'],
                ['ref' => '32.2', 'name' => 'ท้องเดินจากเชื้อไกอาร์เดีย', 'name_en' => 'Giardiasis'],
                ['ref' => '33', 'name' => 'โรคลำไส้แปรปรวน', 'name_en' => 'Irritable Bowel Syndrome'],
                ['ref' => '33.1', 'name' => 'ภาวะพร่องแล็กเทส', 'name_en' => 'Lactose Intolerance'],
                ['ref' => '34', 'name' => 'อาหารเป็นพิษ', 'name_en' => 'Food Poisoning'],
                ['ref' => '34.1', 'name' => 'อาหารเป็นพิษจากเชื้อโรค', 'name_en' => 'Foodborne Illness'],
                ['ref' => '35', 'name' => 'อหิวาต์', 'name_en' => 'Cholera'],
                ['ref' => '36', 'name' => 'บิด', 'name_en' => 'Dysentery'],
                ['ref' => '36.1', 'name' => 'บิดชิเกลลา', 'name_en' => 'Bacillary Dysentery / Shigellosis'],
                ['ref' => '36.2', 'name' => 'บิดอะมีบา', 'name_en' => 'Amebic Dysentery'],
                ['ref' => '37', 'name' => 'ไทฟอยด์/ไข้รากสาดน้อย', 'name_en' => 'Typhoid Fever'],
                ['ref' => '38', 'name' => 'ตับอักเสบจากไวรัส', 'name_en' => 'Viral Hepatitis'],
                ['ref' => '39', 'name' => 'ฝีตับอะมีบา', 'name_en' => 'Amebic Liver Abscess'],
                ['ref' => '40', 'name' => 'นิ่วในถุงน้ำดี/ถุงน้ำดีอักเสบ/ท่อน้ำตับอักเสบ', 'name_en' => 'Gallstones / Cholecystitis / Cholangitis'],
                ['ref' => '41', 'name' => 'ท่อน้ำดีอักเสบ', 'name_en' => 'Cholangitis'],
                ['ref' => '42', 'name' => 'ดีซ่านสรีระในทารกแรกเกิด/ท่อน้ำดีตีบตันแต่กำเนิด', 'name_en' => 'Neonatal Jaundice / Biliary Atresia'],
                ['ref' => '43', 'name' => 'ท่อน้ำดีตีบตันแต่กำเนิด', 'name_en' => 'Biliary Atresia'],
                ['ref' => '44', 'name' => 'ตับแข็ง', 'name_en' => 'Cirrhosis'],
                ['ref' => '45', 'name' => 'มะเร็งตับ/โรคพยาธิใบไม้ตับ', 'name_en' => 'Liver Cancer / Liver Fluke'],
                ['ref' => '46', 'name' => 'ไส้ติ่งอักเสบ', 'name_en' => 'Appendicitis'],
                ['ref' => '47', 'name' => 'เยื่อบุช่องท้องอักเสบ', 'name_en' => 'Peritonitis'],
                ['ref' => '48', 'name' => 'ตับอ่อนอักเสบ', 'name_en' => 'Pancreatitis'],
                ['ref' => '49', 'name' => 'อาหารไม่ย่อย', 'name_en' => 'Dyspepsia'],
                ['ref' => '49.1', 'name' => 'โรคกรดไหลย้อน/เกิร์ด', 'name_en' => 'GERD'],
                ['ref' => '50', 'name' => 'กระเพาะอาหารอักเสบ', 'name_en' => 'Gastritis'],
                ['ref' => '51', 'name' => 'แผลเป็ปติก', 'name_en' => 'Peptic Ulcer'],
                ['ref' => '52', 'name' => 'กระเพาะอาหารทะลุ/แผลเป็ปติกทะลุ', 'name_en' => 'Perforated Peptic Ulcer'],
                ['ref' => '53', 'name' => 'กระเพาะอาหารแตก', 'name_en' => 'Gastric Rupture'],
                ['ref' => '54', 'name' => 'กระเพาะหรือลำไส้อุดกั้น', 'name_en' => 'Intestinal Obstruction'],
                ['ref' => '55', 'name' => 'อาเจียนในเด็ก', 'name_en' => 'Vomiting in Children'],
                ['ref' => '56', 'name' => 'ตับ ม้าม หรือหัวใจฉีกขาด/เลือดตกใน', 'name_en' => 'Internal Bleeding / Organ Laceration'],
                ['ref' => '57', 'name' => 'ไส้เลื่อน', 'name_en' => 'Hernia'],
                ['ref' => '58', 'name' => 'ริดสีดวงทวาร', 'name_en' => 'Hemorrhoids'],
                ['ref' => '58.1', 'name' => 'แผลปริที่ปากทวาร', 'name_en' => 'Anal Fissure'],
                ['ref' => '58.2', 'name' => 'ฝีรอบทวารหนัก/ฝีก้นกบ/ทวารหนักรูสตู', 'name_en' => 'Perianal Abscess / Fistula in Ano'],
                ['ref' => '59', 'name' => 'แผลเปื่อยที่ปาก', 'name_en' => 'Oral Ulcers'],
                ['ref' => '59.1', 'name' => 'แผลแอฟทัส', 'name_en' => 'Aphthous Ulcers'],
                ['ref' => '59.2', 'name' => 'แผลเปื่อยที่เกิดจากการบาดเจ็บ', 'name_en' => 'Traumatic Oral Ulcer'],
                ['ref' => '59.3', 'name' => 'เริมในช่องปากชนิดเฉียบพลัน/เฮอร์แปงไจนา', 'name_en' => 'Acute Herpetic Stomatitis / Herpangina'],
                ['ref' => '59.4', 'name' => 'ปากนกกระจอก', 'name_en' => 'Angular Cheilitis'],
                ['ref' => '59.5', 'name' => 'โรคเชื้อราในช่องปาก/มุมปากเปื่อยจากเชื้อรา', 'name_en' => 'Oral Thrush / Candidiasis'],
                ['ref' => '59.6', 'name' => 'มะเร็งช่องปาก', 'name_en' => 'Oral Cancer'],
                ['ref' => '60', 'name' => 'ปวดฟัน ฟันผุ', 'name_en' => 'Toothache / Dental Caries'],
                ['ref' => '61', 'name' => 'เหงือกอักเสบ', 'name_en' => 'Gingivitis'],
                ['ref' => '62', 'name' => 'ฟันเหลืองดำ/ฟันตกกระ', 'name_en' => 'Tooth Discoloration / Dental Fluorosis'],
            ],

            // -------------------------------------------------------
            // บทที่ 3 โรคระบบประสาทและสมอง (ข้อ 63-90)
            // -------------------------------------------------------
            '000003' => [
                ['ref' => '63', 'name' => 'โปลิโอ', 'name_en' => 'Poliomyelitis'],
                ['ref' => '64', 'name' => 'โรคพิษสุนัขบ้า', 'name_en' => 'Rabies'],
                ['ref' => '65', 'name' => 'สมองอักเสบ', 'name_en' => 'Encephalitis'],
                ['ref' => '65.1', 'name' => 'โรคเรย์ซินโดรม', 'name_en' => "Reye's Syndrome"],
                ['ref' => '66', 'name' => 'เยื่อหุ้มสมองอักเสบ/ไข้กาฬหลังแอ่น', 'name_en' => 'Meningitis / Meningococcal Meningitis'],
                ['ref' => '66.1', 'name' => 'ไข้กาฬหลังแอ่น', 'name_en' => 'Meningococcal Disease'],
                ['ref' => '67', 'name' => 'บาดทะยัก', 'name_en' => 'Tetanus'],
                ['ref' => '67.1', 'name' => 'โบทูลิซึม', 'name_en' => 'Botulism'],
                ['ref' => '68', 'name' => 'ชักจากไข้', 'name_en' => 'Febrile Seizure'],
                ['ref' => '69', 'name' => 'ชักในทารกแรกเกิด', 'name_en' => 'Neonatal Seizure'],
                ['ref' => '70', 'name' => 'โรคลมชัก ลมบ้าหมู', 'name_en' => 'Epilepsy'],
                ['ref' => '71', 'name' => 'ไมเกรน', 'name_en' => 'Migraine'],
                ['ref' => '71.1', 'name' => 'ปวดศีรษะคลัสเตอร์', 'name_en' => 'Cluster Headache'],
                ['ref' => '72', 'name' => 'ปวดศีรษะจากความเครียด', 'name_en' => 'Tension Headache'],
                ['ref' => '73', 'name' => 'เวียนศีรษะ บ้านหมุน', 'name_en' => 'Vertigo'],
                ['ref' => '74', 'name' => 'เป็นลม', 'name_en' => 'Syncope / Fainting'],
                ['ref' => '75', 'name' => 'หมดสติ', 'name_en' => 'Coma / Unconsciousness'],
                ['ref' => '76', 'name' => 'โรคหลอดเลือดสมอง/สมองขาดเลือดชั่วขณะ/อัมพาตครึ่งซีก', 'name_en' => 'Stroke / TIA / Hemiplegia'],
                ['ref' => '77', 'name' => 'อัมพาตเบลล์/เบลล์พัลซี', 'name_en' => "Bell's Palsy"],
                ['ref' => '78', 'name' => 'ภาวะโพแทสเซียมในเลือดต่ำ/อัมพาตครั้งคราว', 'name_en' => 'Hypokalemic Periodic Paralysis'],
                ['ref' => '79', 'name' => 'ไมแอสทีเนียแกรวิส', 'name_en' => 'Myasthenia Gravis'],
                ['ref' => '79.1', 'name' => 'โรคพาร์กินสัน', 'name_en' => "Parkinson's Disease"],
                ['ref' => '80', 'name' => 'สมองพิการ', 'name_en' => 'Cerebral Palsy'],
                ['ref' => '81', 'name' => 'ศีรษะได้รับบาดเจ็บ/เลือดออกในสมอง', 'name_en' => 'Head Injury / Intracranial Hemorrhage'],
                ['ref' => '82', 'name' => 'ฝีสมอง', 'name_en' => 'Brain Abscess'],
                ['ref' => '83', 'name' => 'เนื้องอกสมอง', 'name_en' => 'Brain Tumor'],
                ['ref' => '84', 'name' => 'ไขสันหลังอักเสบเฉียบพลัน', 'name_en' => 'Transverse Myelitis'],
                ['ref' => '85', 'name' => 'ไขสันหลังได้รับบาดเจ็บ', 'name_en' => 'Spinal Cord Injury'],
                ['ref' => '86', 'name' => 'เนื้องอกไขสันหลัง', 'name_en' => 'Spinal Cord Tumor'],
                ['ref' => '87', 'name' => 'ปลายประสาทอักเสบ', 'name_en' => 'Peripheral Neuropathy'],
                ['ref' => '88', 'name' => 'โรคจิตกังวล โรคกังวลทั่วไป/โรคแพนิก', 'name_en' => 'Anxiety / Panic Disorder'],
                ['ref' => '88.1', 'name' => 'โรคแพนิก', 'name_en' => 'Panic Disorder'],
                ['ref' => '88.2', 'name' => 'โรคอารมณ์แปรปรวน โรคซึมเศร้า', 'name_en' => 'Mood Disorder / Depression'],
                ['ref' => '89', 'name' => 'กลุ่มอาการระบายลมหายใจเกิน', 'name_en' => 'Hyperventilation Syndrome'],
                ['ref' => '90', 'name' => 'เด็กไม่อยากไปโรงเรียน/โรคกลัวโรงเรียน', 'name_en' => 'School Phobia'],
            ],

            // -------------------------------------------------------
            // บทที่ 4 โรคระบบไหลเวียนโลหิตและโรคเลือด (ข้อ 91-106.1)
            // -------------------------------------------------------
            '000004' => [
                ['ref' => '91', 'name' => 'ช็อก', 'name_en' => 'Shock'],
                ['ref' => '92', 'name' => 'ความดันโลหิตสูง', 'name_en' => 'Hypertension'],
                ['ref' => '92.1', 'name' => 'หลอดเลือดแดงใหญ่โป่งพอง/ภาวะเลือดเซาะผนังหลอดเลือดแดงใหญ่', 'name_en' => 'Aortic Aneurysm / Dissection'],
                ['ref' => '93', 'name' => 'ความดันตกในท่าเขียน', 'name_en' => 'Orthostatic Hypotension'],
                ['ref' => '94', 'name' => 'ไข้รูมาติก/โรคหัวใจรูมาติก', 'name_en' => 'Rheumatic Fever / Rheumatic Heart Disease'],
                ['ref' => '95', 'name' => 'เยื่อบุหัวใจอักเสบ', 'name_en' => 'Infective Endocarditis'],
                ['ref' => '96', 'name' => 'โรคหัวใจขาดเลือด/โรคหลอดเลือดหัวใจตีบ/โรคหัวใจขาดเลือดชั่วขณะ/โรคกล้ามเนื้อหัวใจตาย', 'name_en' => 'Coronary Artery Disease / Myocardial Infarction'],
                ['ref' => '97', 'name' => 'โรคหัวใจเต้นผิดจังหวะ', 'name_en' => 'Arrhythmia'],
                ['ref' => '98', 'name' => 'หัวใจวาย/หัวใจล้มเหลว', 'name_en' => 'Heart Failure'],
                ['ref' => '99', 'name' => 'หลอดเลือดขอดที่ขา', 'name_en' => 'Varicose Veins'],
                ['ref' => '99.1', 'name' => 'ภาวะหลอดเลือดดำส่วนลึกมีลิ่มเลือด', 'name_en' => 'Deep Vein Thrombosis'],
                ['ref' => '100', 'name' => 'โลหิตจางจากการขาดธาตุเหล็ก', 'name_en' => 'Iron Deficiency Anemia'],
                ['ref' => '101', 'name' => 'โลหิตจางจากเม็ดเลือดแดงแตก/ภาวะพร่องเอนไซม์ จี-6-พีดี', 'name_en' => 'Hemolytic Anemia / G6PD Deficiency'],
                ['ref' => '102', 'name' => 'เม็ดเลือดแดงแตกในทารกแรกเกิด', 'name_en' => 'Hemolytic Disease of Newborn'],
                ['ref' => '103', 'name' => 'โลหิตจางจากไขกระดูกฝ่อ', 'name_en' => 'Aplastic Anemia'],
                ['ref' => '104', 'name' => 'ไอทีพี/ฮีโมฟีเลีย', 'name_en' => 'ITP / Hemophilia'],
                ['ref' => '105', 'name' => 'ทาลัสซีเมีย', 'name_en' => 'Thalassemia'],
                ['ref' => '106', 'name' => 'มะเร็งเม็ดเลือดขาว', 'name_en' => 'Leukemia'],
                ['ref' => '106.1', 'name' => 'มะเร็งต่อมน้ำเหลือง', 'name_en' => 'Lymphoma'],
            ],

            // -------------------------------------------------------
            // บทที่ 5 โรคระบบกระดูกและกล้ามเนื้อ (ข้อ 107-116)
            // -------------------------------------------------------
            '000005' => [
                ['ref' => '107', 'name' => 'ปวดกล้ามเนื้อหลัง', 'name_en' => 'Back Pain / Muscle Strain'],
                ['ref' => '108', 'name' => 'รากประสาทถูกกด/หมอนรองกระดูกสันหลังเคลื่อน/โพรงกระดูกสันหลังแคบ', 'name_en' => 'Herniated Disc / Spinal Stenosis'],
                ['ref' => '108.1', 'name' => 'กระดูกคอเสื่อม/กระดูกงอกออกจากรากประสาท', 'name_en' => 'Cervical Spondylosis'],
                ['ref' => '109', 'name' => 'ข้อเสื่อม/ข้อเข่าเสื่อม', 'name_en' => 'Osteoarthritis / Knee Osteoarthritis'],
                ['ref' => '110', 'name' => 'โรคปวดข้อรูมาตอยด์', 'name_en' => 'Rheumatoid Arthritis'],
                ['ref' => '110.1', 'name' => 'ข้อสันหลังอักเสบเรื้อรัง', 'name_en' => 'Ankylosing Spondylitis'],
                ['ref' => '111', 'name' => 'เอสแอลอี', 'name_en' => 'Systemic Lupus Erythematosus (SLE)'],
                ['ref' => '112', 'name' => 'ข้ออักเสบชนิดติดเชื้อเฉียบพลัน', 'name_en' => 'Septic Arthritis'],
                ['ref' => '113', 'name' => 'ข้อเคล็ด/ข้อแพลง', 'name_en' => 'Sprains and Strains'],
                ['ref' => '114', 'name' => 'เส้นเอ็นอักเสบ/ปลอกหุ้มเส้นเอ็นอักเสบ', 'name_en' => 'Tendonitis / Tenosynovitis'],
                ['ref' => '114.1', 'name' => 'พังผืดส้นเท้าอักเสบ', 'name_en' => 'Plantar Fasciitis'],
                ['ref' => '115', 'name' => 'เส้นประสาทมือถูกพังผืดรัดแน่น/โรคคาร์พัลทูนเนล', 'name_en' => 'Carpal Tunnel Syndrome'],
                ['ref' => '116', 'name' => 'ตะคริว', 'name_en' => 'Muscle Cramps'],
            ],

            // -------------------------------------------------------
            // บทที่ 6 โรคระบบต่อมไร้ท่อและโภชนาการ (ข้อ 117-133)
            // -------------------------------------------------------
            '000006' => [
                ['ref' => '117', 'name' => 'เบาหวาน', 'name_en' => 'Diabetes Mellitus'],
                ['ref' => '117.1', 'name' => 'เบาจืด', 'name_en' => 'Diabetes Insipidus'],
                ['ref' => '117.2', 'name' => 'ไขมันในเลือดสูง/ไขมันในเลือดผิดปกติ', 'name_en' => 'Dyslipidemia'],
                ['ref' => '118', 'name' => 'ภาวะน้ำตาลในเลือดต่ำ', 'name_en' => 'Hypoglycemia'],
                ['ref' => '119', 'name' => 'ภาวะแคลเซียมในเลือดต่ำ', 'name_en' => 'Hypocalcemia'],
                ['ref' => '120', 'name' => 'คอพอก/ต่อมไทรอยด์โต/คอพอกธรรมดา', 'name_en' => 'Goiter / Simple Goiter'],
                ['ref' => '121', 'name' => 'ภาวะต่อมไทรอยด์ทำงานเกิน/พิษจากไทรอยด์/คอพอกเป็นพิษ', 'name_en' => 'Hyperthyroidism / Thyrotoxicosis'],
                ['ref' => '122', 'name' => 'ต่อมไทรอยด์อักเสบ', 'name_en' => 'Thyroiditis'],
                ['ref' => '123', 'name' => 'มะเร็งไทรอยด์', 'name_en' => 'Thyroid Cancer'],
                ['ref' => '124', 'name' => 'ภาวะขาดไทรอยด์/ต่อมไทรอยด์ทำงานน้อย', 'name_en' => 'Hypothyroidism'],
                ['ref' => '125', 'name' => 'โรคคุชชิง', 'name_en' => "Cushing's Syndrome"],
                ['ref' => '126', 'name' => 'โรคแอดดิสัน', 'name_en' => "Addison's Disease"],
                ['ref' => '127', 'name' => 'โรคซีแฮน', 'name_en' => "Sheehan's Syndrome"],
                ['ref' => '128', 'name' => 'โรคเกาต์', 'name_en' => 'Gout'],
                ['ref' => '129', 'name' => 'โรคของผู้หญิงวัยหมดประจำเดือน', 'name_en' => 'Menopause Syndrome'],
                ['ref' => '129.1', 'name' => 'กระดูกพรุน', 'name_en' => 'Osteoporosis'],
                ['ref' => '130', 'name' => 'โรคขาดอาหารในเด็ก', 'name_en' => 'Malnutrition in Children'],
                ['ref' => '131', 'name' => 'โรคขาดวิตามินเอ/เกล็ดกระดี่ขึ้นตา', 'name_en' => 'Vitamin A Deficiency'],
                ['ref' => '132', 'name' => 'โรคเหน็บชา/โรคขาดวิตามินบี 1', 'name_en' => 'Beriberi / Vitamin B1 Deficiency'],
                ['ref' => '133', 'name' => 'ลักปิดลักเปิด', 'name_en' => 'Scurvy / Vitamin C Deficiency'],
            ],

            // -------------------------------------------------------
            // บทที่ 7 โรคระบบทางเดินปัสสาวะ (ข้อ 134-142)
            // -------------------------------------------------------
            '000007' => [
                ['ref' => '134', 'name' => 'ภาวะไตวาย', 'name_en' => 'Renal Failure'],
                ['ref' => '135', 'name' => 'โรคไตเนฟโรติก', 'name_en' => 'Nephrotic Syndrome'],
                ['ref' => '136', 'name' => 'หน่วยไตอักเสบเฉียบพลัน', 'name_en' => 'Acute Glomerulonephritis'],
                ['ref' => '137', 'name' => 'กรวยไตอักเสบ', 'name_en' => 'Acute Pyelonephritis'],
                ['ref' => '138', 'name' => 'นิ่วไต', 'name_en' => 'Renal Calculi'],
                ['ref' => '139', 'name' => 'นิ่วท่อไต', 'name_en' => 'Ureteric Calculi'],
                ['ref' => '140', 'name' => 'นิ่วกระเพาะปัสสาวะ', 'name_en' => 'Bladder Calculi'],
                ['ref' => '141', 'name' => 'กระเพาะปัสสาวะอักเสบ', 'name_en' => 'Cystitis'],
                ['ref' => '142', 'name' => 'ท่อปัสสาวะตีบ', 'name_en' => 'Urethral Stricture'],
            ],

            // -------------------------------------------------------
            // บทที่ 8 โรคระบบอวัยวะสืบพันธุ์ชาย (ข้อ 143-146.1)
            // -------------------------------------------------------
            '000008' => [
                ['ref' => '143', 'name' => 'ต่อมลูกหมากโต', 'name_en' => 'Benign Prostatic Hyperplasia (BPH)'],
                ['ref' => '143.1', 'name' => 'ต่อมลูกหมากอักเสบ', 'name_en' => 'Prostatitis'],
                ['ref' => '144', 'name' => 'หนังหุ้มปลายองคชาตตีบ', 'name_en' => 'Phimosis'],
                ['ref' => '145', 'name' => 'ถุงน้ำที่ถุงอัณฑะ/กล่อนน้ำ', 'name_en' => 'Hydrocele'],
                ['ref' => '146', 'name' => 'หลอดเลือดอัณฑะขอด', 'name_en' => 'Varicocele'],
                ['ref' => '146.1', 'name' => 'อัณฑะบิดตัว', 'name_en' => 'Testicular Torsion'],
            ],

            // -------------------------------------------------------
            // บทที่ 9 โรคระบบอวัยวะสืบพันธุ์หญิงและการตั้งครรภ์ (ข้อ 147-160)
            // -------------------------------------------------------
            '000009' => [
                ['ref' => '147', 'name' => 'ปีกมดลูกอักเสบ/เยื่อบุโพรงมดลูกอักเสบ', 'name_en' => 'Pelvic Inflammatory Disease (PID)'],
                ['ref' => '148', 'name' => 'ตกขาวธรรมดา', 'name_en' => 'Leukorrhoea'],
                ['ref' => '149', 'name' => 'ช่องคลอดอักเสบ', 'name_en' => 'Vaginitis'],
                ['ref' => '149.1', 'name' => 'ช่องคลอดอักเสบจากเชื้อรา', 'name_en' => 'Vaginal Candidiasis'],
                ['ref' => '149.2', 'name' => 'ช่องคลอดอักเสบจากเชื้อทริโคโมแนส', 'name_en' => 'Trichomoniasis'],
                ['ref' => '150', 'name' => 'ปวดประจำเดือน', 'name_en' => 'Dysmenorrhea'],
                ['ref' => '151', 'name' => 'ประจำเดือนไม่มา/ประจำเดือนขาด', 'name_en' => 'Amenorrhea'],
                ['ref' => '152', 'name' => 'ดียูบี', 'name_en' => 'Dysfunctional Uterine Bleeding (DUB)'],
                ['ref' => '152.1', 'name' => 'เนื้องอกมดลูก', 'name_en' => 'Uterine Myoma'],
                ['ref' => '153', 'name' => 'เยื่อบุโพรงมดลูกเจริญผิดที่/เอ็นโดเมทริโอซิส', 'name_en' => 'Endometriosis'],
                ['ref' => '153.1', 'name' => 'เนื้องอกรังไข่/ถุงน้ำรังไข่', 'name_en' => 'Ovarian Tumor / Cyst'],
                ['ref' => '153.2', 'name' => 'กลุ่มอาการถุงน้ำรังไข่หลายใบ/พีซีโอเอส', 'name_en' => 'PCOS'],
                ['ref' => '154', 'name' => 'ภาวะตั้งครรภ์', 'name_en' => 'Pregnancy'],
                ['ref' => '155', 'name' => 'ครรภ์เป็นพิษ', 'name_en' => 'Preeclampsia / Eclampsia'],
                ['ref' => '156', 'name' => 'แพ้ท้อง', 'name_en' => 'Hyperemesis Gravidarum / Morning Sickness'],
                ['ref' => '157', 'name' => 'ครรภ์นอกมดลูก', 'name_en' => 'Ectopic Pregnancy'],
                ['ref' => '158', 'name' => 'ครรภ์ไข่ปลาอุก', 'name_en' => 'Molar Pregnancy'],
                ['ref' => '159', 'name' => 'รกเกาะต่ำ', 'name_en' => 'Placenta Previa'],
                ['ref' => '160', 'name' => 'รกลอกตัวก่อนกำหนด', 'name_en' => 'Abruptio Placentae'],
            ],

            // -------------------------------------------------------
            // บทที่ 10 โรคหู (ข้อ 161-170.1)
            // -------------------------------------------------------
            '000010' => [
                ['ref' => '161', 'name' => 'หูชั้นนอกอักเสบ', 'name_en' => 'Otitis Externa'],
                ['ref' => '162', 'name' => 'โรคเชื้อราในช่องหู', 'name_en' => 'Otomycosis'],
                ['ref' => '163', 'name' => 'หูชั้นกลางอักเสบ', 'name_en' => 'Acute Otitis Media'],
                ['ref' => '164', 'name' => 'หูชั้นในอักเสบเฉียบพลัน/เส้นประสาทการทรงตัวอักเสบ', 'name_en' => 'Labyrinthitis / Vestibular Neuritis'],
                ['ref' => '164.1', 'name' => 'บ้านหมุนจากการเปลี่ยนท่า/บีพีพีวี', 'name_en' => 'BPPV'],
                ['ref' => '164.2', 'name' => 'เนื้องอกประสาทหู', 'name_en' => 'Acoustic Neuroma'],
                ['ref' => '165', 'name' => 'โรคเมเนียร์', 'name_en' => "Meniere's Disease"],
                ['ref' => '166', 'name' => 'หูตึง/หูหนวก', 'name_en' => 'Hearing Loss'],
                ['ref' => '167', 'name' => 'เยื่อแก้วหูทะลุ', 'name_en' => 'Tympanic Membrane Perforation'],
                ['ref' => '168', 'name' => 'แผลถลอกในช่องหู/ขี้หูอุดตันรูหู', 'name_en' => 'Earwax Impaction'],
                ['ref' => '169', 'name' => 'ขี้หูอุดตันรูหู', 'name_en' => 'Cerumen Impaction'],
                ['ref' => '170', 'name' => 'สิ่งแปลกปลอมเข้าหู', 'name_en' => 'Foreign Body in Ear'],
                ['ref' => '170.1', 'name' => 'หูบาดเจ็บจากความกดดันอากาศ', 'name_en' => 'Barotrauma of Ear'],
            ],

            // -------------------------------------------------------
            // บทที่ 11 โรคตา (ข้อ 171-186)
            // -------------------------------------------------------
            '000011' => [
                ['ref' => '171', 'name' => 'เยื่อตาขาวอักเสบจากเชื้อแบคทีเรีย', 'name_en' => 'Bacterial Conjunctivitis'],
                ['ref' => '172', 'name' => 'เยื่อตาขาวอักเสบจากไวรัส', 'name_en' => 'Viral Conjunctivitis'],
                ['ref' => '173', 'name' => 'เยื่อตาขาวอักเสบจากการแพ้', 'name_en' => 'Allergic Conjunctivitis'],
                ['ref' => '174', 'name' => 'ตาอักเสบจากเชื้อหนองใน', 'name_en' => 'Gonococcal Conjunctivitis'],
                ['ref' => '175', 'name' => 'ริดสีดวงตา', 'name_en' => 'Trachoma'],
                ['ref' => '176', 'name' => 'กุ้งยิง', 'name_en' => 'Hordeolum / Stye'],
                ['ref' => '176.1', 'name' => 'หนังตาอักเสบ', 'name_en' => 'Blepharitis'],
                ['ref' => '177', 'name' => 'ท่อน้ำตาอุดตัน/ถุงน้ำตาอักเสบ', 'name_en' => 'Dacryocystitis'],
                ['ref' => '178', 'name' => 'สายตาผิดปกติ/ตาเข', 'name_en' => 'Refractive Errors / Strabismus'],
                ['ref' => '179', 'name' => 'ต้อเนื้อ/ต้อลิ้นหมา', 'name_en' => 'Pterygium / Pinguecula'],
                ['ref' => '180', 'name' => 'ต้อกระจก', 'name_en' => 'Cataract'],
                ['ref' => '181', 'name' => 'ต้อหิน', 'name_en' => 'Glaucoma'],
                ['ref' => '181.1', 'name' => 'จอตาลอก', 'name_en' => 'Retinal Detachment'],
                ['ref' => '181.2', 'name' => 'จุดภาพชัดเสื่อมตามวัย', 'name_en' => 'Age-related Macular Degeneration (AMD)'],
                ['ref' => '182', 'name' => 'กระจกตาอักเสบ/แผลกระจกตา', 'name_en' => 'Keratitis / Corneal Ulcer'],
                ['ref' => '183', 'name' => 'ม่านตาอักเสบ', 'name_en' => 'Uveitis'],
                ['ref' => '184', 'name' => 'เลือดออกใต้ตาขาว', 'name_en' => 'Subconjunctival Hemorrhage'],
                ['ref' => '185', 'name' => 'ตาได้รับบาดเจ็บรุนแรง', 'name_en' => 'Serious Eye Injury / Trauma'],
                ['ref' => '186', 'name' => 'สิ่งแปลกปลอมเข้าตา', 'name_en' => 'Foreign Body in Eye'],
            ],

            // -------------------------------------------------------
            // บทที่ 12 โรคผิวหนัง (ข้อ 187-207.1)
            // -------------------------------------------------------
            '000012' => [
                ['ref' => '187', 'name' => 'เริม', 'name_en' => 'Herpes Simplex'],
                ['ref' => '188', 'name' => 'งูสวัด', 'name_en' => 'Herpes Zoster / Shingles'],
                ['ref' => '189', 'name' => 'หูด', 'name_en' => 'Warts'],
                ['ref' => '189.1', 'name' => 'หงอนไก่', 'name_en' => 'Condyloma Acuminata'],
                ['ref' => '190', 'name' => 'กลาก', 'name_en' => 'Tinea / Ringworm'],
                ['ref' => '191', 'name' => 'เกลื้อน', 'name_en' => 'Tinea Versicolor / Pityriasis'],
                ['ref' => '191.1', 'name' => 'โรคเชื้อราแคนดิดา', 'name_en' => 'Cutaneous Candidiasis'],
                ['ref' => '192', 'name' => 'โรคติดเชื้อแบคทีเรียของผิวหนัง', 'name_en' => 'Bacterial Skin Infections'],
                ['ref' => '192.1', 'name' => 'ฝี', 'name_en' => 'Abscess / Furuncle'],
                ['ref' => '192.2', 'name' => 'แผลพุพอง', 'name_en' => 'Impetigo'],
                ['ref' => '192.3', 'name' => 'แผลอักเสบ', 'name_en' => 'Infected Wound'],
                ['ref' => '192.4', 'name' => 'เนื้อเยื่อใต้ผิวหนังชั้นลึกอักเสบ', 'name_en' => 'Cellulitis'],
                ['ref' => '192.5', 'name' => 'ไฟลามทุ่ง', 'name_en' => 'Erysipelas'],
                ['ref' => '193', 'name' => 'ฝีเต้านม', 'name_en' => 'Mastitis / Breast Abscess'],
                ['ref' => '194', 'name' => 'ต่อมน้ำเหลืองอักเสบ', 'name_en' => 'Lymphadenitis'],
                ['ref' => '195', 'name' => 'หิด', 'name_en' => 'Scabies'],
                ['ref' => '196', 'name' => 'เหา', 'name_en' => 'Pediculosis / Lice'],
                ['ref' => '197', 'name' => 'โรคเรื้อน', 'name_en' => "Leprosy / Hansen's Disease"],
                ['ref' => '198', 'name' => 'ลมพิษ', 'name_en' => 'Urticaria / Hives'],
                ['ref' => '199', 'name' => 'ผิวหนังอักเสบจากการสัมผัส', 'name_en' => 'Contact Dermatitis'],
                ['ref' => '200', 'name' => 'ผิวหนังอักเสบจากภูมิแพ้', 'name_en' => 'Atopic Dermatitis'],
                ['ref' => '200.1', 'name' => 'ผิวหนังอักเสบชนิดเกล็ดรังแค/รังแค', 'name_en' => 'Seborrheic Dermatitis / Dandruff'],
                ['ref' => '201', 'name' => 'รังแค', 'name_en' => 'Dandruff'],
                ['ref' => '202', 'name' => 'ผมร่วง ผมบาง', 'name_en' => 'Alopecia / Hair Loss'],
                ['ref' => '203', 'name' => 'กลากน้ำนม/โรคด่างขาว', 'name_en' => 'Pityriasis Alba / Vitiligo'],
                ['ref' => '203.1', 'name' => 'ฝีนิ้วมือ', 'name_en' => 'Paronychia / Whitlow'],
                ['ref' => '203.2', 'name' => 'โซริอาซิส/โรคเกล็ดเงิน', 'name_en' => 'Psoriasis'],
                ['ref' => '204', 'name' => 'สิว', 'name_en' => 'Acne Vulgaris'],
                ['ref' => '205', 'name' => 'ฝ้า', 'name_en' => 'Chloasma / Melasma'],
                ['ref' => '206', 'name' => 'คีลอยด์/แผลเป็น', 'name_en' => 'Keloid / Hypertrophic Scar'],
                ['ref' => '207', 'name' => 'ตาปลา/หนังหนาด้าน', 'name_en' => 'Corns and Calluses'],
                ['ref' => '207.1', 'name' => 'กลุ่มอาการสตีเวนส์จอห์นสัน', 'name_en' => 'Stevens-Johnson Syndrome (SJS)'],
            ],

            // -------------------------------------------------------
            // บทที่ 13 โรคติดต่อทางเพศสัมพันธ์ (ข้อ 208-212)
            // -------------------------------------------------------
            '000013' => [
                ['ref' => '208', 'name' => 'หนองใน', 'name_en' => 'Gonorrhea'],
                ['ref' => '209', 'name' => 'หนองในเทียม', 'name_en' => 'Chlamydia / Non-gonococcal Urethritis'],
                ['ref' => '210', 'name' => 'แผลริมอ่อน', 'name_en' => 'Chancroid'],
                ['ref' => '211', 'name' => 'ซิฟิลิส', 'name_en' => 'Syphilis'],
                ['ref' => '212', 'name' => 'ฝีมะม่วง', 'name_en' => 'Lymphogranuloma Venereum (LGV)'],
            ],

            // -------------------------------------------------------
            // บทที่ 14 โรคที่เกิดจากอุบัติเหตุฯ (ข้อ 213-223)
            // -------------------------------------------------------
            '000014' => [
                ['ref' => '213', 'name' => 'กระดูกหัก', 'name_en' => 'Fractures'],
                ['ref' => '214', 'name' => 'กระดูกซี่โครงหัก', 'name_en' => 'Rib Fractures'],
                ['ref' => '215', 'name' => 'ก้างปลา/กระดูกติดคอ', 'name_en' => 'Foreign Body in Throat'],
                ['ref' => '216', 'name' => 'จมน้ำ', 'name_en' => 'Drowning'],
                ['ref' => '217', 'name' => 'ไฟฟ้าช็อต', 'name_en' => 'Electrocution / Electrical Injury'],
                ['ref' => '218', 'name' => 'บาดแผลไฟไหม้น้ำร้อนลวก', 'name_en' => 'Burns and Scalds'],
                ['ref' => '218.1', 'name' => 'โรคจากความร้อน', 'name_en' => 'Heat Stroke / Heat Exhaustion'],
                ['ref' => '218.2', 'name' => 'ภาวะตัวเย็นเกิน', 'name_en' => 'Hypothermia'],
                ['ref' => '219', 'name' => 'กินสารพิษหรือยาพิษ', 'name_en' => 'Poisoning'],
                ['ref' => '219.1', 'name' => 'พิษปลาปักเป้า พิษแมงดาทะเล', 'name_en' => 'Puffer Fish / Horseshoe Crab Poisoning'],
                ['ref' => '219.2', 'name' => 'พิษปลาทะเล', 'name_en' => 'Marine Fish Toxins'],
                ['ref' => '219.3', 'name' => 'พิษหอยทะเล', 'name_en' => 'Shellfish Poisoning'],
                ['ref' => '219.4', 'name' => 'พิษคางคก', 'name_en' => 'Toad Poisoning'],
                ['ref' => '219.5', 'name' => 'พิษเห็ด', 'name_en' => 'Mushroom Poisoning'],
                ['ref' => '220', 'name' => 'ตะกั่วเป็นพิษ', 'name_en' => 'Lead Poisoning'],
                ['ref' => '221', 'name' => 'งูกัด', 'name_en' => 'Snake Bite'],
                ['ref' => '222', 'name' => 'สัตว์กัด/แมลงต่อย', 'name_en' => 'Animal Bites / Insect Stings'],
                ['ref' => '223', 'name' => 'ปลิงเข้าอวัยวะ', 'name_en' => 'Leech Infestation'],
            ],

            // -------------------------------------------------------
            // บทที่ 15 โรคติดเชื้อ (ข้อ 224-229.4)
            // -------------------------------------------------------
            '000015' => [
                ['ref' => '224', 'name' => 'มาลาเรีย', 'name_en' => 'Malaria'],
                ['ref' => '225', 'name' => 'ไข้เลือดออก', 'name_en' => 'Dengue Hemorrhagic Fever'],
                ['ref' => '226', 'name' => 'สครับไทฟัส', 'name_en' => 'Scrub Typhus'],
                ['ref' => '227', 'name' => 'เล็ปโตสไปโรซิส/ไข้ฉี่หนู', 'name_en' => 'Leptospirosis'],
                ['ref' => '228', 'name' => 'โลหิตเป็นพิษ', 'name_en' => 'Septicemia / Bacteremia'],
                ['ref' => '229', 'name' => 'โลหิตเป็นพิษในทารกแรกเกิด', 'name_en' => 'Neonatal Sepsis'],
                ['ref' => '229.1', 'name' => 'โรคมือ-เท้า-ปาก', 'name_en' => 'Hand, Foot and Mouth Disease (HFMD)'],
                ['ref' => '229.2', 'name' => 'เมลิออยโดซิส', 'name_en' => 'Melioidosis'],
                ['ref' => '229.3', 'name' => 'แอนแทรกซ์', 'name_en' => 'Anthrax'],
                ['ref' => '229.4', 'name' => 'บรูเซลโลซิส', 'name_en' => 'Brucellosis'],
            ],

            // -------------------------------------------------------
            // บทที่ 16 โรคพยาธิ (ข้อ 230-236)
            // -------------------------------------------------------
            '000016' => [
                ['ref' => '230', 'name' => 'โรคพยาธิไส้เดือน', 'name_en' => 'Ascariasis / Roundworm'],
                ['ref' => '231', 'name' => 'โรคพยาธิเส้นด้าย', 'name_en' => 'Enterobiasis / Threadworm'],
                ['ref' => '232', 'name' => 'โรคพยาธิตัวตืด', 'name_en' => 'Taeniasis / Tapeworm'],
                ['ref' => '233', 'name' => 'โรคพยาธิปากขอ', 'name_en' => 'Hookworm Infection'],
                ['ref' => '234', 'name' => 'โรคพยาธิแส้ม้า', 'name_en' => 'Trichuriasis / Whipworm'],
                ['ref' => '235', 'name' => 'ทริคิโนซิส', 'name_en' => 'Trichinosis'],
                ['ref' => '236', 'name' => 'โรคพยาธิตัวจี๊ด', 'name_en' => 'Gnathostomiasis'],
            ],

            // -------------------------------------------------------
            // บทที่ 17 โรคมะเร็ง (ข้อ 237-237.20)
            // -------------------------------------------------------
            '000017' => [
                ['ref' => '237', 'name' => 'มะเร็ง', 'name_en' => 'Cancer (General)'],
                ['ref' => '237.1', 'name' => 'มะเร็งผิวหนัง', 'name_en' => 'Skin Cancer'],
                ['ref' => '237.2', 'name' => 'มะเร็งเต้านม', 'name_en' => 'Breast Cancer'],
                ['ref' => '237.3', 'name' => 'มะเร็งปากมดลูก', 'name_en' => 'Cervical Cancer'],
                ['ref' => '237.4', 'name' => 'มะเร็งเยื่อบุมดลูก', 'name_en' => 'Endometrial Cancer'],
                ['ref' => '237.5', 'name' => 'มะเร็งรังไข่', 'name_en' => 'Ovarian Cancer'],
                ['ref' => '237.6', 'name' => 'มะเร็งปอด', 'name_en' => 'Lung Cancer'],
                ['ref' => '237.7', 'name' => 'มะเร็งกล่องเสียง', 'name_en' => 'Laryngeal Cancer'],
                ['ref' => '237.8', 'name' => 'มะเร็งโพรงหลังจมูก', 'name_en' => 'Nasopharyngeal Cancer'],
                ['ref' => '237.9', 'name' => 'มะเร็งทอนซิล', 'name_en' => 'Tonsillar Cancer'],
                ['ref' => '237.10', 'name' => 'มะเร็งหลอดอาหาร', 'name_en' => 'Esophageal Cancer'],
                ['ref' => '237.11', 'name' => 'มะเร็งกระเพาะอาหาร', 'name_en' => 'Stomach Cancer'],
                ['ref' => '237.12', 'name' => 'มะเร็งลำไส้เล็ก', 'name_en' => 'Small Intestine Cancer'],
                ['ref' => '237.13', 'name' => 'มะเร็งลำไส้ใหญ่และไส้ตรง', 'name_en' => 'Colorectal Cancer'],
                ['ref' => '237.14', 'name' => 'มะเร็งตับอ่อน', 'name_en' => 'Pancreatic Cancer'],
                ['ref' => '237.15', 'name' => 'มะเร็งต่อมลูกหมาก', 'name_en' => 'Prostate Cancer'],
                ['ref' => '237.16', 'name' => 'มะเร็งกระเพาะปัสสาวะ', 'name_en' => 'Bladder Cancer'],
                ['ref' => '237.17', 'name' => 'มะเร็งอัณฑะ', 'name_en' => 'Testicular Cancer'],
                ['ref' => '237.18', 'name' => 'มะเร็งไต', 'name_en' => 'Renal Cancer'],
                ['ref' => '237.19', 'name' => 'มะเร็งกระดูก', 'name_en' => 'Bone Cancer'],
                ['ref' => '237.20', 'name' => 'มะเร็งลูกตาในเด็ก', 'name_en' => 'Retinoblastoma'],
            ],

            // -------------------------------------------------------
            // บทที่ 18 โรคติดเชื้ออุบัติใหม่ (ข้อ 238-240)
            // -------------------------------------------------------
            '000018' => [
                ['ref' => '238', 'name' => 'เอดส์', 'name_en' => 'AIDS / HIV'],
                ['ref' => '239', 'name' => 'ซาร์ส', 'name_en' => 'SARS'],
                ['ref' => '240', 'name' => 'ไข้หวัดนก/ไข้หวัดใหญ่สัตว์ปีก', 'name_en' => 'Avian Influenza'],
            ],
        ];

        // ============================================================
        // 3. Insert diseases
        // ============================================================
        $diseaseSeq = (int) DB::table('diseases')->max('disease_id') + 1;

        foreach ($diseasesByGroup as $categoryId => $diseases) {
            foreach ($diseases as $data) {
                $diseaseId = str_pad($diseaseSeq, 10, '0', STR_PAD_LEFT);

                DB::table('diseases')->insertOrIgnore([
                    'disease_id'          => $diseaseId,
                    'disease_category_id' => $categoryId,
                    'disease_name'        => $data['name'],
                    'disease_name_en'     => $data['name_en'],
                    'reference'           => $data['ref'],
                    'status'              => '1',
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);

                $diseaseSeq++;
            }
        }
    }
}
