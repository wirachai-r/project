<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class QuestionBoxRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'question_text'    => 'required|string|max:500',
            'question_text_en' => 'nullable|string|max:500',
            'question_image'   => 'nullable|string|max:255',
            'question_type'    => 'nullable|in:S,M',
            'status'           => 'nullable|in:1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'question_text.required' => 'กรุณากรอกข้อความคำถาม',
            'question_type.in'       => 'ประเภทคำถามต้องเป็น S (เดี่ยว) หรือ M (หลาย)',
        ];
    }
}
