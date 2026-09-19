<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesTextInput;
use App\Rules\UniqueNameIgnoringWhitespace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleRequest extends FormRequest
{
    use NormalizesTextInput;

    protected function prepareForValidation(): void
    {
        $this->normalizeTextInput(['title', 'title_en']);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('articles', 'title')
                    ->where('article_category_id', $this->input('article_category_id', $this->route('article')?->article_category_id))
                    ->ignore($this->route('article')?->getKey(), 'article_id'),
                (new UniqueNameIgnoringWhitespace('articles', 'title'))
                    ->where(fn ($query) => $query->where('article_category_id', $this->input('article_category_id', $this->route('article')?->article_category_id)))
                    ->ignore($this->route('article')?->getKey(), 'article_id'),
            ],
            'title_en' => 'nullable|string|max:255',
            'content' => 'sometimes|required|string',
            'content_en' => 'nullable|string',
            'thumbnail' => 'nullable|string|max:255',
            'status' => 'nullable|in:1,2,3',
            'article_category_id' => 'sometimes|required|exists:article_categories,article_category_id',
            'references' => 'nullable|array',
            'references.*' => 'required|string|max:2048|distinct',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'กรุณากรอกชื่อบทความ',
            'title.unique' => 'มีชื่อบทความนี้อยู่ในหมวดหมู่แล้ว',
            'content.required' => 'กรุณากรอกเนื้อหา',
            'article_category_id.required' => 'กรุณาเลือกหมวดหมู่',
            'article_category_id.exists' => 'ไม่พบหมวดหมู่ที่เลือก',
        ];
    }
}
