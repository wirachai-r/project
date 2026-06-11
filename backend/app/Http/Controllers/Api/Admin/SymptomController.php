<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SymptomRequest;
use App\Http\Resources\Admin\SymptomResource;
use App\Models\MainSymptom;
use Illuminate\Http\Request;

class SymptomController extends Controller
{
    public function index(Request $request)
    {
        $symptoms = MainSymptom::query()
            ->with('category')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->symptom_category_id, fn($q) => $q->where('symptom_category_id', $request->symptom_category_id))
            ->when($request->search, fn($q) => $q->where('symptom_name', 'like', '%' . $request->search . '%'))
            ->orderBy('symptom_name')
            ->paginate(20);

        return SymptomResource::collection($symptoms);
    }

    public function store(SymptomRequest $request)
    {
        $symptom = MainSymptom::create([
            'symptom_id'          => $this->generateId(),
            'symptom_name'        => $request->symptom_name,
            'symptom_name_en'     => $request->symptom_name_en,
            'description'         => $request->description,
            'symptom_image'       => $request->symptom_image,
            'status'              => $request->status ?? '1',
            'symptom_category_id' => $request->symptom_category_id,
            'created_by'          => $request->user()->user_id,
            'updated_by'          => $request->user()->user_id,
        ]);

        return new SymptomResource($symptom->load('category'));
    }

    public function show(MainSymptom $symptom)
    {
        return new SymptomResource($symptom->load('category'));
    }

    public function update(SymptomRequest $request, MainSymptom $symptom)
    {
        $symptom->update([
            'symptom_name'        => $request->symptom_name,
            'symptom_name_en'     => $request->symptom_name_en,
            'description'         => $request->description,
            'symptom_image'       => $request->symptom_image,
            'status'              => $request->status ?? $symptom->status,
            'symptom_category_id' => $request->symptom_category_id,
            'updated_by'          => $request->user()->user_id,
        ]);

        return new SymptomResource($symptom->load('category'));
    }

    public function destroy(MainSymptom $symptom)
    {
        if ($symptom->diagrams()->exists()) {
            return response()->json([
                'message' => 'ไม่สามารถลบได้ เนื่องจากมี diagram ที่ใช้อาการนี้อยู่'
            ], 422);
        }

        $symptom->delete();

        return response()->json(['message' => 'ลบอาการสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = MainSymptom::max('symptom_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
