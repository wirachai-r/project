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
            'disease_name'         => 'sometimes|required|string|max:150',
            'disease_name_en'      => 'nullable|string|max:150',
            'description'          => 'nullable|string',
            'cause'                => 'nullable|string',
            'symptom_description'  => 'nullable|string',
            'complications'        => 'nullable|string',
            'diagnosis'            => 'nullable|string',
            'medical_treatment'    => 'nullable|string',
            'self_care'            => 'nullable|string',
            'when_to_see_doctor'   => 'nullable|string',
            'prevention'           => 'nullable|string',
            'recommendations'      => 'nullable|string',
            'disease_image'        => 'nullable|string|max:255',
            'status'               => 'nullable|in:1,2',
            'disease_category_id'  => 'sometimes|required|exists:disease_categories,disease_category_id',
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
