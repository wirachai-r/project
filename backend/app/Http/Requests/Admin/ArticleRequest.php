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
            'title'               => 'required|string|max:255',
            'title_en'            => 'nullable|string|max:255',
            'content'             => 'required|string',
            'content_en'          => 'nullable|string',
            'thumbnail'           => 'nullable|string|max:255',
            'status'              => 'nullable|in:1,2', // 1=Published, 2=Draft, 3=Archived
            'article_category_id' => 'required|exists:article_categories,article_category_id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'               => 'กรุณากรอกชื่อบทความ',
            'content.required'             => 'กรุณากรอกเนื้อหาบทความ',
            'article_category_id.required' => 'กรุณาเลือกหมวดหมู่บทความ',
            'article_category_id.exists'   => 'ไม่พบหมวดหมู่บทความที่เลือก',
        ];
    }
}
