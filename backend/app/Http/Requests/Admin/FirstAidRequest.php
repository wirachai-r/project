<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FirstAidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                 => 'required|string|max:255',
            'title_en'              => 'nullable|string|max:255',
            'content'               => 'required|string',
            'content_en'            => 'nullable|string',
            'cover_image'           => 'nullable|string|max:255',
            'status'                => 'nullable|in:1,0',
            'first_aid_category_id' => 'required|exists:first_aid_categories,first_aid_category_id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'                 => 'กรุณากรอกชื่อหัวข้อปฐมพยาบาล',
            'content.required'               => 'กรุณากรอกเนื้อหาปฐมพยาบาล',
            'first_aid_category_id.required' => 'กรุณาเลือกหมวดหมู่ปฐมพยาบาล',
            'first_aid_category_id.exists'   => 'ไม่พบหมวดหมู่ปฐมพยาบาลที่เลือก',
        ];
    }
}
