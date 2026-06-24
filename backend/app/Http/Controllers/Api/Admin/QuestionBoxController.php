<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuestionBoxRequest;
use App\Http\Resources\Admin\QuestionBoxResource;
use App\Models\Diagram;
use App\Models\QuestionBox;
use Illuminate\Http\Request;

/**
 * @tags Admin QuestionBoxController
 */

class QuestionBoxController extends Controller
{
    public function index(Request $request, Diagram $diagram)
    {
        $boxes = QuestionBox::query()
            ->with('choices')
            ->where('diagram_id', $diagram->diagram_id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('box_id')
            ->paginate(20);

        return QuestionBoxResource::collection($boxes);
    }

    public function store(QuestionBoxRequest $request, Diagram $diagram)
    {
        $box = QuestionBox::create([
            'box_id'        => $this->generateId(),
            'question_text'    => $request->question_text,
            'question_text_en' => $request->question_text_en,
            'question_type'    => $request->question_type ?? 'S',
            'status'           => $request->status ?? '1',
            'diagram_id'       => $diagram->diagram_id,
            'created_by'       => $request->user()->user_id,
            'updated_by'       => $request->user()->user_id,
        ]);

        return new QuestionBoxResource($box->load('choices'));
    }

    public function show(Diagram $diagram, QuestionBox $questionBox)
    {
        return new QuestionBoxResource($questionBox->load('choices'));
    }

    public function update(QuestionBoxRequest $request, Diagram $diagram, QuestionBox $questionBox)
    {
        $questionBox->update([
            'question_text'    => $request->question_text,
            'question_text_en' => $request->question_text_en,
            'question_type'    => $request->question_type ?? $questionBox->question_type,
            'status'           => $request->status ?? $questionBox->status,
            'updated_by'       => $request->user()->user_id,
        ]);

        return new QuestionBoxResource($questionBox->load('choices'));
    }

    public function destroy(Diagram $diagram, QuestionBox $questionBox)
    {
        if ($questionBox->choices()->exists()) {
            return response()->json([
                'message' => 'ไม่สามารถลบได้ เนื่องจากมีตัวเลือกในคำถามนี้อยู่'
            ], 422);
        }

        $questionBox->delete();

        return response()->json(['message' => 'ลบคำถามสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = QuestionBox::max('box_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
