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
        'facility_type',
        'address',
        'province',
        'district',
        'subdistrict',
        'postcode',
        'phone',
        'latitude',
        'longitude',
        'status',
        'created_by',
        'updated_by',
    ];
}
