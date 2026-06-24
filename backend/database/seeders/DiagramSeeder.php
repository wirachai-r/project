<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * DiagramSeeder
 * ข้อมูลจากสารบัญ "ตำราการตรวจรักษาโรคทั่วไป"
 * แผนภูมิที่ 1-68 (หน้า 8-220)
 *
 * diagram_id = 5 หลัก เช่น 00001, 00002
 * entry_box_id = null (รอ seed QuestionBox ทีหลัง)
 */
class DiagramSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $diagrams = [
            // เล่ม 1 — หน้า 8-220
            ['id' => '00001', 'name' => 'ไข้',                                                      'name_en' => 'Fever'],
            ['id' => '00002', 'name' => 'ไข้ร่วมกับน้ำมูกหรือไอ',                                  'name_en' => 'Fever with Rhinorrhea or Cough'],
            ['id' => '00003', 'name' => 'ไข้ร่วมกับหอบ',                                            'name_en' => 'Fever with Dyspnea'],
            ['id' => '00004', 'name' => 'ไข้ร่วมกับมีผื่นหรือตุ่มขึ้น',                            'name_en' => 'Fever with Rash or Vesicles'],
            ['id' => '00005', 'name' => 'อ่อนเพลีย',                                                'name_en' => 'Fatigue / Weakness'],
            ['id' => '00006', 'name' => 'น้ำหนักลด',                                                'name_en' => 'Weight Loss'],
            ['id' => '00007', 'name' => 'น้ำหนักมากหรืออ้วน',                                      'name_en' => 'Overweight / Obesity'],
            ['id' => '00008', 'name' => 'ซีด / โลหิตจาง',                                          'name_en' => 'Pallor / Anemia'],
            ['id' => '00009', 'name' => 'ซีด / โลหิตจาง ร่วมกับมีไข้',                            'name_en' => 'Anemia with Fever'],
            ['id' => '00010', 'name' => 'จุดแดง-จ้ำเขียว',                                         'name_en' => 'Petechiae / Purpura / Ecchymosis'],
            ['id' => '00011', 'name' => 'ดีซ่าน / ตาเหลือง',                                       'name_en' => 'Jaundice'],
            ['id' => '00012', 'name' => 'ดีซ่านในทารกแรกเกิด',                                     'name_en' => 'Neonatal Jaundice'],
            ['id' => '00013', 'name' => 'บวมทั่วไป',                                                'name_en' => 'Generalized Edema'],
            ['id' => '00014', 'name' => 'บวมเฉพาะที่ / มีก้อน',                                   'name_en' => 'Local Swelling / Mass'],
            ['id' => '00015', 'name' => 'เป็นลม',                                                   'name_en' => 'Syncope / Fainting'],
            ['id' => '00016', 'name' => 'หมดสติ',                                                   'name_en' => 'Loss of Consciousness'],
            ['id' => '00017', 'name' => 'ช็อก',                                                     'name_en' => 'Shock'],
            ['id' => '00018', 'name' => 'ชัก / มือเท้าเกร็ง / ตะคริว',                            'name_en' => 'Seizure / Spasm / Muscle Cramp'],
            ['id' => '00019', 'name' => 'อัมพาต / หนังตาตก',                                      'name_en' => 'Paralysis / Ptosis'],
            ['id' => '00020', 'name' => 'ชา',                                                       'name_en' => 'Numbness / Paresthesia'],
            ['id' => '00021', 'name' => 'ปวดศีรษะ',                                                 'name_en' => 'Headache'],
            ['id' => '00022', 'name' => 'เวียนศีรษะ / บ้านหมุน',                                  'name_en' => 'Dizziness / Vertigo'],
            ['id' => '00023', 'name' => 'ปวดตา / เจ็บตา',                                          'name_en' => 'Eye Pain'],
            ['id' => '00024', 'name' => 'ตามัว / ตาฝ้าฟาง / มองเห็นผิดปกติ หรือภาพผิดปกติ',     'name_en' => 'Blurred Vision / Visual Disturbance'],
            ['id' => '00025', 'name' => 'เคืองตา / คันตา / ตาแฉะ / ตาแดง',                       'name_en' => 'Eye Irritation / Red Eye'],
            ['id' => '00026', 'name' => 'ปวดหู',                                                    'name_en' => 'Ear Pain / Otalgia'],
            ['id' => '00027', 'name' => 'หูอื้อ / มีเสียงในหู',                                   'name_en' => 'Tinnitus / Ear Fullness'],
            ['id' => '00028', 'name' => 'หูตึง / หูหนวก',                                          'name_en' => 'Hearing Loss / Deafness'],
            ['id' => '00029', 'name' => 'หูมีหนองไหล / เลือดออก',                                 'name_en' => 'Ear Discharge / Bleeding'],
            ['id' => '00030', 'name' => 'คัดจมูก / น้ำมูกไหล',                                    'name_en' => 'Nasal Congestion / Rhinorrhea'],
            ['id' => '00031', 'name' => 'เลือดกำเดาไหล',                                           'name_en' => 'Epistaxis / Nosebleed'],
            ['id' => '00032', 'name' => 'โรคฟัน',                                                   'name_en' => 'Dental Disease'],
            ['id' => '00033', 'name' => 'คางบวม / คอบวม',                                          'name_en' => 'Jaw / Neck Swelling'],
            ['id' => '00034', 'name' => 'กลิ่นลำบาก',                                              'name_en' => 'Breathing Difficulty / Dyspnea'],
            ['id' => '00035', 'name' => 'เจ็บคอ',                                                   'name_en' => 'Sore Throat'],
            ['id' => '00036', 'name' => 'ปากเจ็บ / แผลที่ปาก / ลิ้นเป็นฝ้าขาว',                 'name_en' => 'Mouth Sore / Oral Ulcer / White Tongue'],
            ['id' => '00037', 'name' => 'เสียงแหบ',                                                 'name_en' => 'Hoarseness / Dysphonia'],
            ['id' => '00038', 'name' => 'ไอ',                                                       'name_en' => 'Cough'],
            ['id' => '00039', 'name' => 'หอบ / เหนื่อยง่าย',                                      'name_en' => 'Dyspnea / Easy Fatigability'],
            ['id' => '00040', 'name' => 'เจ็บหน้าอก',                                              'name_en' => 'Chest Pain'],
            ['id' => '00041', 'name' => 'ใจสั่น / เหนื่อยออกตามมือเท้า',                         'name_en' => 'Palpitation / Peripheral Cyanosis'],
            ['id' => '00042', 'name' => 'อาเจียน',                                                  'name_en' => 'Vomiting'],
            ['id' => '00043', 'name' => 'ปวดท้อง',                                                  'name_en' => 'Abdominal Pain'],
            ['id' => '00044', 'name' => 'ปวดท้องร่วมกับมีไข้',                                    'name_en' => 'Abdominal Pain with Fever'],
            ['id' => '00045', 'name' => 'ปวดท้องแบบเป็น ๆ หาย ๆ',                                'name_en' => 'Recurrent Abdominal Pain'],
            ['id' => '00046', 'name' => 'ปวดท้องน้อยในผู้หญิงวัยเจริญพันธุ์',                    'name_en' => 'Lower Abdominal Pain in Reproductive Women'],
            ['id' => '00047', 'name' => 'ท้องเดิน',                                                 'name_en' => 'Diarrhea'],
            ['id' => '00048', 'name' => 'ท้องเดินเรื้อรัง',                                        'name_en' => 'Chronic Diarrhea'],
            ['id' => '00049', 'name' => 'บิด',                                                      'name_en' => 'Dysentery'],
            ['id' => '00050', 'name' => 'ถ่ายเป็นเลือด / ถ่ายดำ',                                'name_en' => 'Bloody Stool / Melena'],
            ['id' => '00051', 'name' => 'ท้องผูก',                                                  'name_en' => 'Constipation'],
            ['id' => '00052', 'name' => 'ปวดข้อ / ปวดเอ็น',                                       'name_en' => 'Joint / Tendon Pain'],
            ['id' => '00053', 'name' => 'ปวดหลัง',                                                  'name_en' => 'Back Pain'],
            ['id' => '00054', 'name' => 'ปัสสาวะลำบาก / ปัสสาวะไม่ออก / ปัสสาวะบ่อย',          'name_en' => 'Dysuria / Urinary Retention / Frequency'],
            ['id' => '00055', 'name' => 'ปัสสาวะสีผิดปกติ',                                       'name_en' => 'Abnormal Urine Color'],
            ['id' => '00056', 'name' => 'ประจำเดือนขาด / ไม่มา',                                  'name_en' => 'Amenorrhea / Missed Period'],
            ['id' => '00057', 'name' => 'ตกขาว / คันในช่องคลอด',                                  'name_en' => 'Vaginal Discharge / Pruritus Vulvae'],
            ['id' => '00058', 'name' => 'เลือดออกทางช่องคลอด / ประจำเดือนออกมากกว่าปกติ / ตกเลือดระหว่างตั้งครรภ์', 'name_en' => 'Vaginal Bleeding / Menorrhagia / Antepartum Hemorrhage'],
            ['id' => '00059', 'name' => 'กามโรคในผู้ชาย',                                          'name_en' => 'STD in Male'],
            ['id' => '00060', 'name' => 'กามโรคในผู้หญิง',                                         'name_en' => 'STD in Female'],
            ['id' => '00061', 'name' => 'โรคผิวหนัง',                                              'name_en' => 'Skin Disease'],
            ['id' => '00062', 'name' => 'คัน',                                                      'name_en' => 'Pruritus / Itching'],
            ['id' => '00063', 'name' => 'ผื่น ตุ่ม วงด่าง',                                       'name_en' => 'Rash / Papule / Depigmentation'],
            ['id' => '00064', 'name' => 'ผื่น ตุ่ม วงด่าง ร่วมกับมีอาการคัน',                   'name_en' => 'Rash / Papule / Depigmentation with Itching'],
            ['id' => '00065', 'name' => 'ผมร่วง / ผมบาง',                                         'name_en' => 'Hair Loss / Alopecia'],
            ['id' => '00066', 'name' => 'โรคหนอนพยาธิ',                                            'name_en' => 'Helminthiasis'],
            ['id' => '00067', 'name' => 'แมลงต่อย',                                                'name_en' => 'Insect Sting / Bite'],
            ['id' => '00068', 'name' => 'งูกัด',                                                    'name_en' => 'Snake Bite'],
        ];

        foreach ($diagrams as $d) {
            DB::table('diagrams')->insertOrIgnore([
                'diagram_id'      => $d['id'],
                'diagram_name'    => $d['name'],
                'diagram_name_en' => $d['name_en'],
                'description'     => null,
                'status'          => '1',
                'entry_box_id'    => null, // รอ seed QuestionBox ทีหลัง
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        $this->command->info('✅ DiagramSeeder สำเร็จ');
        $this->command->info('   📊 diagrams : ' . count($diagrams));
    }
}
