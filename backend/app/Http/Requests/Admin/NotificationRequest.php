<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'   => 'required|string|max:255',
            'body'    => 'required|string',
            'type'    => 'nullable|in:S,W,E,I',
            'user_id' => 'required|exists:users,user_id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'   => 'กรุณากรอกหัวข้อการแจ้งเตือน',
            'body.required'    => 'กรุณากรอกเนื้อหา',
            'user_id.required' => 'กรุณาเลือกผู้รับการแจ้งเตือน',
            'user_id.exists'   => 'ไม่พบผู้ใช้ที่เลือก',
        ];
    }
}
