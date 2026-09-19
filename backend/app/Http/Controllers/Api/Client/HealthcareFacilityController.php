<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\HealthcareFacilityResource;
use App\Models\HealthcareFacility;
use App\Services\OpenStreetMapFacilityService;
use App\Support\AdminTableQuery;
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
            ->when($request->facility_type, fn ($q) => $q->where('facility_type', $request->facility_type))
            ->when($request->province, fn ($q) => $q->where('province', $request->province))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch(
                $q,
                $request->string('search')->toString(),
                null,
                ['facility_name', 'facility_name_en'],
            ))
            ->orderBy('facility_name');

        $latitude = filter_var($request->latitude, FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($request->longitude, FILTER_VALIDATE_FLOAT);
        if ($latitude === false || $longitude === false) {
            return HealthcareFacilityResource::collection($query->paginate(20));
        }

        $radiusMetres = min(max($request->integer('radius', 10000), 1000), 20000);
        $latitudeDelta = $radiusMetres / 111320;
        $longitudeScale = max(cos(deg2rad((float) $latitude)), 0.01);
        $longitudeDelta = $radiusMetres / (111320 * $longitudeScale);

        $local = HealthcareFacilityResource::collection($query
            ->whereBetween('latitude', [(float) $latitude - $latitudeDelta, (float) $latitude + $latitudeDelta])
            ->whereBetween('longitude', [(float) $longitude - $longitudeDelta, (float) $longitude + $longitudeDelta])
            ->limit(100)
            ->get())->resolve($request);
        $externalResult = $openStreetMap->nearbyResult(
            latitude: (float) $latitude,
            longitude: (float) $longitude,
            radiusMetres: $radiusMetres,
            facilityType: $request->string('facility_type')->toString() ?: null,
            search: $request->string('search')->toString() ?: null,
        );
        $external = $externalResult['facilities'];

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

        return response()->json([
            'data' => array_slice($local, 0, 200),
            'meta' => [
                'radius_metres' => $radiusMetres,
                'source' => $externalResult['source'],
                'cache_status' => $externalResult['cache_status'],
                'external_facilities_available' => $externalResult['available'],
                'external_facilities_stale' => $externalResult['stale'],
            ],
        ]);
    }

    public function show(HealthcareFacility $healthcareFacility)
    {
        abort_if($healthcareFacility->status !== '1', 404);

        return new HealthcareFacilityResource($healthcareFacility);
    }
}
