<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SymptomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'symptom_name'        => 'required|string|max:150',
            'symptom_name_en'     => 'nullable|string|max:150',
            'description'         => 'nullable|string',
            'symptom_image'       => 'nullable|string|max:255',
            'status'              => 'nullable|in:1,2',
            'symptom_category_id' => 'required|exists:symptom_categories,symptom_category_id',
        ];
    }

    public function messages(): array
    {
        return [
            'symptom_name.required'        => 'กรุณากรอกชื่ออาการ',
            'symptom_category_id.required' => 'กรุณาเลือกหมวดหมู่',
            'symptom_category_id.exists'   => 'ไม่พบหมวดหมู่ที่เลือก',
        ];
    }
}
