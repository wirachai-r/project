<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\BodyAreaGroupResource;
use App\Http\Resources\Client\SymptomResource;
use App\Models\BodyAreaGroup;
use App\Models\BodyAreaSubgroup;

class BodyAreaGroupController extends Controller
{
    public function index()
    {
        $groups = BodyAreaGroup::query()
            ->where('status', '1')
            ->whereHas('symptoms', fn ($query) => $query->where('main_symptoms.status', '1'))
            ->withCount(['symptoms' => fn ($query) => $query->where('main_symptoms.status', '1')])
            ->with(['subgroups' => fn ($query) => $query
                ->where('status', '1')
                ->withCount(['symptoms' => fn ($symptoms) => $symptoms->where('main_symptoms.status', '1')])])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return BodyAreaGroupResource::collection($groups);
    }

    public function symptoms(BodyAreaGroup $bodyAreaGroup)
    {
        abort_if($bodyAreaGroup->status !== '1', 404);
        $symptoms = $bodyAreaGroup->symptoms()
            ->where('main_symptoms.status', '1')
            ->with('category')
            ->get();

        return SymptomResource::collection($symptoms);
    }

    public function subgroupSymptoms(BodyAreaGroup $bodyAreaGroup, BodyAreaSubgroup $bodyAreaSubgroup)
    {
        abort_if($bodyAreaGroup->status !== '1'
            || $bodyAreaSubgroup->body_area_group_id !== $bodyAreaGroup->id
            || $bodyAreaSubgroup->status !== '1', 404);

        return SymptomResource::collection(
            $bodyAreaSubgroup->symptoms()
                ->where('main_symptoms.status', '1')
                ->with('category')
                ->get()
        );
    }
}
