<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\SymptomCategoryResource;
use App\Http\Resources\Client\SymptomResource;
use App\Models\MainSymptom;
use App\Models\SymptomCategory;
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
            ->where('status', '1')
            ->when($request->symptom_category_id, fn($q) => $q->where('symptom_category_id', $request->symptom_category_id))
            ->when($request->search, fn($q) => $q->where('symptom_name', 'like', '%' . $request->search . '%'))
            ->orderBy('symptom_name')
            ->paginate(20);

        return SymptomResource::collection($symptoms);
    }

    public function show(MainSymptom $mainSymptom)
    {
        abort_if($mainSymptom->status !== '1', 404);

        return new SymptomResource($mainSymptom->load('category'));
    }
}
