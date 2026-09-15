<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feedback_type' => ['required', Rule::in(['general', 'content_error', 'assessment'])],
            'target_type' => ['nullable', 'required_unless:feedback_type,general', Rule::in(['article', 'disease', 'symptom', 'first_aid', 'assessment'])],
            'target_id' => ['nullable', 'required_unless:feedback_type,general', 'string', 'max:50'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'category' => ['nullable', 'required_if:feedback_type,content_error', Rule::in(['inaccurate', 'outdated', 'unclear', 'unsafe', 'suggestion', 'bug', 'content_error', 'other'])],
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['sometimes', 'array', 'max:3'],
            'attachments.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
