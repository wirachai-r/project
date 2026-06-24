<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HealthcareFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_name' => 'required|string|max:200',
            'facility_type' => 'required|string|max:50',
            'address'       => 'nullable|string',
            'province'      => 'nullable|string|max:100',
            'district'      => 'nullable|string|max:100',
            'sub_district'  => 'nullable|string|max:100',
            'postal_code'   => 'nullable|string|max:10',
            'phone'         => 'nullable|string|max:20',
            'latitude'      => 'nullable|numeric|between:-90,90',
            'longitude'     => 'nullable|numeric|between:-180,180',
            'status'        => 'nullable|in:1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'facility_name.required' => 'กรุณากรอกชื่อสถานพยาบาล',
            'facility_type.required' => 'กรุณาเลือกประเภทสถานพยาบาล',
            'latitude.between'       => 'ละติจูดต้องอยู่ระหว่าง -90 ถึง 90',
            'longitude.between'      => 'ลองจิจูดต้องอยู่ระหว่าง -180 ถึง 180',
        ];
    }
}
