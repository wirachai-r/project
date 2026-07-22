<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'               => 'sometimes|required|string|max:255',
            'title_en'            => 'nullable|string|max:255',
            'content'             => 'sometimes|required|string',
            'content_en'          => 'nullable|string',
            'thumbnail'           => 'nullable|string|max:255',
            'status'              => 'nullable|in:1,2,3',
            'article_category_id' => 'sometimes|required|exists:article_categories,article_category_id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'               => 'กรุณากรอกชื่อบทความ',
            'content.required'             => 'กรุณากรอกเนื้อหา',
            'article_category_id.required' => 'กรุณาเลือกหมวดหมู่',
            'article_category_id.exists'   => 'ไม่พบหมวดหมู่ที่เลือก',
        ];
    }
}
