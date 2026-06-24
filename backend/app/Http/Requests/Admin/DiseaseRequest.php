<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DiseaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disease_name'        => 'required|string|max:150',
            'disease_name_en'     => 'nullable|string|max:150',
            'description'         => 'nullable|string',
            'disease_image'       => 'nullable|string|max:255',
            'status'              => 'nullable|in:1,2',
            'disease_category_id' => 'required|exists:disease_categories,disease_category_id',
        ];
    }

    public function messages(): array
    {
        return [
            'disease_name.required'        => 'กรุณากรอกชื่อโรค',
            'disease_category_id.required' => 'กรุณาเลือกหมวดหมู่',
            'disease_category_id.exists'   => 'ไม่พบหมวดหมู่ที่เลือก',
        ];
    }
}
