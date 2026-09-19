<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesTextInput;
use App\Rules\UniqueNameIgnoringWhitespace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HealthcareFacilityRequest extends FormRequest
{
    use NormalizesTextInput;

    protected function prepareForValidation(): void
    {
        $this->normalizeTextInput([
            'facility_name', 'facility_name_en', 'province', 'district',
            'sub_district', 'phone', 'moph_code_9_new', 'moph_code_9',
            'moph_code_5', 'license_code_11', 'organization_type',
            'service_type', 'affiliation', 'department', 'hospital_level',
            'network_type', 'source_status', 'service_area', 'province_code',
            'district_code', 'sub_district_code', 'village', 'parent_facility',
            'data_source',
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
                (new UniqueNameIgnoringWhitespace('healthcare_facilities', 'facility_name'))
                    ->where(function ($query) {
                        $province = $this->input('province');
                        $district = $this->input('district');
                        $province === null ? $query->whereNull('province') : $query->where('province', $province);
                        $district === null ? $query->whereNull('district') : $query->where('district', $district);
                    })
                    ->ignore($this->route('healthcareFacility')?->getKey(), 'facility_id'),
            ],
            'facility_type' => 'required|string|max:50',
            'moph_code_9_new' => [
                'nullable', 'string', 'max:9',
                Rule::unique('healthcare_facilities', 'moph_code_9_new')
                    ->ignore($this->route('healthcareFacility')?->getKey(), 'facility_id'),
            ],
            'moph_code_9' => 'nullable|string|max:9',
            'moph_code_5' => 'nullable|string|max:5',
            'license_code_11' => 'nullable|string|max:11',
            'organization_type' => 'nullable|string|max:150',
            'service_type' => 'nullable|string|max:150',
            'affiliation' => 'nullable|string|max:150',
            'department' => 'nullable|string|max:150',
            'hospital_level' => 'nullable|string|max:100',
            'network_type' => 'nullable|string|max:100',
            'actual_beds' => 'nullable|integer|min:0',
            'source_status' => 'nullable|string|max:100',
            'service_area' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'province_code' => 'nullable|string|max:2',
            'province' => 'nullable|string|max:100',
            'district_code' => 'nullable|string|max:4',
            'district' => 'nullable|string|max:100',
            'sub_district_code' => 'nullable|string|max:6',
            'sub_district' => 'nullable|string|max:100',
            'village' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:10',
            'parent_facility' => 'nullable|string|max:255',
            'established_date' => 'nullable|date_format:Y-m-d',
            'closed_date' => 'nullable|date_format:Y-m-d|after_or_equal:established_date',
            'source_updated_at' => 'nullable|date',
            'data_source' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'open_hours' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'nullable|in:1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'facility_name.required' => 'กรุณากรอกชื่อสถานพยาบาล',
            'facility_name.unique' => 'มีสถานพยาบาลชื่อนี้อยู่ในจังหวัดและอำเภอเดียวกันแล้ว',
            'facility_type.required' => 'กรุณาเลือกประเภทสถานพยาบาล',
            'latitude.between' => 'ละติจูดต้องอยู่ระหว่าง -90 ถึง 90',
            'longitude.between' => 'ลองจิจูดต้องอยู่ระหว่าง -180 ถึง 180',
        ];
    }
}
