<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\BodyAreaGroupResource;
use App\Http\Resources\Client\SymptomResource;
use App\Models\BodyAreaGroup;
use App\Models\BodyAreaSubgroup;
use Illuminate\Http\Request;

class BodyAreaGroupController extends Controller
{
    public function index(Request $request)
    {
        $mode = $this->mode($request);
        $eligible = fn ($query) => $this->eligibleSymptoms($query, $mode);

        $groups = BodyAreaGroup::query()
            ->where('status', '1')
            ->whereHas('symptoms', $eligible)
            ->withCount(['symptoms' => $eligible])
            ->with(['subgroups' => fn ($query) => $query
                ->where('status', '1')
                ->whereHas('symptoms', $eligible)
                ->withCount(['symptoms' => $eligible])])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return BodyAreaGroupResource::collection($groups);
    }

    public function symptoms(Request $request, BodyAreaGroup $bodyAreaGroup)
    {
        abort_if($bodyAreaGroup->status !== '1', 404);
        $symptoms = $this->eligibleSymptoms(
            $bodyAreaGroup->symptoms(),
            $this->mode($request),
        )
            ->with('category')
            ->get();

        return SymptomResource::collection($symptoms);
    }

    public function subgroupSymptoms(
        Request $request,
        BodyAreaGroup $bodyAreaGroup,
        BodyAreaSubgroup $bodyAreaSubgroup,
    ) {
        abort_if($bodyAreaGroup->status !== '1'
            || $bodyAreaSubgroup->body_area_group_id !== $bodyAreaGroup->id
            || $bodyAreaSubgroup->status !== '1', 404);

        return SymptomResource::collection(
            $this->eligibleSymptoms(
                $bodyAreaSubgroup->symptoms(),
                $this->mode($request),
            )
                ->with('category')
                ->get()
        );
    }

    private function mode(Request $request): ?string
    {
        return $request->validate([
            'mode' => 'nullable|in:classic,adaptive',
        ])['mode'] ?? null;
    }

    private function eligibleSymptoms($query, ?string $mode)
    {
        return $query
            ->where('main_symptoms.status', '1')
            ->when($mode === 'classic', fn ($symptoms) => $symptoms->whereHas(
                'diagrams',
                fn ($diagrams) => $diagrams
                    ->where('diagrams.status', '1')
                    ->whereNotNull('diagrams.entry_box_id'),
            ))
            ->when($mode === 'adaptive', fn ($symptoms) => $symptoms->whereHas(
                'diseases',
                fn ($diseases) => $diseases->where('diseases.status', '1'),
            ));
    }
}
