<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TreatmentOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_name'     => 'required|string|max:150',
            'order_name_en'  => 'nullable|string|max:150',
            'description'    => 'nullable|string',
            'urgency_type'   => 'required|in:R,P,Y,G,W',
            'order_sequence' => 'nullable|integer|min:0',
            'status'         => 'nullable|in:1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'order_name.required'   => 'กรุณากรอกชื่อคำสั่งการรักษา',
            'urgency_type.required' => 'กรุณาเลือกระดับความเร่งด่วน',
            'urgency_type.in'       => 'ระดับความเร่งด่วนต้องเป็น R, P, Y, G หรือ W',
        ];
    }
}
