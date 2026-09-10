<?php

namespace App\Http\Requests\Client;

use App\Support\HealthTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDailyHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recorded_on' => ['required', 'date_format:Y-m-d'],
            'recorded_at' => ['nullable', 'date', 'before_or_equal:now'],
            'status' => ['required', 'in:well,normal,unwell'],
            'note' => ['nullable', 'string'],
            'symptom_ids' => ['nullable', 'array'],
            'symptom_ids.*' => ['string', 'distinct', 'exists:main_symptoms,symptom_id'],
            'health_episode_ids' => ['sometimes', 'array'],
            'health_episode_ids.*' => ['integer', 'distinct', 'exists:health_episodes,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('recorded_on') || ! $this->filled('recorded_on')) {
                return;
            }

            if (HealthTime::localDate($this->string('recorded_on')->toString())->isAfter(HealthTime::today())) {
                $validator->errors()->add('recorded_on', 'วันที่บันทึกต้องไม่เกินวันนี้');
            }
        }];
    }
}
