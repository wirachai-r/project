<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\HealthcareFacilityResource;
use App\Models\HealthcareFacility;
use App\Services\OpenStreetMapFacilityService;
use Illuminate\Http\Request;

/**
 * @tags Client HealthcareFacilityController
 */

class HealthcareFacilityController extends Controller
{
    public function index(Request $request, OpenStreetMapFacilityService $openStreetMap)
    {
        $query = HealthcareFacility::query()
            ->where('status', '1')
            ->when($request->facility_type, fn($q) => $q->where('facility_type', $request->facility_type))
            ->when($request->province, fn($q) => $q->where('province', $request->province))
            ->when($request->search, fn($q) => $q->where('facility_name', 'like', '%' . $request->search . '%'))
            ->orderBy('facility_name');

        $latitude = filter_var($request->latitude, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($request->longitude, FILTER_VALIDATE_FLOAT);
        if ($latitude === false || $longitude === false) {
            return HealthcareFacilityResource::collection($query->paginate(20));
        }

        $local = HealthcareFacilityResource::collection($query->get())->resolve($request);
        $external = $openStreetMap->nearby(
            latitude: (float) $latitude,
            longitude: (float) $longitude,
            radiusMetres: $request->integer('radius', 10000),
            facilityType: $request->string('facility_type')->toString() ?: null,
            search: $request->string('search')->toString() ?: null,
        );

        $knownNames = array_fill_keys(array_map(
            fn (array $facility) => mb_strtolower(trim($facility['facility_name'])),
            $local,
        ), true);
        foreach ($external as $facility) {
            $key = mb_strtolower(trim($facility['facility_name']));
            if (! isset($knownNames[$key])) {
                $local[] = $facility;
                $knownNames[$key] = true;
            }
        }

        return response()->json(['data' => $local]);
    }

    public function show(HealthcareFacility $healthcareFacility)
    {
        abort_if($healthcareFacility->status !== '1', 404);

        return new HealthcareFacilityResource($healthcareFacility);
    }
}
