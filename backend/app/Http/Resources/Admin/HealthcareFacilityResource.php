<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class HealthcareFacilityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'facility_id'   => $this->facility_id,
            'facility_name' => $this->facility_name,
            'facility_type' => $this->facility_type,
            'address'       => $this->address,
            'province'      => $this->province,
            'district'      => $this->district,
            'subdistrict'   => $this->subdistrict,
            'postcode'      => $this->postcode,
            'phone'         => $this->phone,
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
            'status'        => $this->status,
            'created_by'    => $this->created_by,
            'updated_by'    => $this->updated_by,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
