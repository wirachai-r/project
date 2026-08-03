<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AnswerChoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $choiceTextRule = $this->isMethod('post')
            ? ['required', 'string', 'max:255']
            : ['sometimes', 'required', 'string', 'max:255'];

        return [
            'choice_text'     => $choiceTextRule,
            'choice_text_en'  => 'nullable|string|max:255',
            'choice_image'    => 'nullable|string|max:255',
            'order'           => 'nullable|integer|min:0',
            'status'          => 'nullable|in:1,2',
            'next_box_id'     => 'nullable|exists:question_boxes,box_id',
            'next_diagram_id' => 'nullable|exists:diagrams,diagram_id',
        ];
    }

    public function messages(): array
    {
        return [
            'choice_text.required'    => 'กรุณากรอกข้อความตัวเลือก',
            'next_box_id.exists'      => 'ไม่พบกรอบคำถามที่เลือก',
            'next_diagram_id.exists'  => 'ไม่พบแผนภูมิที่เลือก',
        ];
    }
}
