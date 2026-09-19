<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesTextInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FirstAidRequest extends FormRequest
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
        // apiResource uses the snake_case route parameter `{first_aid}`.
        // Reading `firstAid` here returns null, so an update would fail the
        // unique check against the record being updated.
        $firstAid = $this->route('first_aid');

        return [
            'title' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('first_aids', 'title')
                    ->ignore($firstAid?->getKey(), 'first_aid_id'),
            ],
            'title_en' => 'nullable|string|max:255',
            'content' => 'sometimes|required|string',
            'content_en' => 'nullable|string',
            'thumbnail' => 'nullable|string|max:255',
            'status' => 'nullable|in:1,2,3',
            'first_aid_category_id' => 'sometimes|required|exists:first_aid_categories,first_aid_category_id',
            'references' => 'nullable|array',
            'references.*' => 'required|string|max:2048|distinct',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'กรุณากรอกชื่อเรื่อง',
            'title.unique' => 'มีชื่อเรื่องปฐมพยาบาลนี้อยู่แล้ว',
            'content.required' => 'กรุณากรอกเนื้อหา',
            'first_aid_category_id.required' => 'กรุณาเลือกหมวดหมู่',
            'first_aid_category_id.exists' => 'ไม่พบหมวดหมู่ที่เลือก',
        ];
    }
}
