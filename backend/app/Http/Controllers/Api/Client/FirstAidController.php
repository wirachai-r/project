<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\FirstAidCategoryResource;
use App\Http\Resources\Client\FirstAidResource;
use App\Models\FirstAid;
use App\Models\FirstAidCategory;
use Illuminate\Http\Request;

/**
 * @tags Client FirstAidController
 */

class FirstAidController extends Controller
{
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
            ->when($request->first_aid_category_id, fn($q) => $q->where('first_aid_category_id', $request->first_aid_category_id))
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->orderBy('published_at', 'desc')
            ->paginate(20);

        return FirstAidResource::collection($firstAids);
    }

    public function show(FirstAid $firstAid)
    {
        abort_if($firstAid->status !== '1', 404);

        return new FirstAidResource($firstAid->load('category'));
    }
}
