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

        $box = DB::transaction(function () use ($request, $diagram) {
            Diagram::query()->whereKey($diagram->diagram_id)->lockForUpdate()->first();

            $maxFrameNumber = QuestionBox::query()
                ->where('diagram_id', $diagram->diagram_id)
                ->pluck('frame_number')
                ->filter(fn ($number) => is_numeric($number))
                ->map(fn ($number) => (float) $number)
                ->max();

            return QuestionBox::create([
                'box_id'              => $this->generateId(),
                'frame_number'        => $request->filled('frame_number')
                    ? $request->frame_number
                    : (string) ((int) floor($maxFrameNumber ?? 0) + 1),
                'question_text'       => $request->question_text,
                'question_text_en'    => $request->question_text_en,
                'question_image'      => $request->question_image,
                'question_type'       => $request->question_type ?? 'S',
                'answer_mode'         => $request->answer_mode ?? ($request->question_type === 'M' ? 'checklist' : null),
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
        });

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
            'frame_number'        => $request->exists('frame_number') ? $request->frame_number : $questionBox->frame_number,
            'question_text'       => $request->input('question_text', $questionBox->question_text),
            'question_text_en'    => $request->exists('question_text_en') ? $request->question_text_en : $questionBox->question_text_en,
            'question_image'      => $request->exists('question_image') ? $request->question_image : $questionBox->question_image,
            'question_type'       => $type,
            'answer_mode'         => $request->exists('answer_mode')
                ? $request->answer_mode
                : ($request->exists('question_type')
                    ? ($type === 'M' ? 'checklist' : ($questionBox->answer_mode === 'checklist' ? 'multiple' : $questionBox->answer_mode))
                    : $questionBox->answer_mode),
            'min_required'        => $type === 'M'
                ? ($request->exists('min_required') ? $request->min_required : $questionBox->min_required)
                : null,
            // Preserve checklist navigation while another answer type is active so
            // switching the type back does not silently discard existing links.
            'yes_next_box_id'     => $request->exists('yes_next_box_id') ? $request->yes_next_box_id : $questionBox->yes_next_box_id,
            'yes_next_diagram_id' => $request->exists('yes_next_diagram_id') ? $request->yes_next_diagram_id : $questionBox->yes_next_diagram_id,
            'no_next_box_id'      => $request->exists('no_next_box_id') ? $request->no_next_box_id : $questionBox->no_next_box_id,
            'no_next_diagram_id'  => $request->exists('no_next_diagram_id') ? $request->no_next_diagram_id : $questionBox->no_next_diagram_id,

            // ➕ เพิ่มการแก้ไขข้อมูลคอลัมน์ detail ในขั้นตอน Update
            'detail'              => $request->exists('detail') ? $request->detail : $questionBox->detail,

            'status'              => $request->status ?? $questionBox->status,
            'updated_by'          => $request->user()->user_id,
        ]);

        if ($request->boolean('sync_result_bindings')) {
            $this->syncResultBindingsForType($questionBox);
        }

        return new QuestionBoxResource($questionBox->load(['choices', 'yesNextBox', 'noNextBox', 'yesNextDiagram', 'noNextDiagram']));
    }

    public function destroy(Diagram $diagram, QuestionBox $questionBox)
    {
        abort_if(
            $questionBox->diagram_id !== $diagram->diagram_id,
            404,
            'ไม่พบกล่องคำถามในแผนภูมินี้'
        );

        DB::transaction(function () use ($diagram, $questionBox) {
            // Delete the selected box together with every downstream box reachable
            // from its choices/checklist branches. A plain FK nullOnDelete would only
            // disconnect children and leave them orphaned on the canvas.
            $diagramBoxes = QuestionBox::query()
                ->where('diagram_id', $diagram->diagram_id)
                ->get(['box_id', 'yes_next_box_id', 'no_next_box_id']);
            $diagramBoxIds = $diagramBoxes->pluck('box_id')->flip();
            $choiceTargets = DB::table('answer_choices')
                ->whereIn('box_id', $diagramBoxIds->keys())
                ->whereNotNull('next_box_id')
                ->get(['box_id', 'next_box_id'])
                ->groupBy('box_id');

            $targetsByBox = $diagramBoxes->mapWithKeys(function (QuestionBox $box) use ($choiceTargets) {
                $targets = collect([$box->yes_next_box_id, $box->no_next_box_id])
                    ->merge($choiceTargets->get($box->box_id, collect())->pluck('next_box_id'))
                    ->filter()
                    ->unique()
                    ->values();

                return [$box->box_id => $targets];
            });

            $boxIds = collect();
            $pendingBoxIds = [$questionBox->box_id];
            while ($pendingBoxId = array_pop($pendingBoxIds)) {
                if ($boxIds->contains($pendingBoxId) || !$diagramBoxIds->has($pendingBoxId)) {
                    continue;
                }

                $boxIds->push($pendingBoxId);
                foreach ($targetsByBox->get($pendingBoxId, collect()) as $targetBoxId) {
                    $pendingBoxIds[] = $targetBoxId;
                }
            }

            $choiceIds = DB::table('answer_choices')->whereIn('box_id', $boxIds)->pluck('choice_id');

            // เก็บ rule ที่ได้รับผลกระทบไว้ เพื่อลบเฉพาะ rule ที่ไม่เหลือเงื่อนไข
            $ruleIds = RuleCondition::query()
                ->whereIn('box_id', $boxIds)
                ->when(
                    $choiceIds->isNotEmpty(),
                    fn ($query) => $query->orWhereIn('choice_id', $choiceIds)
                )
                ->pluck('rule_id')
                ->unique();

            // assessment_answers ใช้ FK แบบ restrict จึงต้องลบก่อนตัวเลือกและกล่อง
            DB::table('assessment_answers')->whereIn('box_id', $boxIds)->delete();
            RuleCondition::query()->whereIn('box_id', $boxIds)->delete();
            DB::table('answer_choices')->whereIn('box_id', $boxIds)->delete();
            QuestionBox::query()->whereIn('box_id', $boxIds)->delete();

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

    /**
     * Keep terminal results attached to the equivalent yes/no branch when the
     * answer type changes. Calling this on every save also repairs boxes that
     * were changed before this conversion existed.
     */
    private function syncResultBindingsForType(QuestionBox $questionBox): void
    {
        $branchChoices = $questionBox->choices()
            ->orderBy('order')
            ->limit(2)
            ->get();
        $yesChoice = $branchChoices->get(0);
        $noChoice = $branchChoices->get(1);

        if ($questionBox->question_type === 'M') {
            foreach ([['choice' => $yesChoice, 'outcome' => 'yes'], ['choice' => $noChoice, 'outcome' => 'no']] as $branch) {
                if (! $branch['choice']) {
                    continue;
                }

                DiagnosisRule::query()
                    ->whereNull('threshold_outcome')
                    ->whereHas('conditions', fn ($query) => $query
                        ->where('box_id', $questionBox->box_id)
                        ->where('choice_id', $branch['choice']->choice_id))
                    ->update([
                        'threshold_box_id' => $questionBox->box_id,
                        'threshold_outcome' => $branch['outcome'],
                    ]);
            }

            return;
        }

        DiagnosisRule::query()
            ->where('threshold_box_id', $questionBox->box_id)
            ->whereNotNull('threshold_outcome')
            ->get()
            ->each(function (DiagnosisRule $rule) use ($questionBox, $yesChoice, $noChoice) {
                $choice = $rule->threshold_outcome === 'no' ? $noChoice : $yesChoice;
                if (! $choice) {
                    return;
                }

                RuleCondition::query()
                    ->where('rule_id', $rule->rule_id)
                    ->where('box_id', $questionBox->box_id)
                    ->update(['choice_id' => $choice->choice_id]);

                $rule->update([
                    'threshold_box_id' => null,
                    'threshold_outcome' => null,
                ]);
            });
    }

    private function generateId(): string
    {
        $last = QuestionBox::max('box_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
