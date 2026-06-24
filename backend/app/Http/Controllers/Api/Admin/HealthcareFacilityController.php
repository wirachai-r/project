<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HealthcareFacilityRequest;
use App\Http\Resources\Admin\HealthcareFacilityResource;
use App\Models\HealthcareFacility;
use Illuminate\Http\Request;

/**
 * @tags Admin HealthcareFacilityController
 */

class HealthcareFacilityController extends Controller
{
    public function index(Request $request)
    {
        $facilities = HealthcareFacility::query()
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->facility_type, fn($q) => $q->where('facility_type', $request->facility_type))
            ->when($request->province, fn($q) => $q->where('province', $request->province))
            ->when($request->search, fn($q) => $q->where('facility_name', 'like', '%' . $request->search . '%'))
            ->orderBy('facility_name')
            ->paginate(20);

        return HealthcareFacilityResource::collection($facilities);
    }

    public function store(HealthcareFacilityRequest $request)
    {
        $facility = HealthcareFacility::create([
            'facility_id'   => $this->generateId(),
            'facility_name' => $request->facility_name,
            'facility_type' => $request->facility_type,
            'address'       => $request->address,
            'province'      => $request->province,
            'district'      => $request->district,
            'sub_district'   => $request->sub_district,
            'postal_code'      => $request->postal_code,
            'phone'         => $request->phone,
            'latitude'      => $request->latitude,
            'longitude'     => $request->longitude,
            'status'        => $request->status ?? '1',
            'created_by'    => $request->user()->user_id,
            'updated_by'    => $request->user()->user_id,
        ]);

        return new HealthcareFacilityResource($facility);
    }

    public function show(HealthcareFacility $healthcareFacility)
    {
        return new HealthcareFacilityResource($healthcareFacility);
    }

    public function update(HealthcareFacilityRequest $request, HealthcareFacility $healthcareFacility)
    {
        $healthcareFacility->update([
            'facility_name' => $request->facility_name,
            'facility_type' => $request->facility_type,
            'address'       => $request->address,
            'province'      => $request->province,
            'district'      => $request->district,
            'sub_district'   => $request->sub_district,
            'postal_code'      => $request->postal_code,
            'phone'         => $request->phone,
            'latitude'      => $request->latitude,
            'longitude'     => $request->longitude,
            'status'        => $request->status ?? $healthcareFacility->status,
            'updated_by'    => $request->user()->user_id,
        ]);

        return new HealthcareFacilityResource($healthcareFacility);
    }

    public function destroy(HealthcareFacility $healthcareFacility)
    {
        $healthcareFacility->delete();

        return response()->json(['message' => 'ลบสถานพยาบาลสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = HealthcareFacility::max('facility_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
