<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class QuestionBoxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $questionTextRule = $this->isMethod('post')
            ? ['required', 'string', 'max:500']
            : ['sometimes', 'required', 'string', 'max:500'];

        return [
            'frame_number'       => 'nullable|string|max:20',
            'question_text'       => $questionTextRule,
            'question_text_en'    => 'nullable|string|max:500',
            'question_image'      => 'nullable|string|max:255',
            'question_type'       => 'nullable|in:S,M',
            'answer_mode'         => 'nullable|in:binary,multiple,checklist',
            'sync_result_bindings' => 'sometimes|boolean',
            'status'              => 'nullable|in:1,2',

            // ใช้เฉพาะ question_type = M
            'min_required'        => 'nullable|integer|min:1|required_if:question_type,M',
            'yes_next_box_id'     => 'nullable|string|exists:question_boxes,box_id',
            'yes_next_diagram_id' => 'nullable|string|exists:diagrams,diagram_id',
            'no_next_box_id'      => 'nullable|string|exists:question_boxes,box_id',
            'no_next_diagram_id'  => 'nullable|string|exists:diagrams,diagram_id',

            // ➕ เพิ่มกฎการตรวจสอบสำหรับรายละเอียด (detail)
            // เนื่องจากเราตั้งเป็น text ในฐานข้อมูล และกำหนด nullable ไว้ จึงใช้กฎแบบนี้ครับ
            'detail'              => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'question_text.required'    => 'กรุณากรอกข้อความคำถาม',
            'min_required.required_if'  => 'กรุณาระบุจำนวนข้อขั้นต่ำสำหรับกล่องคำถามแบบติ๊กหลายข้อ',
            'min_required.min'          => 'จำนวนข้อขั้นต่ำต้องมากกว่า 0',
            'yes_next_box_id.exists'    => 'ไม่พบกล่องคำถามปลายทาง (ใช่) ที่เลือก',
            'no_next_box_id.exists'     => 'ไม่พบกล่องคำถามปลายทาง (ไม่ใช่) ที่เลือก',

            // ➕ เพิ่มข้อความแจ้งเตือนสำหรับ detail (ถ้าพิมพ์ข้อมูลไม่ตรงตามกฎ string)
            'detail.string'             => 'รูปแบบข้อมูลรายละเอียดไม่ถูกต้อง',
        ];
    }
}
