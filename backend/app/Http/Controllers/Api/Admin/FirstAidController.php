<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FirstAidRequest;
use App\Http\Resources\Admin\FirstAidResource;
use App\Models\FirstAid;
use Illuminate\Http\Request;

/**
 * @tags Admin FirstAidController
 */

class FirstAidController extends Controller
{
    public function index(Request $request)
    {
        $firstAids = FirstAid::query()
            ->with('category')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->first_aid_category_id, fn($q) => $q->where('first_aid_category_id', $request->first_aid_category_id))
            ->when($request->search, fn($q) => $q->where('title', 'like', '%' . $request->search . '%'))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return FirstAidResource::collection($firstAids);
    }

    public function store(FirstAidRequest $request)
    {
        $firstAid = FirstAid::create([
            'first_aid_id'          => $this->generateId(),
            'title'                 => $request->title,
            'title_en'              => $request->title_en,
            'content'               => $request->content,
            'content_en'            => $request->content_en,
            'cover_image'           => $request->cover_image,
            'status'                => $request->status ?? '2',
            'first_aid_category_id' => $request->first_aid_category_id,
            'created_by'            => $request->user()->user_id,
            'updated_by'            => $request->user()->user_id,
        ]);

        return new FirstAidResource($firstAid->load('category'));
    }

    public function show(FirstAid $firstAid)
    {
        return new FirstAidResource($firstAid->load('category'));
    }

    public function update(FirstAidRequest $request, FirstAid $firstAid)
    {
        $firstAid->update([
            'title'                 => $request->title,
            'title_en'              => $request->title_en,
            'content'               => $request->content,
            'content_en'            => $request->content_en,
            'cover_image'           => $request->cover_image,
            'status'                => $request->status ?? $firstAid->status,
            'first_aid_category_id' => $request->first_aid_category_id,
            'updated_by'            => $request->user()->user_id,
        ]);

        return new FirstAidResource($firstAid->load('category'));
    }

    public function destroy(FirstAid $firstAid)
    {
        $firstAid->delete();

        return response()->json(['message' => 'ลบข้อมูลปฐมพยาบาลสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = FirstAid::max('first_aid_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
