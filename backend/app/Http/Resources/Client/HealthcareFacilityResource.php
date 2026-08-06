<?php

namespace App\Http\Resources\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class HealthcareFacilityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'facility_id'      => $this->facility_id,
            'facility_name'    => $this->facility_name,
            'facility_name_en' => $this->facility_name_en,
            'facility_type'    => $this->facility_type,
            'address'          => $this->address,
            'province'         => $this->province,
            'district'         => $this->district,
            'sub_district'     => $this->sub_district,
            'postal_code'      => $this->postal_code,
            'latitude'         => $this->latitude,
            'longitude'        => $this->longitude,
            'phone'            => $this->phone,
            'website'          => $this->website,
            'open_hours'       => $this->open_hours,
            'source'           => 'local',
        ];
    }
}
