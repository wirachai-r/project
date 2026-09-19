<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\FirstAidCategoryResource;
use App\Http\Resources\Client\FirstAidResource;
use App\Models\FirstAid;
use App\Models\FirstAidCategory;
use App\Support\AdminTableQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Client FirstAidController
 */
class FirstAidController extends Controller
{
    public function offlineBundle(): JsonResponse
    {
        $items = FirstAid::query()
            ->with('category')
            ->where('status', '1')
            ->orderBy('published_at', 'desc')
            ->get();

        return response()->json([
            'data' => $items,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function categories()
    {
        $categories = FirstAidCategory::query()
            ->where('status', '1')
            ->orderBy('category_name')
            ->get();

        return FirstAidCategoryResource::collection($categories);
    }

    public function index(Request $request)
    {
        $firstAids = FirstAid::query()
            ->with('category')
            ->where('status', '1')
            ->when($request->first_aid_category_id, fn ($q) => $q->where('first_aid_category_id', $request->first_aid_category_id))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch(
                $q,
                $request->string('search')->toString(),
                null,
                ['title', 'title_en'],
            ))
            ->orderBy('published_at', 'desc')
            ->paginate(20);

        return FirstAidResource::collection($firstAids);
    }

    public function show(FirstAid $firstAid)
    {
        abort_if($firstAid->status !== '1', 404);

        $firstAid->increment('view_count');

        return new FirstAidResource($firstAid->load('category'));
    }
}
