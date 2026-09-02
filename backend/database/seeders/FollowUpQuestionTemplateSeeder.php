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
                'question_text' => 'เมื่อเทียบกับครั้งล่าสุด อาการนี้เป็นอย่างไร?',
                'description' => 'เลือกคำตอบที่ใกล้เคียงกับอาการที่คุณสังเกตได้ในตอนนี้',
                'answer_type' => 'single_choice',
                'options' => ['ดีขึ้นมาก', 'ดีขึ้นเล็กน้อย', 'ใกล้เคียงเดิม', 'แย่ลงเล็กน้อย', 'แย่ลงมาก'],
                'unit' => null,
                'is_required' => true,
            ],
            [
                'question_text' => 'อาการนี้กระทบกิจวัตรประจำวันมากน้อยเพียงใด?',
                'description' => 'พิจารณาจากการทำงาน การเรียน การเคลื่อนไหว การรับประทานอาหาร และการนอน',
                'answer_type' => 'single_choice',
                'options' => ['ไม่กระทบ', 'กระทบเล็กน้อย', 'กระทบปานกลาง', 'กระทบมาก'],
                'unit' => null,
                'is_required' => true,
            ],
            [
                'question_text' => 'มีอาการอื่นเพิ่มขึ้นจากครั้งล่าสุดหรือไม่?',
                'description' => 'ตอบตามสิ่งที่คุณสังเกตได้ โดยยังไม่ต้องระบุรายละเอียด',
                'answer_type' => 'boolean',
                'options' => ['มี', 'ไม่มี'],
                'unit' => null,
                'is_required' => false,
            ],
            [
                'question_text' => 'มีอะไรเปลี่ยนแปลงหรืออยากบันทึกเพิ่มเติมหรือไม่?',
                'description' => 'เช่น อาการอื่นที่เพิ่มขึ้น สิ่งที่ทำก่อนอาการเปลี่ยน หรือสิ่งที่ช่วยให้รู้สึกดีขึ้น',
                'answer_type' => 'text',
                'options' => null,
                'unit' => null,
                'is_required' => false,
            ],
            [
                'question_text' => 'ตั้งแต่ครั้งล่าสุด คุณได้ดูแลตัวเองด้วยวิธีใดบ้าง?',
                'description' => 'เลือกได้มากกว่าหนึ่งข้อ หากไม่ได้ทำอะไรเพิ่มให้เว้นข้อนี้ไว้',
                'answer_type' => 'multiple_choice',
                'options' => ['พักผ่อน', 'ดื่มน้ำ', 'รับประทานยาตามที่ได้รับคำแนะนำ', 'ดูแลตนเองด้วยวิธีอื่น'],
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
