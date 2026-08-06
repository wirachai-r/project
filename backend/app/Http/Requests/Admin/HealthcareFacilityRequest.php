<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesTextInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HealthcareFacilityRequest extends FormRequest
{
    use NormalizesTextInput;

    protected function prepareForValidation(): void
    {
        $this->normalizeTextInput([
            'facility_name', 'facility_name_en', 'province', 'district',
            'sub_district', 'phone',
        ]);
    }
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'facility_name' => [
                'required', 'string', 'max:200',
                Rule::unique('healthcare_facilities', 'facility_name')
                    ->where(function ($query) {
                        $province = $this->input('province');
                        $district = $this->input('district');
                        $province === null ? $query->whereNull('province') : $query->where('province', $province);
                        $district === null ? $query->whereNull('district') : $query->where('district', $district);
                    })
                    ->ignore($this->route('healthcareFacility')?->getKey(), 'facility_id'),
            ],
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
            'facility_name.unique'   => 'มีสถานพยาบาลชื่อนี้อยู่ในจังหวัดและอำเภอเดียวกันแล้ว',
            'facility_type.required' => 'กรุณาเลือกประเภทสถานพยาบาล',
            'latitude.between'       => 'ละติจูดต้องอยู่ระหว่าง -90 ถึง 90',
            'longitude.between'      => 'ลองจิจูดต้องอยู่ระหว่าง -180 ถึง 180',
        ];
    }
}
