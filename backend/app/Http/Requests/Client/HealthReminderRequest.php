<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HealthReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'reminder_type' => ['required', Rule::in(['daily_record', 'follow_up', 'reassessment', 'custom'])],
            'health_episode_id' => [
                'nullable',
                'required_if:reminder_type,follow_up',
                Rule::exists('health_episodes', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()->user_id)->where('status', 'A')
                ),
            ],
            'frequency' => ['required', Rule::in(['daily', 'weekly'])],
            'time_of_day' => ['required', 'date_format:H:i'],
            'days_of_week' => ['nullable', 'required_if:frequency,weekly', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            'timezone' => ['required', 'timezone'],
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
