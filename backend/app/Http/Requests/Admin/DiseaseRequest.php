<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesTextInput;
use App\Rules\UniqueNameIgnoringWhitespace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DiseaseRequest extends FormRequest
{
    use NormalizesTextInput;

    protected function prepareForValidation(): void
    {
        $this->normalizeTextInput(['disease_name', 'disease_name_en']);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disease_name' => ['sometimes', 'required', 'string', 'max:150', Rule::unique('diseases', 'disease_name')->ignore($this->route('disease')?->getKey(), 'disease_id'), (new UniqueNameIgnoringWhitespace('diseases', 'disease_name'))->ignore($this->route('disease')?->getKey(), 'disease_id')],
            'disease_name_en' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'cause' => 'nullable|string',
            'symptom_description' => 'nullable|string',
            'complications' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'medical_treatment' => 'nullable|string',
            'self_care' => 'nullable|string',
            'when_to_see_doctor' => 'nullable|string',
            'prevention' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'disease_image' => 'nullable|string|max:255',
            'status' => 'nullable|in:1,2',
            'minimum_supporting_symptoms' => 'sometimes|required|integer|min:1|max:65535',
            'disease_category_id' => 'sometimes|required|exists:disease_categories,disease_category_id',
            'references' => 'nullable|array',
            'references.*' => 'required|string|max:2048|distinct',
            'symptom_ids' => 'nullable|array',
            'symptom_ids.*' => 'required|string|distinct|exists:main_symptoms,symptom_id',
            'symptom_assessments' => 'nullable|array',
            'symptom_assessments.*.symptom_id' => 'required|string|distinct|exists:main_symptoms,symptom_id',
            'symptom_assessments.*.assessment_weight' => 'nullable|numeric|min:0|max:100',
            'symptom_assessments.*.is_key_symptom' => 'nullable|boolean',
            'symptom_assessments.*.absence_penalty' => 'nullable|numeric|min:0|max:100',
            'symptom_assessments.*.question_text' => 'nullable|string|max:500',
            'symptom_assessments.*.evidence_source' => 'nullable|string|max:5000',
            'symptom_assessments.*.evidence_status' => 'nullable|in:unreviewed,source_linked,verified,rejected',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->hasAny(['minimum_supporting_symptoms', 'symptom_ids'])) {
                return;
            }

            $disease = $this->route('disease');
            $minimum = (int) $this->input(
                'minimum_supporting_symptoms',
                $disease?->minimum_supporting_symptoms ?? 1,
            );
            $symptomCount = $this->has('symptom_ids')
                ? collect($this->input('symptom_ids', []))->unique()->count()
                : ($disease?->symptoms()->count() ?? 0);

            if ($symptomCount > 0 && $minimum > $symptomCount) {
                $validator->errors()->add(
                    'minimum_supporting_symptoms',
                    'จำนวนอาการสนับสนุนขั้นต่ำต้องไม่เกินจำนวนอาการที่เลือกให้ภาวะนี้',
                );
            }
        }];
    }

    public function messages(): array
    {
        return [
            'disease_name.required' => 'กรุณากรอกชื่อโรค',
            'disease_name.unique' => 'มีชื่อโรคนี้อยู่แล้ว',
            'disease_category_id.required' => 'กรุณาเลือกหมวดหมู่',
            'disease_category_id.exists' => 'ไม่พบหมวดหมู่ที่เลือก',
        ];
    }
}
