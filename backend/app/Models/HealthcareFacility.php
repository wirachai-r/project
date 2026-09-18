<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthcareFacility extends Model
{
    protected $primaryKey = 'facility_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'facility_id',
        'facility_name',
        'facility_name_en',
        'facility_type',
        'moph_code_9_new',
        'moph_code_9',
        'moph_code_5',
        'license_code_11',
        'organization_type',
        'service_type',
        'affiliation',
        'department',
        'hospital_level',
        'network_type',
        'actual_beds',
        'source_status',
        'service_area',
        'address',
        'province_code',
        'province',
        'district_code',
        'district',
        'sub_district_code',
        'sub_district',
        'village',
        'postal_code',
        'parent_facility',
        'established_date',
        'closed_date',
        'source_updated_at',
        'data_source',
        'phone',
        'website',
        'open_hours',
        'latitude',
        'longitude',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'actual_beds' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'established_date' => 'date:Y-m-d',
            'closed_date' => 'date:Y-m-d',
            'source_updated_at' => 'datetime',
        ];
    }
}
