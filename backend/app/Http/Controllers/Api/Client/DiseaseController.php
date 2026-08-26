<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\DiseaseCategoryResource;
use App\Http\Resources\Client\DiseaseResource;
use App\Models\Disease;
use App\Models\DiseaseCategory;
use App\Support\AdminTableQuery;
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
            ->when($request->disease_category_id, fn ($q) => $q->where('disease_category_id', $request->disease_category_id))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch(
                $q,
                $request->string('search')->toString(),
                'disease_id',
                ['disease_name', 'disease_name_en', 'description'],
            ))
            ->orderBy('disease_name')
            ->get();

        return DiseaseResource::collection($diseases);
    }

    public function show(Request $request, Disease $disease)
    {
        abort_if($disease->status !== '1', 404);

        if ($request->boolean('track_view', true)) {
            Disease::query()
                ->whereKey($disease->getKey())
                ->toBase()
                ->increment('view_count');
            $disease->refresh();
        }

        return new DiseaseResource($disease->load(['category', 'treatmentOrders']));
    }
}
