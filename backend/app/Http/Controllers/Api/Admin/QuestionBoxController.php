<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuestionBoxRequest;
use App\Http\Resources\Admin\QuestionBoxResource;
use App\Models\Diagram;
use App\Models\DiagnosisRule;
use App\Models\QuestionBox;
use App\Models\RuleCondition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @tags Admin QuestionBoxController
 */

class QuestionBoxController extends Controller
{
    public function index(Request $request, Diagram $diagram)
    {
        $boxes = QuestionBox::query()
            ->with(['choices', 'yesNextBox', 'noNextBox', 'yesNextDiagram', 'noNextDiagram'])
            ->where('diagram_id', $diagram->diagram_id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('box_id')
            ->get();

        return QuestionBoxResource::collection($boxes);
    }

    public function store(QuestionBoxRequest $request, Diagram $diagram)
    {
        $this->validateChecklistFields($request);

        $box = QuestionBox::create([
            'box_id'              => $this->generateId(),
            'question_text'       => $request->question_text,
            'question_text_en'    => $request->question_text_en,
            'question_image'      => $request->question_image,
            'question_type'       => $request->question_type ?? 'S',
            'min_required'        => $request->question_type === 'M' ? $request->min_required : null,
            'yes_next_box_id'     => $request->question_type === 'M' ? $request->yes_next_box_id : null,
            'yes_next_diagram_id' => $request->question_type === 'M' ? $request->yes_next_diagram_id : null,
            'no_next_box_id'      => $request->question_type === 'M' ? $request->no_next_box_id : null,
            'no_next_diagram_id'  => $request->question_type === 'M' ? $request->no_next_diagram_id : null,

            // ➕ เพิ่มการเก็บข้อมูลคอลัมน์ detail ในขั้นตอน Create
            'detail'              => $request->detail,

            'status'              => $request->status ?? '1',
            'diagram_id'          => $diagram->diagram_id,
            'created_by'          => $request->user()->user_id,
            'updated_by'          => $request->user()->user_id,
        ]);

        return new QuestionBoxResource($box->load(['choices', 'yesNextBox', 'noNextBox', 'yesNextDiagram', 'noNextDiagram']));
    }

    public function show(Diagram $diagram, QuestionBox $questionBox)
    {
        return new QuestionBoxResource(
            $questionBox->load(['choices', 'yesNextBox', 'noNextBox', 'yesNextDiagram', 'noNextDiagram'])
        );
    }

    public function update(QuestionBoxRequest $request, Diagram $diagram, QuestionBox $questionBox)
    {
        $this->validateChecklistFields($request);

        $type = $request->question_type ?? $questionBox->question_type;

        $questionBox->update([
            'question_text'       => $request->input('question_text', $questionBox->question_text),
            'question_text_en'    => $request->exists('question_text_en') ? $request->question_text_en : $questionBox->question_text_en,
            'question_image'      => $request->exists('question_image') ? $request->question_image : $questionBox->question_image,
            'question_type'       => $type,
            'min_required'        => $type === 'M'
                ? ($request->exists('min_required') ? $request->min_required : $questionBox->min_required)
                : null,
            'yes_next_box_id'     => $type === 'M'
                ? ($request->exists('yes_next_box_id') ? $request->yes_next_box_id : $questionBox->yes_next_box_id)
                : null,
            'yes_next_diagram_id' => $type === 'M'
                ? ($request->exists('yes_next_diagram_id') ? $request->yes_next_diagram_id : $questionBox->yes_next_diagram_id)
                : null,
            'no_next_box_id'      => $type === 'M'
                ? ($request->exists('no_next_box_id') ? $request->no_next_box_id : $questionBox->no_next_box_id)
                : null,
            'no_next_diagram_id'  => $type === 'M'
                ? ($request->exists('no_next_diagram_id') ? $request->no_next_diagram_id : $questionBox->no_next_diagram_id)
                : null,

            // ➕ เพิ่มการแก้ไขข้อมูลคอลัมน์ detail ในขั้นตอน Update
            'detail'              => $request->exists('detail') ? $request->detail : $questionBox->detail,

            'status'              => $request->status ?? $questionBox->status,
            'updated_by'          => $request->user()->user_id,
        ]);

        return new QuestionBoxResource($questionBox->load(['choices', 'yesNextBox', 'noNextBox', 'yesNextDiagram', 'noNextDiagram']));
    }

    public function destroy(Diagram $diagram, QuestionBox $questionBox)
    {
        abort_if(
            $questionBox->diagram_id !== $diagram->diagram_id,
            404,
            'ไม่พบกล่องคำถามในแผนภูมินี้'
        );

        DB::transaction(function () use ($questionBox) {
            $choiceIds = $questionBox->choices()->pluck('choice_id');

            // เก็บ rule ที่ได้รับผลกระทบไว้ เพื่อลบเฉพาะ rule ที่ไม่เหลือเงื่อนไข
            $ruleIds = RuleCondition::query()
                ->where('box_id', $questionBox->box_id)
                ->when(
                    $choiceIds->isNotEmpty(),
                    fn ($query) => $query->orWhereIn('choice_id', $choiceIds)
                )
                ->pluck('rule_id')
                ->unique();

            // assessment_answers ใช้ FK แบบ restrict จึงต้องลบก่อนตัวเลือกและกล่อง
            $questionBox->assessmentAnswers()->delete();
            $questionBox->ruleConditions()->delete();
            $questionBox->choices()->delete();
            $questionBox->delete();

            // ลบผลลัพธ์ปลายทางที่ไม่มีเงื่อนไขเหลือ และยังไม่ถูกใช้ในประวัติการประเมิน
            DiagnosisRule::query()
                ->whereIn('rule_id', $ruleIds)
                ->whereDoesntHave('conditions')
                ->whereDoesntHave('assessmentResults')
                ->get()
                ->each(function (DiagnosisRule $rule) {
                    $rule->diseases()->detach();
                    $rule->delete();
                });
        });

        return response()->json(['message' => 'ลบกล่องคำถามและข้อมูลที่เกี่ยวข้องสำเร็จ']);
    }

    /**
     * ตรวจสอบ field ที่เกี่ยวกับ checklist (question_type = M)
     */
    private function validateChecklistFields(Request $request): void
    {
        if ($request->question_type !== 'M') {
            return;
        }

        // yes_next_box_id และ yes_next_diagram_id ใช้พร้อมกันไม่ได้
        abort_if(
            $request->yes_next_box_id && $request->yes_next_diagram_id,
            422,
            'ไม่สามารถระบุ yes_next_box_id และ yes_next_diagram_id พร้อมกันได้'
        );

        // no_next_box_id และ no_next_diagram_id ใช้พร้อมกันไม่ได้
        abort_if(
            $request->no_next_box_id && $request->no_next_diagram_id,
            422,
            'ไม่สามารถระบุ no_next_box_id และ no_next_diagram_id พร้อมกันได้'
        );

        // ป้องกัน self-loop วนกลับหาตัวเอง
        if ($request->yes_next_box_id) {
            abort_if(
                $request->yes_next_box_id === $request->route('questionBox')?->box_id,
                422,
                'ไม่สามารถเชื่อมโยงกลับไปยังกล่องคำถามเดิมได้ (ใช่)'
            );
        }
        if ($request->no_next_box_id) {
            abort_if(
                $request->no_next_box_id === $request->route('questionBox')?->box_id,
                422,
                'ไม่สามารถเชื่อมโยงกลับไปยังกล่องคำถามเดิมได้ (ไม่ใช่)'
            );
        }

        // ตรวจ next_diagram ต้องมี entry_box_id แล้ว
        foreach (['yes_next_diagram_id', 'no_next_diagram_id'] as $field) {
            if ($request->$field) {
                $diagram = Diagram::where('diagram_id', $request->$field)->first();
                abort_if(!$diagram, 422, 'ไม่พบ diagram ปลายทางที่เลือก');
                abort_if(
                    !$diagram->entry_box_id,
                    422,
                    'diagram ปลายทางยังไม่มีกรอบคำถามเริ่มต้น กรุณาตั้งค่าก่อนเชื่อมโยง'
                );
            }
        }
    }

    private function generateId(): string
    {
        $last = QuestionBox::max('box_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
