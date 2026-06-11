<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnswerChoiceRequest;
use App\Http\Resources\Admin\AnswerChoiceResource;
use App\Models\AnswerChoice;
use App\Models\QuestionBox;
use Illuminate\Http\Request;

class AnswerChoiceController extends Controller
{
    public function index(Request $request, QuestionBox $questionBox)
    {
        $choices = AnswerChoice::query()
            ->where('box_id', $questionBox->box_id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('order')
            ->get();

        return AnswerChoiceResource::collection($choices);
    }

    public function store(AnswerChoiceRequest $request, QuestionBox $questionBox)
    {
        $choice = AnswerChoice::create([
            'choice_id'      => $this->generateId(),
            'choice_text'    => $request->choice_text,
            'choice_text_en' => $request->choice_text_en,
            'order'          => $request->order ?? 0,
            'status'         => $request->status ?? '1',
            'box_id'         => $questionBox->box_id,
            'next_box_id'    => $request->next_box_id,
            'created_by'     => $request->user()->user_id,
            'updated_by'     => $request->user()->user_id,
        ]);

        return new AnswerChoiceResource($choice);
    }

    public function show(QuestionBox $questionBox, AnswerChoice $answerChoice)
    {
        return new AnswerChoiceResource($answerChoice);
    }

    public function update(AnswerChoiceRequest $request, QuestionBox $questionBox, AnswerChoice $answerChoice)
    {
        $answerChoice->update([
            'choice_text'    => $request->choice_text,
            'choice_text_en' => $request->choice_text_en,
            'order'          => $request->order ?? $answerChoice->order,
            'status'         => $request->status ?? $answerChoice->status,
            'next_box_id'    => $request->next_box_id,
            'updated_by'     => $request->user()->user_id,
        ]);

        return new AnswerChoiceResource($answerChoice);
    }

    public function destroy(QuestionBox $questionBox, AnswerChoice $answerChoice)
    {
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
