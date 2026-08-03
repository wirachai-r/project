<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class GoogleLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'กรุณาส่ง Google Token',
        ];
    }
}
