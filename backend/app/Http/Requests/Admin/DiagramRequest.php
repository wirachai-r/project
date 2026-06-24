<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DiagramRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'diagram_name'    => 'required|string|max:150',
            'diagram_name_en' => 'nullable|string|max:150',
            'description'     => 'nullable|string',
            'status'          => 'nullable|in:1,2',
            'entry_box_id'    => 'nullable|exists:question_boxes,box_id',
            'symptom_ids'     => 'nullable|array',
            'symptom_ids.*'   => 'exists:main_symptoms,symptom_id',
        ];
    }

    public function messages(): array
    {
        return [
            'diagram_name.required' => 'กรุณากรอกชื่อแผนภูมิ',
            'symptom_ids.*.exists'  => 'ไม่พบอาการที่เลือก',
        ];
    }
}
