<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesTextInput;
use App\Models\BodyAreaGroup;
use App\Rules\UniqueNameIgnoringWhitespace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BodyAreaGroupRequest extends FormRequest
{
    use NormalizesTextInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeTextInput(['name', 'name_en']);

        $input = $this->all();

        if (isset($input['symptom_ids']) && is_array($input['symptom_ids'])) {
            $input['symptom_ids'] = $this->uniqueIds($input['symptom_ids']);
        }

        if (isset($input['subgroups']) && is_array($input['subgroups'])) {
            $input['subgroups'] = array_map(function ($subgroup) {
                if (is_array($subgroup) && isset($subgroup['symptom_ids']) && is_array($subgroup['symptom_ids'])) {
                    $subgroup['symptom_ids'] = $this->uniqueIds($subgroup['symptom_ids']);
                }

                return $subgroup;
            }, $input['subgroups']);
        }

        $this->replace($input);
    }

    private function uniqueIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => trim((string) $id))
            ->filter(fn (string $id) => $id !== '')
            ->uniqueStrict()
            ->values()
            ->all();
    }

    public function rules(): array
    {
        $routeGroup = $this->route('body_area_group') ?? $this->route('bodyAreaGroup');
        $id = $routeGroup instanceof BodyAreaGroup
            ? $routeGroup->getKey()
            : (is_numeric($routeGroup) ? (int) $routeGroup : null);

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('body_area_groups')->ignore($id), (new UniqueNameIgnoringWhitespace('body_area_groups', 'name'))->ignore($id)],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            // The original upload is capped at 5 MB in the client. Cropping can
            // temporarily produce a larger encoded file, so allow a bounded
            // 20 MB processed image here.
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:20480'],
            'remove_image' => ['sometimes', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['1', '2'])],
            'symptom_ids' => ['sometimes', 'array'],
            'symptom_ids.*' => ['string', 'distinct', 'exists:main_symptoms,symptom_id'],
            'subgroups' => ['sometimes', 'array'],
            'subgroups.*.id' => ['nullable', 'integer'],
            'subgroups.*.name' => ['required', 'string', 'max:100'],
            'subgroups.*.name_en' => ['nullable', 'string', 'max:100'],
            'subgroups.*.description' => ['nullable', 'string', 'max:255'],
            'subgroups.*.image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:20480'],
            'subgroups.*.remove_image' => ['sometimes', 'boolean'],
            'subgroups.*.status' => ['nullable', Rule::in(['1', '2'])],
            'subgroups.*.symptom_ids' => ['sometimes', 'array'],
            // `distinct` on this nested wildcard compares symptom IDs across all
            // subgroups. Reusing the same symptom in different subgroups is valid;
            // prepareForValidation() already removes duplicates within each one.
            'subgroups.*.symptom_ids.*' => ['string', 'exists:main_symptoms,symptom_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'ชื่อกลุ่มบริเวณนี้มีอยู่แล้ว',
            'image.image' => 'ไฟล์ที่เลือกต้องเป็นรูปภาพ',
            'image.mimes' => 'รองรับเฉพาะรูป PNG, JPG และ WEBP',
            'image.max' => 'รูปภาพหลังประมวลผลต้องมีขนาดไม่เกิน 20 MB',
            'symptom_ids.*.exists' => 'มีอาการที่เลือกบางรายการไม่อยู่ในระบบ กรุณาโหลดหน้าใหม่',
        ];
    }
}
