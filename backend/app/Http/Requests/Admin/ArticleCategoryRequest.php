<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ArticleCategoryRequest extends FormRequest
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
            'status'           => 'nullable|in:1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'กรุณากรอกชื่อหมวดหมู่บทความ',
        ];
    }
}
