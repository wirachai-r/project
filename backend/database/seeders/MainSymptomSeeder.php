<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MainSymptomSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ============================================================
        // 1. Symptom Categories
        // ============================================================
        $categories = [
            ['symptom_category_id' => '000001', 'category_name' => 'อาการทั่วไปและทั่วร่างกาย', 'category_name_en' => 'Constitutional & General Symptoms'],
            ['symptom_category_id' => '000002', 'category_name' => 'ศีรษะ สมอง และระบบประสาท', 'category_name_en' => 'Head, Brain & Nervous System'],
            ['symptom_category_id' => '000003', 'category_name' => 'ตาและการมองเห็น', 'category_name_en' => 'Eyes & Vision'],
            ['symptom_category_id' => '000004', 'category_name' => 'หูและการได้ยิน', 'category_name_en' => 'Ears & Hearing'],
            ['symptom_category_id' => '000005', 'category_name' => 'จมูกและไซนัส', 'category_name_en' => 'Nose & Sinuses'],
            ['symptom_category_id' => '000006', 'category_name' => 'ปาก ฟัน และลำคอ', 'category_name_en' => 'Mouth, Teeth & Throat'],
            ['symptom_category_id' => '000007', 'category_name' => 'ระบบทางเดินหายใจและหน้าอก', 'category_name_en' => 'Respiratory System & Chest'],
            ['symptom_category_id' => '000008', 'category_name' => 'ระบบทางเดินอาหารและลำไส้', 'category_name_en' => 'Digestive System & Gastrointestinal'],
            ['symptom_category_id' => '000009', 'category_name' => 'ผิวหนัง เส้นผม และแมลงสัตว์กัดต่อย', 'category_name_en' => 'Skin, Hair & Insect Bites'],
            ['symptom_category_id' => '000010', 'category_name' => 'ระบบกล้ามเนื้อ กระดูก และข้อ', 'category_name_en' => 'Musculoskeletal System & Joints'],
            ['symptom_category_id' => '000011', 'category_name' => 'ระบบทางเดินปัสสาวะ', 'category_name_en' => 'Urinary System'],
            ['symptom_category_id' => '000012', 'category_name' => 'ระบบสืบพันธุ์และสุขภาพเพศหญิง', 'category_name_en' => "Women's Health & Reproductive"],
            ['symptom_category_id' => '000013', 'category_name' => 'ระบบสืบพันธุ์และสุขภาพเพศชาย', 'category_name_en' => "Men's Health & Reproductive"],
            ['symptom_category_id' => '000014', 'category_name' => 'ภาวะฉุกเฉิน อุบัติเหตุ และสารพิษ', 'category_name_en' => 'Emergency, Trauma & Toxicology'],
        ];

        foreach ($categories as $cat) {
            DB::table('symptom_categories')->insertOrIgnore([
                ...$cat,
                'status'     => '1',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ============================================================
        // 2. Main Symptoms — เรียงตามดัชนีหนังสือ ก–ฮ
        //    diagram = เลขแผนภูมิ 1–68 เท่านั้น
        // ============================================================
        $symptoms = [
            // ก ─────────────────────────────────────────────────────
            ['name' => 'กลิ่นลำบาก', 'name_en' => 'Difficulty Smelling', 'cat' => '000005', 'diagram' => 34],
            ['name' => 'ก้อนที่ขาหนีบ', 'name_en' => 'Inguinal Mass', 'cat' => '000010', 'diagram' => 14],
            ['name' => 'ก้อนบวม (มีก้อน)', 'name_en' => 'Local Mass / Swelling', 'cat' => '000010', 'diagram' => 14],
            ['name' => 'กามโรคในผู้ชาย', 'name_en' => 'STD in Male', 'cat' => '000013', 'diagram' => 59],
            ['name' => 'กามโรคในผู้หญิง', 'name_en' => 'STD in Female', 'cat' => '000012', 'diagram' => 60],

            // ข ─────────────────────────────────────────────────────
            ['name' => 'ข้ออักเสบ', 'name_en' => 'Arthritis', 'cat' => '000010', 'diagram' => 52],
            ['name' => 'ขัดเบา', 'name_en' => 'Dysuria', 'cat' => '000011', 'diagram' => 54],
            ['name' => 'ขาบวมข้างเดียว', 'name_en' => 'Unilateral Leg Edema', 'cat' => '000010', 'diagram' => 14],
            ['name' => 'แขนขาเคลื่อนไหวผิดปกติ', 'name_en' => 'Abnormal Limb Movement', 'cat' => '000002', 'diagram' => 18],
            ['name' => 'แขนขาอ่อนแรง', 'name_en' => 'Limb Weakness', 'cat' => '000002', 'diagram' => 19],
            ['name' => 'ไข่ดันบวม', 'name_en' => 'Scrotal Swelling', 'cat' => '000013', 'diagram' => 14],
            ['name' => 'ไข้', 'name_en' => 'Fever', 'cat' => '000001', 'diagram' => 1],
            ['name' => 'ไข้ + แขนขาอ่อนแรง', 'name_en' => 'Fever with Limb Weakness', 'cat' => '000001', 'diagram' => 19],
            ['name' => 'ไข้ + คางบวม/คอบวม', 'name_en' => 'Fever with Swollen Jaw/Neck', 'cat' => '000001', 'diagram' => 33],
            ['name' => 'ไข้ + จุดแดง-จ้ำเขียว', 'name_en' => 'Fever with Petechiae/Purpura', 'cat' => '000001', 'diagram' => 10],
            ['name' => 'ไข้ + เจ็บคอ', 'name_en' => 'Fever with Sore Throat', 'cat' => '000001', 'diagram' => 35],
            ['name' => 'ไข้ + เจ็บหน้าอก', 'name_en' => 'Fever with Chest Pain', 'cat' => '000001', 'diagram' => 3],
            ['name' => 'ไข้ + ชัก', 'name_en' => 'Febrile Seizure', 'cat' => '000001', 'diagram' => 18],
            ['name' => 'ไข้ + ซีด', 'name_en' => 'Fever with Pallor', 'cat' => '000001', 'diagram' => 9],
            ['name' => 'ไข้ + ดีซ่าน (ตาเหลือง)', 'name_en' => 'Fever with Jaundice', 'cat' => '000001', 'diagram' => 11],
            ['name' => 'ไข้ + ท้องเดิน', 'name_en' => 'Fever with Diarrhea', 'cat' => '000001', 'diagram' => 47],
            ['name' => 'ไข้นานกว่า 1 เดือน', 'name_en' => 'Prolonged Fever > 1 Month', 'cat' => '000001', 'diagram' => 1],
            ['name' => 'ไข้ + น้ำมูกไหล (ไข้หวัด)', 'name_en' => 'Fever with Common Cold', 'cat' => '000001', 'diagram' => 2],
            ['name' => 'ไข้ + บวม', 'name_en' => 'Fever with Edema', 'cat' => '000001', 'diagram' => 13],
            ['name' => 'ไข้ + ปวดข้อ', 'name_en' => 'Fever with Joint Pain', 'cat' => '000001', 'diagram' => 52],
            ['name' => 'ไข้ + ปวดท้อง', 'name_en' => 'Fever with Abdominal Pain', 'cat' => '000001', 'diagram' => 44],
            ['name' => 'ไข้ + ปวดหลัง', 'name_en' => 'Fever with Back Pain', 'cat' => '000001', 'diagram' => 53],
            ['name' => 'ไข้ + ปวดหู', 'name_en' => 'Fever with Ear Pain', 'cat' => '000001', 'diagram' => 26],
            ['name' => 'ไข้ + ผื่น/ตุ่มขึ้น', 'name_en' => 'Fever with Rash/Vesicles', 'cat' => '000001', 'diagram' => 4],
            ['name' => 'ไข้ + หอบ', 'name_en' => 'Fever with Dyspnea', 'cat' => '000001', 'diagram' => 3],
            ['name' => 'ไข้ + ไอ', 'name_en' => 'Fever with Cough', 'cat' => '000001', 'diagram' => 2],

            // ค ─────────────────────────────────────────────────────
            ['name' => 'คลื่นไส้', 'name_en' => 'Nausea', 'cat' => '000008', 'diagram' => 42],
            ['name' => 'คอเจ็บ', 'name_en' => 'Sore Throat', 'cat' => '000006', 'diagram' => 35],
            ['name' => 'คอบวม', 'name_en' => 'Neck Swelling', 'cat' => '000006', 'diagram' => 33],
            ['name' => 'คอพอก (คอโต)', 'name_en' => 'Goiter', 'cat' => '000006', 'diagram' => 14],
            ['name' => 'คอเอียง', 'name_en' => 'Torticollis', 'cat' => '000002', 'diagram' => 18],
            ['name' => 'คัดจมูก', 'name_en' => 'Nasal Congestion', 'cat' => '000005', 'diagram' => 30],
            ['name' => 'คัน', 'name_en' => 'Pruritus / Itching', 'cat' => '000009', 'diagram' => 62],
            ['name' => 'คันก้น', 'name_en' => 'Anal Itching', 'cat' => '000009', 'diagram' => 62],
            ['name' => 'คันคอ', 'name_en' => 'Throat Itching', 'cat' => '000006', 'diagram' => 30, 'diagrams' => [30, 38]], // มีเลข 38 คันคอ ด้วยในหน้าแรก ชี้ไปที่ 30, 38
            ['name' => 'คันจมูก', 'name_en' => 'Nasal Itching', 'cat' => '000005', 'diagram' => 30],
            ['name' => 'คันตา', 'name_en' => 'Eye Itching', 'cat' => '000003', 'diagram' => 25],
            ['name' => 'คันในช่องคลอด', 'name_en' => 'Vaginal Itching', 'cat' => '000012', 'diagram' => 57],
            ['name' => 'คันศีรษะ', 'name_en' => 'Scalp Itching', 'cat' => '000009', 'diagram' => 62],
            ['name' => 'คันหู', 'name_en' => 'Ear Itching', 'cat' => '000004', 'diagram' => 26],
            ['name' => 'คางบวม', 'name_en' => 'Jaw Swelling', 'cat' => '000006', 'diagram' => 33],
            ['name' => 'เคืองตา', 'name_en' => 'Eye Irritation', 'cat' => '000003', 'diagram' => 25],

            // ง ─────────────────────────────────────────────────────
            ['name' => 'งูกัด', 'name_en' => 'Snake Bite', 'cat' => '000014', 'diagram' => 68],

            // จ ─────────────────────────────────────────────────────
            ['name' => 'จุดแดง-จ้ำเขียว', 'name_en' => 'Petechiae / Purpura', 'cat' => '000001', 'diagram' => 10],
            ['name' => 'เจ็บคอ', 'name_en' => 'Sore Throat', 'cat' => '000006', 'diagram' => 35],
            ['name' => 'เจ็บตา', 'name_en' => 'Eye Pain', 'cat' => '000003', 'diagram' => 23],
            ['name' => 'เจ็บปาก', 'name_en' => 'Mouth Pain', 'cat' => '000006', 'diagram' => 36],
            ['name' => 'เจ็บหน้าอก', 'name_en' => 'Chest Pain', 'cat' => '000007', 'diagram' => 40],
            ['name' => 'เจ็บหู', 'name_en' => 'Ear Pain', 'cat' => '000004', 'diagram' => 26],
            ['name' => 'ใจสั่น', 'name_en' => 'Palpitation', 'cat' => '000007', 'diagram' => 41],

            // ช ─────────────────────────────────────────────────────
            ['name' => 'ช็อก', 'name_en' => 'Shock', 'cat' => '000014', 'diagram' => 17],
            ['name' => 'ชัก', 'name_en' => 'Seizure / Convulsion', 'cat' => '000002', 'diagram' => 18],
            ['name' => 'ชัก + มีไข้', 'name_en' => 'Febrile Seizure', 'cat' => '000002', 'diagram' => 18],
            ['name' => 'ชา', 'name_en' => 'Numbness', 'cat' => '000002', 'diagram' => 20],

            // ซ ─────────────────────────────────────────────────────
            ['name' => 'ซีด', 'name_en' => 'Pallor / Anemia', 'cat' => '000001', 'diagram' => 8],
            ['name' => 'ซีด + มีไข้', 'name_en' => 'Pallor with Fever', 'cat' => '000001', 'diagram' => 9],

            // ด ─────────────────────────────────────────────────────
            ['name' => 'ดีซ่าน', 'name_en' => 'Jaundice', 'cat' => '000001', 'diagram' => 11],
            ['name' => 'ดีซ่านในทารกแรกเกิด', 'name_en' => 'Neonatal Jaundice', 'cat' => '000002', 'diagram' => 12],

            // ต ─────────────────────────────────────────────────────
            ['name' => 'ตกขาว', 'name_en' => 'Vaginal Discharge', 'cat' => '000012', 'diagram' => 57],
            ['name' => 'ตกเลือดทางช่องคลอด', 'name_en' => 'Vaginal Bleeding', 'cat' => '000012', 'diagram' => 58],
            ['name' => 'ตกเลือดระหว่างตั้งครรภ์', 'name_en' => 'Bleeding during Pregnancy', 'cat' => '000012', 'diagram' => 58],
            ['name' => 'ต่อมไทรอยด์โต', 'name_en' => 'Enlarged Thyroid Goiter', 'cat' => '000006', 'diagram' => 14],
            ['name' => 'ต่อมน้ำเหลืองโต', 'name_en' => 'Lymphadenopathy', 'cat' => '000010', 'diagram' => 14],
            ['name' => 'ตะคริว', 'name_en' => 'Muscle Cramp', 'cat' => '000010', 'diagram' => 18],
            ['name' => 'ตัวร้อน', 'name_en' => 'Feverish Body Heat', 'cat' => '000001', 'diagram' => 1],
            ['name' => 'ตาเจ็บ', 'name_en' => 'Eye Pain', 'cat' => '000003', 'diagram' => 23],
            ['name' => 'ตาแฉะ', 'name_en' => 'Watery Eye / Discharge', 'cat' => '000003', 'diagram' => 25],
            ['name' => 'ตาแดง', 'name_en' => 'Red Eye', 'cat' => '000003', 'diagram' => 25],
            ['name' => 'ตาปรือ', 'name_en' => 'Droopy Eyes / Ptosis', 'cat' => '000003', 'diagram' => 19],
            ['name' => 'ตาฟ้าฟาง', 'name_en' => 'Dimness of Sight', 'cat' => '000003', 'diagram' => 24],
            ['name' => 'ตามัว', 'name_en' => 'Blurred Vision', 'cat' => '000003', 'diagram' => 24],
            ['name' => 'ตามีสิ่งแปลกปลอมเข้า', 'name_en' => 'Foreign Body in Eye', 'cat' => '000003', 'diagram' => 25],
            ['name' => 'ตาเหลือง', 'name_en' => 'Yellow Sclera / Jaundice', 'cat' => '000001', 'diagram' => 11],
            ['name' => 'ตุ่มขึ้นตามตัว', 'name_en' => 'Generalized Papules / Rashes', 'cat' => '000009', 'diagram' => 63],
            ['name' => 'ตุ่มขึ้น + มีไข้', 'name_en' => 'Vesicles with Fever', 'cat' => '000009', 'diagram' => 4],
            ['name' => 'ตุ่มคันตามตัว', 'name_en' => 'Pruritic Papules', 'cat' => '000009', 'diagram' => 64],
            ['name' => 'เต้านมเป็นก้อน', 'name_en' => 'Breast Mass', 'cat' => '000012', 'diagram' => 14],
            ['name' => 'เต้านมเป็นฝี', 'name_en' => 'Breast Abscess', 'cat' => '000012', 'diagram' => 14],

            // ถ ─────────────────────────────────────────────────────
            ['name' => 'ถ่ายดำ', 'name_en' => 'Melena / Black Stool', 'cat' => '000008', 'diagram' => 50],
            ['name' => 'ถ่ายเป็นพยาธิ', 'name_en' => 'Passing Worms in Stool', 'cat' => '000008', 'diagram' => 66],
            ['name' => 'ถ่ายเป็นมูก/มูกปนเลือด', 'name_en' => 'Dysentery Stool', 'cat' => '000008', 'diagram' => 49],
            ['name' => 'ถ่ายเป็นเลือด', 'name_en' => 'Hematochezia / Bloody Stool', 'cat' => '000008', 'diagram' => 50],

            // ท ─────────────────────────────────────────────────────
            ['name' => 'ทวารหนักโผล่', 'name_en' => 'Rectal Prolapse', 'cat' => '000008', 'diagram' => 66],
            ['name' => 'ท้องเดิน (ท้องเสีย ท้องร่วง)', 'name_en' => 'Diarrhea', 'cat' => '000008', 'diagram' => 47],
            ['name' => 'ท้องเดินเรื้อรัง', 'name_en' => 'Chronic Diarrhea', 'cat' => '000008', 'diagram' => 48],
            ['name' => 'ท้องบวม', 'name_en' => 'Abdominal Swelling', 'cat' => '000008', 'diagram' => 13],
            ['name' => 'ท้องผูก', 'name_en' => 'Constipation', 'cat' => '000008', 'diagram' => 51],
            ['name' => 'เท้าบวม', 'name_en' => 'Foot Edema', 'cat' => '000010', 'diagram' => 13],

            // น ─────────────────────────────────────────────────────
            ['name' => 'น้ำมูกไหล', 'name_en' => 'Rhinorrhea / Runny Nose', 'cat' => '000005', 'diagram' => 30],
            ['name' => 'น้ำหนักมาก (น้ำหนักขึ้น)', 'name_en' => 'Weight Gain', 'cat' => '000001', 'diagram' => 7],
            ['name' => 'น้ำหนักลด', 'name_en' => 'Weight Loss', 'cat' => '000001', 'diagram' => 6],
            ['name' => 'แน่นจมูก', 'name_en' => 'Nasal Obstruction', 'cat' => '000005', 'diagram' => 30],

            // บ ─────────────────────────────────────────────────────
            ['name' => 'บวมเฉพาะที่', 'name_en' => 'Localized Swelling', 'cat' => '000010', 'diagram' => 14],
            ['name' => 'บวมทั่วไป', 'name_en' => 'Generalized Edema', 'cat' => '000001', 'diagram' => 13],
            ['name' => 'บาดเจ็บที่ศีรษะ', 'name_en' => 'Head Trauma', 'cat' => '000002', 'diagram' => 15],
            ['name' => 'บ้านหมุน', 'name_en' => 'Vertigo', 'cat' => '000002', 'diagram' => 22],
            ['name' => 'บิด', 'name_en' => 'Amoebiasis / Dysentery', 'cat' => '000008', 'diagram' => 49],

            // ป ─────────────────────────────────────────────────────
            ['name' => 'ประจำเดือนขาด/ไม่มา', 'name_en' => 'Amenorrhea', 'cat' => '000012', 'diagram' => 56],
            ['name' => 'ประจำเดือนออกมาก', 'name_en' => 'Heavy Menstrual Bleeding', 'cat' => '000012', 'diagram' => 58],
            ['name' => 'ปวดข้อ', 'name_en' => 'Joint Pain', 'cat' => '000010', 'diagram' => 52],
            ['name' => 'ปวดต้นคอ/ท้ายทอย', 'name_en' => 'Neck Pain', 'cat' => '000002', 'diagram' => 21],
            ['name' => 'ปวดตรงลิ้นปี่/ยอดอก', 'name_en' => 'Epigastric Pain', 'cat' => '000008', 'diagram' => 43],
            ['name' => 'ปวดตา', 'name_en' => 'Eye Pain', 'cat' => '000003', 'diagram' => 23],
            ['name' => 'ปวดท้อง', 'name_en' => 'Abdominal Pain', 'cat' => '000008', 'diagram' => 43],
            ['name' => 'ปวดท้องน้อยในผู้หญิงวัยเจริญพันธุ์', 'name_en' => 'Lower Abdominal Pain in Women', 'cat' => '000012', 'diagram' => 46],
            ['name' => 'ปวดท้องแบบเป็นๆ หายๆ', 'name_en' => 'Colicky Abdominal Pain', 'cat' => '000008', 'diagram' => 45],
            ['name' => 'ปวดท้อง + มีไข้', 'name_en' => 'Abdominal Pain with Fever', 'cat' => '000008', 'diagram' => 44],
            ['name' => 'ปวดใบหน้าข้างเดียว', 'name_en' => 'Trigeminal Neuralgia / Facial Pain', 'cat' => '000002', 'diagram' => 21],
            ['name' => 'ปวดฟัน', 'name_en' => 'Toothache', 'cat' => '000006', 'diagram' => 32],
            ['name' => 'ปวดศีรษะ', 'name_en' => 'Headache', 'cat' => '000002', 'diagram' => 21],
            ['name' => 'ปวดหลัง', 'name_en' => 'Back Pain', 'cat' => '000010', 'diagram' => 53],
            ['name' => 'ปวดหู', 'name_en' => 'Ear Pain', 'cat' => '000004', 'diagram' => 26],
            ['name' => 'ปวดอัณฑะ', 'name_en' => 'Testicular Pain', 'cat' => '000013', 'diagram' => 14],
            ['name' => 'ปวดเอ็น', 'name_en' => 'Tendon Pain', 'cat' => '000010', 'diagram' => 52],
            ['name' => 'ปัสสาวะกะปริดกะปรอย', 'name_en' => 'Urine Dribbling', 'cat' => '000011', 'diagram' => 54],
            ['name' => 'ปัสสาวะขุ่น', 'name_en' => 'Cloudy Urine', 'cat' => '000011', 'diagram' => 55],
            ['name' => 'ปัสสาวะดำ', 'name_en' => 'Dark Urine', 'cat' => '000011', 'diagram' => 55],
            ['name' => 'ปัสสาวะแดง/เป็นเลือด', 'name_en' => 'Hematuria / Red Urine', 'cat' => '000011', 'diagram' => 55],
            ['name' => 'ปัสสาวะบ่อย', 'name_en' => 'Urinary Frequency', 'cat' => '000011', 'diagram' => 54],
            ['name' => 'ปัสสาวะมาก', 'name_en' => 'Polyuria', 'cat' => '000011', 'diagram' => 54],
            ['name' => 'ปัสสาวะไม่ออก/ออกน้อย', 'name_en' => 'Urinary Retention', 'cat' => '000011', 'diagram' => 54],
            ['name' => 'ปัสสาวะลำบาก', 'name_en' => 'Dysuria', 'cat' => '000011', 'diagram' => 54],
            ['name' => 'ปัสสาวะสีผิดปกติ', 'name_en' => 'Abnormal Urine Color', 'cat' => '000011', 'diagram' => 55],
            ['name' => 'ปากเจ็บ', 'name_en' => 'Sore Mouth', 'cat' => '000006', 'diagram' => 36],
            ['name' => 'ปากเบี้ยว', 'name_en' => 'Mouth Deviation', 'cat' => '000002', 'diagram' => 19],
            ['name' => 'ปากเปื่อย', 'name_en' => 'Mouth Aphthous Ulcer', 'cat' => '000006', 'diagram' => 36],
            ['name' => 'เป็นลม', 'name_en' => 'Syncope / Fainting', 'cat' => '000002', 'diagram' => 15],

            // ผ ─────────────────────────────────────────────────────
            ['name' => 'ผมร่วง/ผมบาง', 'name_en' => 'Hair Loss / Thinning', 'cat' => '000009', 'diagram' => 65],
            ['name' => 'ผื่นขึ้นตามตัว', 'name_en' => 'Generalized Skin Rash', 'cat' => '000009', 'diagram' => 63],
            ['name' => 'ผื่นขึ้น + มีไข้', 'name_en' => 'Rash with Fever', 'cat' => '000009', 'diagram' => 4],
            ['name' => 'ผื่นคันตามตัว', 'name_en' => 'Pruritic Rash', 'cat' => '000009', 'diagram' => 64],
            ['name' => 'แผลที่อวัยวะเพศในผู้ชาย', 'name_en' => 'Male Genital Ulcer', 'cat' => '000013', 'diagram' => 59],
            ['name' => 'แผลที่อวัยวะเพศในผู้หญิง', 'name_en' => 'Female Genital Ulcer', 'cat' => '000012', 'diagram' => 60],
            ['name' => 'แผลในปาก', 'name_en' => 'Stomatitis / Oral Ulcer', 'cat' => '000006', 'diagram' => 36],

            // พ ─────────────────────────────────────────────────────
            ['name' => 'พยาธิ', 'name_en' => 'Parasitic Worms', 'cat' => '000008', 'diagram' => 66],

            // ฟ ─────────────────────────────────────────────────────
            ['name' => 'ฟันมีเลือดออก', 'name_en' => 'Bleeding Gums', 'cat' => '000006', 'diagram' => 32],
            ['name' => 'ฟันเหลืองดำ', 'name_en' => 'Tooth Discoloration', 'cat' => '000006', 'diagram' => 32],

            // ม ─────────────────────────────────────────────────────
            ['name' => 'มีก้อนบวมเฉพาะที่', 'name_en' => 'Localized Swelling Mass', 'cat' => '000010', 'diagram' => 14],
            ['name' => 'มีเสียงในหู', 'name_en' => 'Tinnitus', 'cat' => '000004', 'diagram' => 27],
            ['name' => 'มือจีบเกร็ง', 'name_en' => 'Carpopedal Spasm', 'cat' => '000010', 'diagram' => 18],
            ['name' => 'มือเท้าเกร็ง', 'name_en' => 'Limb Spasm', 'cat' => '000010', 'diagram' => 18],
            ['name' => 'แมลงเข้าหู', 'name_en' => 'Insect in Ear', 'cat' => '000004', 'diagram' => 26],
            ['name' => 'แมลงต่อย', 'name_en' => 'Insect Sting', 'cat' => '000009', 'diagram' => 67],
            ['name' => 'ไม่มีเสียง (เสียงแหบ)', 'name_en' => 'Loss of Voice / Hoarseness', 'cat' => '000006', 'diagram' => 37],

            // ร ─────────────────────────────────────────────────────
            ['name' => 'ริมฝีปากบวม', 'name_en' => 'Swollen Lips', 'cat' => '000006', 'diagram' => 14],
            ['name' => 'โรคผิวหนัง', 'name_en' => 'Skin Disease', 'cat' => '000009', 'diagram' => 61],
            ['name' => 'โรคฟัน', 'name_en' => 'Dental Caries / Disease', 'cat' => '000006', 'diagram' => 32],
            ['name' => 'โรคหนอนพยาธิ', 'name_en' => 'Helminthiasis', 'cat' => '000008', 'diagram' => 66],

            // ล ─────────────────────────────────────────────────────
            ['name' => 'ลมพิษ', 'name_en' => 'Urticaria / Hives', 'cat' => '000001', 'diagram' => 14],
            ['name' => 'ลิ้นเป็นฝ้าขาว', 'name_en' => 'Oral Thrush / White Tongue', 'cat' => '000006', 'diagram' => 36],
            ['name' => 'เลือดกำเดาไหล', 'name_en' => 'Epistaxis / Nosebleed', 'cat' => '000005', 'diagram' => 31],
            ['name' => 'เลือดออกจากฟัน', 'name_en' => 'Bleeding from Teeth/Gums', 'cat' => '000006', 'diagram' => 32],
            ['name' => 'เลือดออกจากรูหู', 'name_en' => 'Otorrhagia / Bleeding from Ear', 'cat' => '000004', 'diagram' => 29],
            ['name' => 'เลือดออกจากช่องคลอด', 'name_en' => 'Vaginal Bleeding', 'cat' => '000012', 'diagram' => 58],
            ['name' => 'โลหิตจาง', 'name_en' => 'Anemia', 'cat' => '000001', 'diagram' => 8],

            // ว ─────────────────────────────────────────────────────
            ['name' => 'วงด่าง', 'name_en' => 'Leukoderma / Depigmentation', 'cat' => '000009', 'diagram' => 63],
            ['name' => 'เวียนศีรษะ/วิงเวียน', 'name_en' => 'Dizziness / Lightheadedness', 'cat' => '000002', 'diagram' => 22],

            // ศ ─────────────────────────────────────────────────────
            ['name' => 'ศีรษะได้รับบาดเจ็บ', 'name_en' => 'Head Injury', 'cat' => '000002', 'diagram' => 15],

            // ส ─────────────────────────────────────────────────────
            ['name' => 'สลบ', 'name_en' => 'Unconsciousness / Coma', 'cat' => '000002', 'diagram' => 16],
            ['name' => 'สิ่งแปลกปลอมเข้าจมูก', 'name_en' => 'Foreign Body in Nose', 'cat' => '000005', 'diagram' => 30],
            ['name' => 'สิ่งแปลกปลอมเข้าตา', 'name_en' => 'Foreign Body in Eye', 'cat' => '000003', 'diagram' => 25],
            ['name' => 'สิ่งแปลกปลอมเข้าหู', 'name_en' => 'Foreign Body in Ear', 'cat' => '000004', 'diagram' => 26],
            ['name' => 'เสียงแหบ', 'name_en' => 'Hoarseness', 'cat' => '000006', 'diagram' => 37],

            // ห ─────────────────────────────────────────────────────
            ['name' => 'หนองไหลจากท่อปัสสาวะ', 'name_en' => 'Urethral Purulent Discharge', 'cat' => '000013', 'diagram' => 59],
            ['name' => 'หนังตาตก', 'name_en' => 'Ptosis / Eyelid Drooping', 'cat' => '000003', 'diagram' => 19],
            ['name' => 'หนังตาบวม', 'name_en' => 'Eyelid Edema', 'cat' => '000003', 'diagram' => 14],
            ['name' => 'หน้าบวม', 'name_en' => 'Facial Swelling', 'cat' => '000001', 'diagram' => 13],
            ['name' => 'หน้ามืด', 'name_en' => 'Presyncope / Faintness', 'cat' => '000002', 'diagram' => 22],
            ['name' => 'หมดสติ', 'name_en' => 'Loss of Consciousness', 'cat' => '000002', 'diagram' => 16],
            ['name' => 'หวัด', 'name_en' => 'Common Cold', 'cat' => '000005', 'diagram' => 30],
            ['name' => 'หอบ', 'name_en' => 'Dyspnea / Wheezing', 'cat' => '000007', 'diagram' => 39],
            ['name' => 'หูตึง/หูหนวก', 'name_en' => 'Hearing Loss', 'cat' => '000004', 'diagram' => 28],
            ['name' => 'หูมีเลือดไหล', 'name_en' => 'Otorrhagia', 'cat' => '000004', 'diagram' => 29],
            ['name' => 'หูมีสิ่งแปลกปลอมเข้า', 'name_en' => 'Foreign Body in Ear', 'cat' => '000004', 'diagram' => 26],
            ['name' => 'หูมีเสียงดัง', 'name_en' => 'Ringing in Ear', 'cat' => '000004', 'diagram' => 27],
            ['name' => 'หูหนองไหล/หูนํ้าหนวก', 'name_en' => 'Otorrhea / Ear Discharge', 'cat' => '000004', 'diagram' => 29],
            ['name' => 'หูมีอาการปวด', 'name_en' => 'Ear Pain', 'cat' => '000004', 'diagram' => 26],
            ['name' => 'หูอื้อ', 'name_en' => 'Muffled Hearing', 'cat' => '000004', 'diagram' => 27],
            ['name' => 'เหงื่อออกตามมือตามเท้า', 'name_en' => 'Palmar/Plantar Hyperhidrosis', 'cat' => '000001', 'diagram' => 41],
            ['name' => 'เหงือกบวม', 'name_en' => 'Gingival Swelling', 'cat' => '000006', 'diagram' => 32],
            ['name' => 'เห็นเงาหยากไย่', 'name_en' => 'Vitreous Floaters', 'cat' => '000003', 'diagram' => 24],
            ['name' => 'เห็นภาพซ้อน', 'name_en' => 'Diplopia / Double Vision', 'cat' => '000003', 'diagram' => 24],
            ['name' => 'เห็นแสงแวบคล้ายฟ้าแลบ', 'name_en' => 'Photopsia / Light Flashes', 'cat' => '000003', 'diagram' => 24],
            ['name' => 'เหนื่อยง่าย', 'name_en' => 'Easy Fatigability', 'cat' => '000001', 'diagram' => 39],

            // อ ─────────────────────────────────────────────────────
            ['name' => 'อ้วน', 'name_en' => 'Obesity', 'cat' => '000001', 'diagram' => 7],
            ['name' => 'อ่อนเพลีย', 'name_en' => 'Malaise / Fatigue', 'cat' => '000001', 'diagram' => 5],
            ['name' => 'อัณฑะบวม', 'name_en' => 'Testicular Swelling', 'cat' => '000013', 'diagram' => 14],
            ['name' => 'อัมพาต', 'name_en' => 'Paralysis / Stroke', 'cat' => '000002', 'diagram' => 19],
            ['name' => 'อาเจียน', 'name_en' => 'Vomiting', 'cat' => '000008', 'diagram' => 42],
            ['name' => 'อาเจียนเป็นเลือด', 'name_en' => 'Hematemesis', 'cat' => '000008', 'diagram' => 42],
            ['name' => 'อุจจาระร่วง', 'name_en' => 'Acute Diarrhea / Gastroenteritis', 'cat' => '000008', 'diagram' => 47],
            ['name' => 'ไอ', 'name_en' => 'Cough', 'cat' => '000007', 'diagram' => 38],
            ['name' => 'ไอเป็นเลือด', 'name_en' => 'Hemoptysis', 'cat' => '000007', 'diagram' => 38],
        ];

        // ============================================================
        // 3. Insert symptoms (ข้าม duplicate name + cat เดียวกัน)
        // ============================================================
        $seenKeys = [];
        $id       = 1;

        foreach ($symptoms as $s) {
            $key = $s['name'] . '||' . $s['cat'];
            if (isset($seenKeys[$key])) {
                continue;
            }
            $seenKeys[$key] = true;

            DB::table('main_symptoms')->insertOrIgnore([
                'symptom_id'          => str_pad($id, 10, '0', STR_PAD_LEFT),
                'symptom_name'        => $s['name'],
                'symptom_name_en'     => $s['name_en'],
                'description'         => 'แผนภูมิที่ ' . implode(', ', $s['diagrams'] ?? [$s['diagram']]),
                'symptom_image'       => null,
                'status'              => '1',
                'symptom_category_id' => $s['cat'],
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);

            $id++;
        }

        $this->command->info('✅ MainSymptomSeeder สำเร็จ');
        $this->command->info('   📂 categories : ' . count($categories));
        $this->command->info('   💊 symptoms   : ' . ($id - 1));
    }
}
