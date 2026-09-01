<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255', 'body' => 'required|string', 'type' => 'nullable|in:S,U,W,E,I',
            'audience' => 'required|in:individual,group,all', 'user_id' => 'nullable|exists:users,user_id',
            'user_ids' => 'nullable|array|min:1', 'user_ids.*' => 'exists:users,user_id', 'audience_filter' => 'nullable|array',
            'audience_filter.role' => 'nullable|in:User,Admin', 'audience_filter.status' => 'nullable|in:1,2',
            'channels' => 'required|array|min:1', 'channels.*' => 'in:in_app,push', 'target_url' => 'nullable|string|max:2048',
            'scheduled_at' => 'nullable|date|after:now', 'is_persistent' => 'boolean', 'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['audience' => $this->input('audience', 'individual'), 'channels' => $this->input('channels', ['in_app'])]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('audience') === 'individual' && ! $this->filled('user_id') && empty($this->input('user_ids'))) {
                $validator->errors()->add('user_id', 'กรุณาเลือกผู้รับ');
            }
            if ($this->filled('expires_at')) {
                $expiresAt = strtotime((string) $this->input('expires_at'));
                $reference = $this->input('scheduled_at') ?: $this->input('starts_at');
                if ($reference && $expiresAt <= strtotime((string) $reference)) {
                    $validator->errors()->add('expires_at', 'วันหมดอายุต้องอยู่หลังวันและเวลาส่งหรือเวลาเริ่มแสดง');
                }
            }
        });
    }
}
