<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\HealthcareFacilityResource;
use App\Models\HealthcareFacility;
use Illuminate\Http\Request;

/**
 * @tags Client HealthcareFacilityController
 */

class HealthcareFacilityController extends Controller
{
    public function index(Request $request)
    {
        $facilities = HealthcareFacility::query()
            ->where('status', '1')
            ->when($request->facility_type, fn($q) => $q->where('facility_type', $request->facility_type))
            ->when($request->province, fn($q) => $q->where('province', $request->province))
            ->when($request->search, fn($q) => $q->where('facility_name', 'like', '%' . $request->search . '%'))
            ->orderBy('facility_name')
            ->paginate(20);

        return HealthcareFacilityResource::collection($facilities);
    }

    public function show(HealthcareFacility $healthcareFacility)
    {
        abort_if($healthcareFacility->status !== '1', 404);

        return new HealthcareFacilityResource($healthcareFacility);
    }
}
