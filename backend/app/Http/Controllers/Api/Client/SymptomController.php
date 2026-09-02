<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\SymptomCategoryResource;
use App\Http\Resources\Client\SymptomResource;
use App\Models\MainSymptom;
use App\Models\SymptomCategory;
use App\Support\AdminTableQuery;
use Illuminate\Http\Request;

/**
 * @tags Client SymptomController
 */
class SymptomController extends Controller
{
    public function categories()
    {
        $categories = SymptomCategory::query()
            ->where('status', '1')
            ->orderBy('category_name')
            ->get();

        return SymptomCategoryResource::collection($categories);
    }

    public function index(Request $request)
    {
        $symptoms = MainSymptom::query()
            ->with('category')
            ->when($request->string('sort')->toString() === 'popular', fn ($query) => $query->withCount([
                'assessments as popularity_count' => fn ($assessments) => $assessments->where('assessment_status', 'C'),
            ]))
            ->where('status', '1')
            ->when($request->symptom_category_id, fn ($q) => $q->where('symptom_category_id', $request->symptom_category_id))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch(
                $q,
                $request->string('search')->toString(),
                'symptom_id',
                ['symptom_name', 'symptom_name_en', 'description'],
            ))
            ->when(
                $request->string('sort')->toString() === 'popular',
                fn ($query) => $query->orderByDesc('popularity_count'),
            )
            ->orderBy('symptom_name')
            ->get();

        return SymptomResource::collection($symptoms);
    }

    public function show(MainSymptom $mainSymptom)
    {
        abort_if($mainSymptom->status !== '1', 404);

        return new SymptomResource($mainSymptom->load('category'));
    }
}
