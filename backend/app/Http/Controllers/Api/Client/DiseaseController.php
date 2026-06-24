<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\DiseaseCategoryResource;
use App\Http\Resources\Client\DiseaseResource;
use App\Models\Disease;
use App\Models\DiseaseCategory;
use Illuminate\Http\Request;

/**
 * @tags Client DiseaseController
 */

class DiseaseController extends Controller
{
    public function categories()
    {
        $categories = DiseaseCategory::query()
            ->where('status', '1')
            ->orderBy('category_name')
            ->get();

        return DiseaseCategoryResource::collection($categories);
    }

    public function index(Request $request)
    {
        $diseases = Disease::query()
            ->with('category')
            ->where('status', '1')
            ->when($request->disease_category_id, fn($q) => $q->where('disease_category_id', $request->disease_category_id))
            ->when($request->search, fn($q) => $q->where('disease_name', 'like', '%' . $request->search . '%'))
            ->orderBy('disease_name')
            ->paginate(20);

        return DiseaseResource::collection($diseases);
    }

    public function show(Disease $disease)
    {
        abort_if($disease->status !== '1', 404);

        return new DiseaseResource($disease->load(['category', 'treatmentOrders']));
    }
}
