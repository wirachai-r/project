<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class HealthcareFacilityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'facility_id' => $this->facility_id,
            'facility_name' => $this->facility_name,
            'facility_name_en' => $this->facility_name_en,
            'facility_type' => $this->facility_type,
            'moph_code_9_new' => $this->moph_code_9_new,
            'moph_code_9' => $this->moph_code_9,
            'moph_code_5' => $this->moph_code_5,
            'license_code_11' => $this->license_code_11,
            'organization_type' => $this->organization_type,
            'service_type' => $this->service_type,
            'affiliation' => $this->affiliation,
            'department' => $this->department,
            'hospital_level' => $this->hospital_level,
            'network_type' => $this->network_type,
            'actual_beds' => $this->actual_beds,
            'source_status' => $this->source_status,
            'service_area' => $this->service_area,
            'address' => $this->address,
            'province_code' => $this->province_code,
            'province' => $this->province,
            'district_code' => $this->district_code,
            'district' => $this->district,
            'sub_district_code' => $this->sub_district_code,
            'sub_district' => $this->sub_district,
            'village' => $this->village,
            'postal_code' => $this->postal_code,
            'parent_facility' => $this->parent_facility,
            'established_date' => $this->established_date?->format('Y-m-d'),
            'closed_date' => $this->closed_date?->format('Y-m-d'),
            'source_updated_at' => $this->source_updated_at?->toISOString(),
            'data_source' => $this->data_source,
            'phone' => $this->phone,
            'website' => $this->website,
            'open_hours' => $this->open_hours,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
