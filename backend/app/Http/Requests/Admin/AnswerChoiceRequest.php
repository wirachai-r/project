<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AnswerChoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            'choice_text'    => 'required|string|max:255',
            'choice_text_en' => 'nullable|string|max:255',
            'order'          => 'nullable|integer',
            'status'         => 'nullable|in:1,2',
            'next_box_id'    => 'nullable|exists:question_boxes,box_id',
        ];
    }

    public function messages(): array
    {
        return [
            'choice_text.required' => 'กรุณากรอกข้อความตัวเลือก',
            'order.integer'       => 'ลำดับต้องเป็นตัวเลข',
            'status.in'          => 'สถานะไม่ถูกต้อง',
            'next_box_id.exists' => 'ไม่พบกล่องคำถามที่เลือก',
        ];
    }
}
