<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DiagramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'diagram_name'    => 'required|string|max:150',
            'diagram_name_en' => 'nullable|string|max:150',
            'description'     => 'nullable|string',
            'status'          => 'nullable|in:1,2',
            'symptom_id'      => 'required|exists:main_symptoms,symptom_id',
            'entry_box_id'    => 'nullable|exists:question_boxes,box_id',
        ];
    }

    public function messages(): array
    {
        return [
            'diagram_name.required' => 'กรุณากรอกชื่อ diagram',
            'symptom_id.required'  => 'กรุณาเลือกอาการ',
            'symptom_id.exists'    => 'ไม่พบอาการที่เลือก',
            'entry_box_id.exists'  => 'ไม่พบกล่องคำถามที่เลือก',
        ];
    }
}
