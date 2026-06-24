<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SymptomCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name'    => 'required|string|max:150',
            'category_name_en' => 'nullable|string|max:150',
            'description'      => 'nullable|string',
            'icon'             => 'nullable|string|max:255',
            'status'           => 'nullable|in:1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'กรุณากรอกชื่อหมวดหมู่อาการ',
        ];
    }
}
