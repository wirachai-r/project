<?php

namespace Database\Seeders;

use App\Models\FollowUpQuestionTemplate;
use Illuminate\Database\Seeder;

class FollowUpQuestionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'question_text' => 'เมื่อเทียบกับครั้งก่อน อาการเป็นอย่างไร?',
                'description' => 'เลือกแนวโน้มที่ตรงกับอาการในขณะนี้มากที่สุด',
                'answer_type' => 'single_choice',
                'options' => ['ดีขึ้น', 'เท่าเดิม', 'แย่ลง'],
                'unit' => null,
                'is_required' => true,
            ],
            [
                'question_text' => 'อาการรบกวนการทำกิจวัตรประจำวันหรือไม่',
                'description' => 'พิจารณาการทำงาน การเรียน การเดิน การรับประทานอาหาร และการนอน',
                'answer_type' => 'single_choice',
                'options' => ['ไม่รบกวน', 'เล็กน้อย', 'มาก', 'ทำกิจวัตรไม่ได้'],
                'unit' => null,
                'is_required' => true,
            ],
            [
                'question_text' => 'วันนี้มีอาการนี้ต่อเนื่องประมาณกี่ชั่วโมง',
                'description' => 'กรอกจำนวนโดยประมาณ ตั้งแต่ 0 ถึง 24 ชั่วโมง',
                'answer_type' => 'number',
                'options' => null,
                'unit' => 'ชั่วโมง',
                'is_required' => false,
            ],
            [
                'question_text' => 'มีอาการใหม่เกิดขึ้นหรือไม่?',
                'description' => 'เลือกว่ามีอาการอื่นเพิ่มขึ้นจากครั้งก่อนหรือไม่',
                'answer_type' => 'boolean',
                'options' => ['มี', 'ไม่มี'],
                'unit' => null,
                'is_required' => true,
            ],
            [
                'question_text' => 'หากมีอาการใหม่ โปรดระบุ',
                'description' => 'เว้นว่างได้หากไม่มีอาการใหม่',
                'answer_type' => 'text',
                'options' => null,
                'unit' => null,
                'is_required' => false,
            ],
            [
                'question_text' => 'ได้ใช้ยาหรือวิธีดูแลอาการตั้งแต่ครั้งก่อนหรือไม่',
                'description' => 'รวมถึงยาที่ได้รับคำแนะนำและการดูแลตนเอง',
                'answer_type' => 'boolean',
                'options' => ['ใช้', 'ไม่ได้ใช้'],
                'unit' => null,
                'is_required' => false,
            ],
            [
                'question_text' => 'หลังดูแลหรือใช้ยาแล้วอาการเป็นอย่างไร',
                'description' => 'เว้นว่างได้หากไม่ได้ใช้ยาหรือวิธีดูแลอาการ',
                'answer_type' => 'single_choice',
                'options' => ['ดีขึ้น', 'ไม่เปลี่ยนแปลง', 'แย่ลง'],
                'unit' => null,
                'is_required' => false,
            ],
            [
                'question_text' => 'วันนี้รับประทานอาหารและดื่มน้ำได้ตามปกติหรือไม่',
                'description' => null,
                'answer_type' => 'single_choice',
                'options' => ['ปกติ', 'ลดลง', 'แทบไม่ได้'],
                'unit' => null,
                'is_required' => false,
            ],
            [
                'question_text' => 'มีข้อมูลอื่นที่ต้องการบันทึกหรือไม่',
                'description' => 'บันทึกสิ่งที่สังเกตได้เพิ่มเติม โดยไม่ต้องระบุข้อมูลส่วนตัวที่ไม่จำเป็น',
                'answer_type' => 'text',
                'options' => null,
                'unit' => null,
                'is_required' => false,
            ],
        ];

        foreach ($templates as $template) {
            FollowUpQuestionTemplate::query()->updateOrCreate(
                ['question_text' => $template['question_text']],
                $template + [
                    'applies_to_all_symptoms' => true,
                    'status' => '1',
                ],
            );
        }
    }
}
