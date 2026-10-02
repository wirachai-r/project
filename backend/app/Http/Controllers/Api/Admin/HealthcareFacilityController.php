<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HealthcareFacilityRequest;
use App\Http\Resources\Admin\HealthcareFacilityResource;
use App\Models\HealthcareFacility;
use App\Support\AdminTableQuery;
use Illuminate\Http\Request;

/**
 * @tags Admin HealthcareFacilityController
 */
class HealthcareFacilityController extends Controller
{
    public function index(Request $request)
    {
        $facilities = HealthcareFacility::query()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->facility_type, fn ($q) => $q->where('facility_type', $request->facility_type))
            ->when($request->province, fn ($q) => $q->where('province', $request->province))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch($q, $request->search, 'facility_id', ['facility_name', 'facility_name_en']))
            ->orderBy('facility_name')
            ->paginate(20);

        return HealthcareFacilityResource::collection($facilities);
    }

    public function store(HealthcareFacilityRequest $request)
    {
        $facility = HealthcareFacility::create(array_merge(
            $request->validated(),
            [
                'facility_id' => $this->generateId(),
                'status' => $request->validated('status', '1'),
                'created_by' => $request->user()->user_id,
                'updated_by' => $request->user()->user_id,
            ],
        ));

        return new HealthcareFacilityResource($facility);
    }

    public function show(HealthcareFacility $healthcareFacility)
    {
        return new HealthcareFacilityResource($healthcareFacility);
    }

    public function update(HealthcareFacilityRequest $request, HealthcareFacility $healthcareFacility)
    {
        $healthcareFacility->update(array_merge(
            $request->validated(),
            ['updated_by' => $request->user()->user_id],
        ));

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
        $next = $last ? (int) $last + 1 : 1;

        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
