<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyHealthRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recorded_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'recorded_at' => ['nullable', 'date', 'before_or_equal:now'],
            'status' => ['required', 'in:well,normal,unwell'],
            'note' => ['nullable', 'string'],
            'symptom_ids' => ['nullable', 'array'],
            'symptom_ids.*' => ['string', 'distinct', 'exists:main_symptoms,symptom_id'],
            'health_episode_ids' => ['sometimes', 'array'],
            'health_episode_ids.*' => ['integer', 'distinct', 'exists:health_episodes,id'],
        ];
    }
}
