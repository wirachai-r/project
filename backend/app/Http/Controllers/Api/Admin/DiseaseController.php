<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiseaseRequest;
use App\Http\Resources\Admin\DiseaseResource;
use App\Models\Disease;
use Illuminate\Http\Request;

/**
 * @tags Admin DiseaseController
 */

class DiseaseController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) ($request->per_page ?? 20);

        $diseases = Disease::query()
            ->with('category')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->disease_category_id, fn($q) => $q->where('disease_category_id', $request->disease_category_id))
            ->when($request->search, fn($q) => $q->where('disease_name', 'like', '%' . $request->search . '%'))
            ->when(
                in_array($request->sort_by, ['id', 'name']),
                function ($q) use ($request) {
                    $direction = $request->sort_direction === 'asc' ? 'asc' : 'desc';

                    if ($request->sort_by === 'id') {
                        $q->orderBy('disease_id', $direction);
                    } elseif ($request->sort_by === 'name') {
                        $q->orderBy('disease_name', $direction);
                    }
                },
                fn($q) => $q->orderBy('disease_id', 'desc')
            )
            ->paginate($perPage);

        return DiseaseResource::collection($diseases);
    }

    public function store(DiseaseRequest $request)
    {
        $disease = Disease::create([
            'disease_id'           => $this->generateId(),
            'disease_name'         => $request->disease_name,
            'disease_name_en'      => $request->disease_name_en,
            'description'          => $request->description,
            'cause'                => $request->cause,
            'symptom_description'  => $request->symptom_description,
            'prevention'           => $request->prevention,
            'disease_image'        => $request->disease_image,
            'status'               => $request->status ?? '1',
            'disease_category_id'  => $request->disease_category_id,
            'created_by'           => $request->user()->user_id,
            'updated_by'           => $request->user()->user_id,
        ]);

        return new DiseaseResource($disease->load('category'));
    }

    public function show(Disease $disease)
    {
        return new DiseaseResource($disease->load(['category', 'treatmentOrders']));
    }

   public function update(DiseaseRequest $request, Disease $disease)
{
    $disease->update([
        'disease_name'         => $request->has('disease_name') ? $request->disease_name : $disease->disease_name,
        'disease_name_en'      => $request->has('disease_name_en') ? $request->disease_name_en : $disease->disease_name_en,
        'description'          => $request->has('description') ? $request->description : $disease->description,
        'cause'                => $request->has('cause') ? $request->cause : $disease->cause,
        'symptom_description'  => $request->has('symptom_description') ? $request->symptom_description : $disease->symptom_description,
        'prevention'           => $request->has('prevention') ? $request->prevention : $disease->prevention,
        'disease_image'        => $request->has('disease_image') ? $request->disease_image : $disease->disease_image,
        'status'               => $request->status ?? $disease->status,
        'disease_category_id'  => $request->has('disease_category_id') ? $request->disease_category_id : $disease->disease_category_id,
        'updated_by'           => $request->user()->user_id,
    ]);

    return new DiseaseResource($disease->load('category'));
}

    public function destroy(Disease $disease)
    {
        if ($disease->treatmentOrders()->exists()) {
            return response()->json([
                'message' => 'ไม่สามารถลบได้ เนื่องจากมีคำสั่งการรักษาของโรคนี้อยู่'
            ], 422);
        }

        $disease->delete();

        return response()->json(['message' => 'ลบโรคสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = Disease::max('disease_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
