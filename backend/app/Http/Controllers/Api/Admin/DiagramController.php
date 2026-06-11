<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiagramRequest;
use App\Http\Resources\Admin\DiagramResource;
use App\Models\Diagram;
use Illuminate\Http\Request;

class DiagramController extends Controller
{
    public function index(Request $request)
    {
        $diagrams = Diagram::query()
            ->with(['symptom', 'entryBox'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->symptom_id, fn($q) => $q->where('symptom_id', $request->symptom_id))
            ->when($request->search, fn($q) => $q->where('diagram_name', 'like', '%' . $request->search . '%'))
            ->orderBy('diagram_name')
            ->paginate(20);

        return DiagramResource::collection($diagrams);
    }

    public function store(DiagramRequest $request)
    {
        $diagram = Diagram::create([
            'diagram_id'      => $this->generateId(),
            'diagram_name'    => $request->diagram_name,
            'diagram_name_en' => $request->diagram_name_en,
            'description'     => $request->description,
            'status'          => $request->status ?? '1',
            'symptom_id'      => $request->symptom_id,
            'created_by'      => $request->user()->user_id,
            'updated_by'      => $request->user()->user_id,
        ]);

        return new DiagramResource($diagram->load(['symptom', 'entryBox']));
    }

    public function show(Diagram $diagram)
    {
        return new DiagramResource($diagram->load(['symptom', 'entryBox', 'questionBoxes']));
    }

    public function update(DiagramRequest $request, Diagram $diagram)
    {
        $diagram->update([
            'diagram_name'    => $request->diagram_name,
            'diagram_name_en' => $request->diagram_name_en,
            'description'     => $request->description,
            'status'          => $request->status ?? $diagram->status,
            'symptom_id'      => $request->symptom_id,
            'entry_box_id'    => $request->entry_box_id,
            'updated_by'      => $request->user()->user_id,
        ]);

        return new DiagramResource($diagram->load(['symptom', 'entryBox']));
    }

    public function destroy(Diagram $diagram)
    {
        if ($diagram->questionBoxes()->exists()) {
            return response()->json([
                'message' => 'ไม่สามารถลบได้ เนื่องจากมีคำถามใน diagram นี้อยู่'
            ], 422);
        }

        $diagram->delete();

        return response()->json(['message' => 'ลบ diagram สำเร็จ']);
    }

    private function generateId(): string
    {
        $last = Diagram::max('diagram_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
