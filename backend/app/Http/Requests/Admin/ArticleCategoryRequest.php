<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesTextInput;
use App\Rules\UniqueNameIgnoringWhitespace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleCategoryRequest extends FormRequest
{
    use NormalizesTextInput;

    protected function prepareForValidation(): void
    {
        $this->normalizeTextInput(['category_name', 'category_name_en']);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => ['sometimes', 'required', 'string', 'max:150', Rule::unique('article_categories', 'category_name')->ignore($this->route('articleCategory')?->getKey(), 'article_category_id'), (new UniqueNameIgnoringWhitespace('article_categories', 'category_name'))->ignore($this->route('articleCategory')?->getKey(), 'article_category_id')],
            'category_name_en' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'status' => 'nullable|in:1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'กรุณากรอกชื่อหมวดหมู่บทความ',
            'category_name.unique' => 'มีชื่อหมวดหมู่บทความนี้อยู่แล้ว',
        ];
    }
}
