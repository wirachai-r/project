<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnswerChoiceRequest;
use App\Http\Resources\Admin\AnswerChoiceResource;
use App\Models\AnswerChoice;
use App\Models\Diagram;
use App\Models\QuestionBox;
use Illuminate\Http\Request;

/**
 * @tags Admin AnswerChoiceController
 */

class AnswerChoiceController extends Controller
{
    public function index(Request $request, QuestionBox $questionBox)
    {
        $choices = AnswerChoice::query()
            ->with(['nextBox', 'nextDiagram'])
            ->where('box_id', $questionBox->box_id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('order')
            ->get();

        return AnswerChoiceResource::collection($choices);
    }

    public function store(AnswerChoiceRequest $request, QuestionBox $questionBox)
    {
        // ตรวจ next_box_id ต้องอยู่ใน diagram เดียวกัน
        if ($request->next_box_id) {
            $nextBox = QuestionBox::where('box_id', $request->next_box_id)->first();
            abort_if(
                $nextBox && $nextBox->diagram_id !== $questionBox->diagram_id,
                422,
                'next_box ต้องอยู่ใน diagram เดียวกัน'
            );
        }

        // next_box_id และ next_diagram_id ใช้พร้อมกันไม่ได้
        abort_if(
            $request->next_box_id && $request->next_diagram_id,
            422,
            'ไม่สามารถระบุ next_box_id และ next_diagram_id พร้อมกันได้'
        );

        $choice = AnswerChoice::create([
            'choice_id'       => $this->generateId(),
            'choice_text'     => $request->choice_text,
            'choice_text_en'  => $request->choice_text_en,
            'choice_image'    => $request->choice_image,
            'order'           => $request->order ?? 0,
            'status'          => $request->status ?? '1',
            'box_id'          => $questionBox->box_id,
            'next_box_id'     => $request->next_box_id,
            'next_diagram_id' => $request->next_diagram_id,
            'created_by'      => $request->user()->user_id,
            'updated_by'      => $request->user()->user_id,
        ]);

        return new AnswerChoiceResource($choice->load(['nextBox', 'nextDiagram']));
    }

    public function show(QuestionBox $questionBox, AnswerChoice $answerChoice)
    {
        abort_if($answerChoice->box_id !== $questionBox->box_id, 404);

        return new AnswerChoiceResource($answerChoice->load(['nextBox', 'nextDiagram']));
    }

    public function update(AnswerChoiceRequest $request, QuestionBox $questionBox, AnswerChoice $answerChoice)
    {
        abort_if($answerChoice->box_id !== $questionBox->box_id, 404);

        if ($request->next_box_id) {
            $nextBox = QuestionBox::where('box_id', $request->next_box_id)->first();
            abort_if(
                $nextBox && $nextBox->diagram_id !== $questionBox->diagram_id,
                422,
                'next_box ต้องอยู่ใน diagram เดียวกัน'
            );
        }

        abort_if(
            $request->next_box_id && $request->next_diagram_id,
            422,
            'ไม่สามารถระบุ next_box_id และ next_diagram_id พร้อมกันได้'
        );

        $answerChoice->update([
            'choice_text'     => $request->choice_text,
            'choice_text_en'  => $request->choice_text_en,
            'choice_image'    => $request->choice_image,
            'order'           => $request->order ?? $answerChoice->order,
            'status'          => $request->status ?? $answerChoice->status,
            'next_box_id'     => $request->next_box_id,
            'next_diagram_id' => $request->next_diagram_id,
            'updated_by'      => $request->user()->user_id,
        ]);

        return new AnswerChoiceResource($answerChoice->load(['nextBox', 'nextDiagram']));
    }

    public function destroy(QuestionBox $questionBox, AnswerChoice $answerChoice)
    {
        abort_if($answerChoice->box_id !== $questionBox->box_id, 404);

        $answerChoice->delete();

        return response()->json(['message' => 'ลบตัวเลือกสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = AnswerChoice::max('choice_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
