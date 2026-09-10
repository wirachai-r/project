<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\AssessmentResource;
use App\Http\Resources\Client\AssessmentResultResource;
use App\Models\AiClarificationChoice;
use App\Models\AiClarificationSession;
use App\Models\AnswerChoice;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentResult;
use App\Models\DiagnosisRule;
use App\Models\Diagram;
use App\Models\MainSymptom;
use App\Models\QuestionBox;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @tags Client AssessmentController
 */
class AssessmentController extends Controller
{
    public function pending(Request $request)
    {
        $validated = $request->validate([
            'symptom_id' => 'nullable|exists:main_symptoms,symptom_id',
        ]);
        $user = $request->user('sanctum');
        $sessionToken = (string) $request->header('X-Session-Token');

        if (! $user && $sessionToken === '') {
            return response()->json(['data' => null]);
        }

        $assessment = Assessment::query()
            ->with('symptom:symptom_id,symptom_name')
            ->when(
                $validated['symptom_id'] ?? null,
                fn ($query, $symptomId) => $query->where('symptom_id', $symptomId),
            )
            ->where('assessment_status', 'P')
            ->when(
                $user,
                fn ($query) => $query->where('user_id', $user->user_id),
                fn ($query) => $query->whereNull('user_id')->where('session_token', $sessionToken),
            )
            ->latest('started_at')
            ->first();

        if (! $assessment) {
            return response()->json(['data' => null]);
        }

        $currentBox = QuestionBox::find($assessment->current_box_id)
            ?? $assessment->diagram?->entryBox;

        return response()->json(['data' => [
            'assessment_id' => $assessment->id,
            'symptom_id' => $assessment->symptom_id,
            'symptom_name' => $assessment->symptom?->symptom_name,
            'diagram_id' => $assessment->diagram_id,
            'started_at' => $assessment->started_at,
            'current_box' => $this->formatBox($currentBox),
            'selected_choice_ids' => $currentBox
                ? $assessment->answers()->where('box_id', $currentBox->box_id)->pluck('choice_id')->values()
                : [],
        ]]);
    }

    public function start(Request $request)
    {
        $request->validate([
            'symptom_id' => 'required|exists:main_symptoms,symptom_id',
            'diagram_id' => 'nullable|exists:diagrams,diagram_id', // เพิ่ม
        ]);

        $symptom = MainSymptom::where('symptom_id', $request->symptom_id)
            ->where('status', '1')
            ->firstOrFail();

        // ถ้าส่ง diagram_id มาด้วย → ใช้เลย
        if ($request->diagram_id) {
            $diagram = Diagram::where('diagram_id', $request->diagram_id)
                ->whereHas('symptoms', fn ($q) => $q->where('main_symptoms.symptom_id', $symptom->symptom_id))
                ->where('status', '1')
                ->whereNotNull('entry_box_id')
                ->firstOrFail();

            return $this->createAssessment($request, $symptom, $diagram);
        }

        // ยังไม่ส่ง diagram_id → หา diagrams ที่ผูกกับ symptom นี้
        $diagrams = Diagram::whereHas('symptoms', fn ($q) => $q->where('main_symptoms.symptom_id', $symptom->symptom_id))
            ->where('status', '1')
            ->whereNotNull('entry_box_id')
            ->orderBy('diagram_id')
            ->get();

        abort_if($diagrams->isEmpty(), 404, 'ไม่พบแผนภูมิสำหรับอาการนี้');

        // มีแค่อันเดียว → เริ่มเลยไม่ต้องให้เลือก
        if ($diagrams->count() === 1) {
            return $this->createAssessment($request, $symptom, $diagrams->first());
        }

        // มีหลายอัน → ให้ user เลือก
        return response()->json([
            'choose_diagram' => true,
            'symptom' => ['symptom_id' => $symptom->symptom_id, 'symptom_name' => $symptom->symptom_name],
            'diagrams' => $diagrams->map(fn ($d) => [
                'diagram_id' => $d->diagram_id,
                'diagram_name' => $d->diagram_name,
            ]),
        ]);
    }

    // แยก logic สร้าง assessment ออกมา
    private function createAssessment(
        Request $request,
        MainSymptom $symptom,
        Diagram $diagram,
        ?Assessment $parent = null,
        ?QuestionBox $firstBox = null,
    ): JsonResponse {
        $user = $request->user('sanctum');
        $sessionToken = $user ? null : ($request->header('X-Session-Token') ?: Str::random(64));

        $assessment = Assessment::create([
            'parent_assessment_id' => $parent?->id,
            'user_id' => $user?->user_id,
            'session_token' => $sessionToken,
            'symptom_id' => $symptom->symptom_id,
            'diagram_id' => $diagram->diagram_id,
            'current_box_id' => ($firstBox ?? $diagram->entryBox)->box_id,
            'assessment_status' => 'P',
            'started_at' => now(),
        ]);

        return response()->json([
            'assessment_id' => $assessment->id,
            'diagram_id' => $diagram->diagram_id,
            'first_box' => $this->formatBox($firstBox ?? $diagram->entryBox),
            'session_token' => $sessionToken,
        ], 201);
    }

    public function continueAssessment(Request $request, Assessment $assessment)
    {
        $this->authorizeAssessment($request, $assessment);
        abort_if($assessment->assessment_status !== 'C', 422, 'การประเมินต้นทางยังไม่เสร็จสิ้น');

        $validated = $request->validate([
            'diagram_id' => 'required|exists:diagrams,diagram_id',
            'target_box_id' => 'nullable|exists:question_boxes,box_id',
        ]);

        $suggestions = DB::table('assessment_results')
            ->join('rule_next_diagrams', 'rule_next_diagrams.rule_id', '=', 'assessment_results.rule_id')
            ->where('assessment_results.assessment_id', $assessment->id)
            ->where('rule_next_diagrams.diagram_id', $validated['diagram_id'])
            ->get(['rule_next_diagrams.target_box_id']);
        abort_if($suggestions->isEmpty(), 422, 'แผนภูมินี้ไม่ได้ถูกแนะนำจากผลการประเมิน');

        $targetBoxId = $validated['target_box_id'] ?? $suggestions->pluck('target_box_id')->filter()->first();
        if (! empty($validated['target_box_id'])) {
            abort_unless(
                $suggestions->pluck('target_box_id')->contains($validated['target_box_id']),
                422,
                'กรอบคำถามนี้ไม่ได้ถูกกำหนดไว้สำหรับแผนภูมิที่แนะนำ'
            );
        }

        $diagram = Diagram::where('diagram_id', $validated['diagram_id'])
            ->where('status', '1')
            ->whereNotNull('entry_box_id')
            ->firstOrFail();

        $firstBox = $targetBoxId
            ? QuestionBox::query()
                ->whereKey($targetBoxId)
                ->where('diagram_id', $diagram->diagram_id)
                ->firstOrFail()
            : null;

        return $this->createAssessment($request, $assessment->symptom, $diagram, $assessment, $firstBox);
    }

    public function answer(Request $request, Assessment $assessment)
    {
        $this->authorizeAssessment($request, $assessment);

        abort_if($assessment->assessment_status !== 'P', 422, 'assessment นี้ไม่ได้อยู่ระหว่างดำเนินการ');

        $request->validate([
            'answers' => 'present|array',
            'answers.*.box_id' => 'required|exists:question_boxes,box_id',
            'answers.*.choice_id' => 'required|exists:answer_choices,choice_id',
            'box_id' => 'required_if:none_selected,true|nullable|exists:question_boxes,box_id',
            'none_selected' => 'sometimes|boolean',
        ]);

        $noneSelected = $request->boolean('none_selected');
        abort_if(! $noneSelected && count($request->answers) < 1, 422, 'กรุณาเลือกคำตอบอย่างน้อย 1 ข้อ');

        $currentBoxId = $noneSelected
            ? $request->input('box_id')
            : $request->answers[0]['box_id'];
        $currentBox = QuestionBox::findOrFail($currentBoxId);

        abort_if(
            $noneSelected && ($currentBox->question_type !== 'M' || (int) $currentBox->min_required !== 1),
            422,
            'ตัวเลือกไม่ใช่ทั้งหมดใช้ได้เฉพาะคำถามแบบเลือกหลายข้อที่กำหนดขั้นต่ำ 1 ข้อ'
        );

        abort_if(
            collect($request->answers)->contains(fn ($answer) => $answer['box_id'] !== $currentBoxId),
            422,
            'คำตอบทั้งหมดต้องเป็นของคำถามเดียวกัน'
        );

        // Re-answering after going back must replace the old choices, including
        // clearing them when the user selects "none of the above".
        AssessmentAnswer::where('assessment_id', $assessment->id)
            ->where('box_id', $currentBoxId)
            ->delete();

        foreach ($request->answers as $ans) {
            AssessmentAnswer::create([
                'assessment_id' => $assessment->id,
                'box_id' => $ans['box_id'],
                'choice_id' => $ans['choice_id'],
            ]);
        }

        $clarificationSession = AiClarificationSession::query()
            ->where('assessment_id', $assessment->id)
            ->where('box_id', $currentBoxId)
            ->where('status', 'active')
            ->first();
        if ($clarificationSession) {
            $submittedChoiceIds = collect($request->answers)->pluck('choice_id');
            $mappedChoice = AiClarificationChoice::query()
                ->whereHas('question', fn ($query) => $query->where('session_id', $clarificationSession->id))
                ->whereIn('maps_to_choice_id', $submittedChoiceIds)
                ->latest('id')
                ->first();
            $clarificationSession->update([
                'status' => 'resolved',
                'resolved_to' => in_array($mappedChoice?->maps_to, ['yes', 'no'], true)
                    ? $mappedChoice->maps_to
                    : null,
                'resolved_at' => now(),
            ]);
        }

        // ---- กรณี Checklist + Threshold (question_type = M) ----
        if ($currentBox->question_type === 'M') {
            $selectedCount = $noneSelected ? 0 : collect($request->answers)
                ->where('box_id', $currentBoxId)
                ->count();

            $passed = $selectedCount >= ($currentBox->min_required ?? 1);

            $nextBoxId = $passed ? $currentBox->yes_next_box_id : $currentBox->no_next_box_id;
            $nextDiagramId = $passed ? $currentBox->yes_next_diagram_id : $currentBox->no_next_diagram_id;

            if ($nextDiagramId) {
                $nextDiagram = Diagram::with('entryBox')->find($nextDiagramId);
                abort_if(! $nextDiagram || ! $nextDiagram->entry_box_id, 422, 'diagram ถัดไปยังไม่มีกรอบเริ่มต้น');
                $assessment->update(['diagram_id' => $nextDiagram->diagram_id]);
                $assessment->update(['current_box_id' => $nextDiagram->entry_box_id]);

                return response()->json([
                    'status' => 'next',
                    'next_box' => $this->formatBox($nextDiagram->entryBox),
                ]);
            }

            if ($nextBoxId) {
                $nextBox = QuestionBox::with(['choices' => fn ($q) => $q->where('status', '1')->orderBy('order')])
                    ->find($nextBoxId);
                $assessment->update(['current_box_id' => $nextBox->box_id]);

                return response()->json([
                    'status' => 'next',
                    'next_box' => $this->formatBox($nextBox),
                ]);
            }

            $results = $this->evaluate($assessment, $passed ? 'yes' : 'no', $currentBoxId);

            return response()->json(['status' => 'completed', 'results' => $results]);
        }

        // ---- กรณี Single choice (type = S) ใช้ logic เดิม ----
        $lastAnswer = collect($request->answers)->last();
        $lastChoice = AnswerChoice::find($lastAnswer['choice_id']);

        if ($lastChoice?->next_diagram_id) {
            $nextDiagram = Diagram::with('entryBox')->find($lastChoice->next_diagram_id);
            abort_if(! $nextDiagram || ! $nextDiagram->entry_box_id, 422, 'diagram ถัดไปยังไม่มีกรอบเริ่มต้น');
            $assessment->update([
                'diagram_id' => $nextDiagram->diagram_id,
                'current_box_id' => $nextDiagram->entry_box_id,
            ]);

            return response()->json([
                'status' => 'next',
                'next_box' => $this->formatBox($nextDiagram->entryBox),
            ]);
        }

        if ($lastChoice?->next_box_id) {
            $nextBox = QuestionBox::with([
                'choices' => fn ($q) => $q->where('status', '1')->orderBy('order'),
            ])->find($lastChoice->next_box_id);
            $assessment->update(['current_box_id' => $nextBox->box_id]);

            return response()->json([
                'status' => 'next',
                'next_box' => $this->formatBox($nextBox),
            ]);
        }

        $results = $this->evaluate($assessment);

        return response()->json([
            'status' => 'completed',
            'results' => $results,
        ]);
    }

    public function abandon(Request $request, Assessment $assessment)
    {
        $this->authorizeAssessment($request, $assessment);

        if ($assessment->assessment_status === 'P') {
            $assessment->update(['assessment_status' => 'A']);
        }

        return response()->json([
            'message' => 'บันทึกการออกจากการประเมินแล้ว',
            'assessment_status' => $assessment->fresh()->assessment_status,
        ]);
    }

    public function result(Request $request, Assessment $assessment)
    {
        $this->authorizeAssessment($request, $assessment);

        abort_if($assessment->assessment_status !== 'C', 422, 'assessment ยังไม่เสร็จสิ้น');

        $assessment->load(['results.diseases.category', 'results.rule.nextDiagrams']);

        return response()->json([
            'assessment_id' => $assessment->id,
            'completed_at' => $assessment->completed_at,
            'results' => AssessmentResultResource::collection($assessment->results),
        ]);
    }

    public function history(Request $request)
    {
        $assessments = Assessment::query()
            ->with(['symptom', 'results', 'healthEpisode.symptoms.symptom'])
            ->where('user_id', $request->user()->user_id)
            ->where('is_saved', true)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return AssessmentResource::collection($assessments);
    }

    public function save(Request $request, Assessment $assessment)
    {
        $user = $request->user('sanctum');
        abort_unless($user, 401);

        if ($assessment->user_id === null) {
            $this->authorizeAssessment($request, $assessment);
            $assessment->update([
                'user_id' => $user->user_id,
                'session_token' => null,
            ]);
        } else {
            abort_if($assessment->user_id !== $user->user_id, 403);
        }
        abort_if($assessment->assessment_status !== 'C', 422, 'การประเมินยังไม่เสร็จสิ้น');

        $assessment->update(['is_saved' => true]);

        return response()->json([
            'message' => 'บันทึกผลการประเมินลงในประวัติเรียบร้อยแล้ว',
            'assessment_id' => $assessment->id,
            'is_saved' => true,
        ]);
    }

    public function show(Request $request, Assessment $assessment)
    {
        $this->authorizeAssessment($request, $assessment);

        return new AssessmentResource(
            $assessment->load(['symptom', 'answers', 'results.diseases', 'healthEpisode.symptoms.symptom'])
        );
    }

    // --- Decision Engine ---
    private function evaluate(Assessment $assessment, ?string $thresholdOutcome = null, ?string $thresholdBoxId = null): array
    {
        $answers = AssessmentAnswer::where('assessment_id', $assessment->id)->get();
        $answeredChoices = $answers->pluck('choice_id')->toArray();

        $rules = DiagnosisRule::with(['conditions', 'diseases', 'nextDiagrams'])
            ->where('diagram_id', $assessment->diagram_id)
            ->where('status', '1')
            ->get();

        $matchedRules = [];

        foreach ($rules as $rule) {
            if ($rule->threshold_outcome !== null) {
                if ($rule->threshold_box_id === $thresholdBoxId && $rule->threshold_outcome === $thresholdOutcome) {
                    $matchedRules[] = $rule;
                }

                continue;
            }
            $conditions = $rule->conditions->where('status', '1');

            if ($conditions->isEmpty()) {
                continue;
            }

            $matched = false;
            $first = true;

            foreach ($conditions as $condition) {
                $conditionMet = in_array($condition->choice_id, $answeredChoices);

                if ($first) {
                    $matched = $conditionMet;
                    $first = false;
                } elseif ($condition->logic_operator === 'OR') {
                    $matched = $matched || $conditionMet;
                } else {
                    $matched = $matched && $conditionMet;
                }
            }

            if ($matched) {
                $matchedRules[] = $rule;
            }
        }

        $urgencyRank = ['R' => 5, 'P' => 4, 'Y' => 3, 'G' => 2, 'W' => 1];
        usort(
            $matchedRules,
            fn ($a, $b) => ($urgencyRank[$b->urgency_level] ?? 0) - ($urgencyRank[$a->urgency_level] ?? 0)
        );

        $savedResults = [];
        foreach ($matchedRules as $rule) {
            $result = AssessmentResult::create([
                'assessment_id' => $assessment->id,
                'urgency_level' => $rule->urgency_level,
                'should_see_doctor' => in_array($rule->urgency_level, ['R', 'P']) ? 'Y' : 'N',
                'recommendation' => $rule->note,
                'rule_id' => $rule->rule_id,
            ]);

            foreach ($rule->diseases as $i => $disease) {
                $result->diseases()->attach($disease->disease_id, [
                    'display_order' => $disease->pivot->display_order ?? $i,
                ]);
            }

            $savedResults[] = $result;
        }

        // ถ้าไม่มี rule ไหน match เลย (หรือ match แต่ data ไม่สมบูรณ์ทั้งหมด)
        // ก็ยังต้อง mark completed ไว้ แต่ results จะเป็น array ว่าง
        $assessment->update([
            'assessment_status' => 'C',
            'current_box_id' => null,
            'completed_at' => now(),
        ]);

        // ใช้ Eloquent Collection แทน collect() ธรรมดา เพราะ load() เป็น method
        // ของ Illuminate\Database\Eloquent\Collection เท่านั้น ไม่ใช่ของ
        // Illuminate\Support\Collection ที่ collect() สร้างให้
        return AssessmentResultResource::collection(
            EloquentCollection::make($savedResults)->load(['diseases', 'rule.nextDiagrams'])
        )->resolve();
    }

    // --- Helpers ---
    private function formatBox($box): array
    {
        return [
            'box_id' => $box->box_id,
            'question_text' => $box->question_text,
            'question_image' => $box->question_image,
            'detail' => $box->detail,
            'question_type' => $box->question_type,
            'min_required' => $box->min_required,
            'choices' => $box->choices()
                ->where('status', '1')
                ->orderBy('order')
                ->get(['choice_id', 'choice_text', 'choice_text_en', 'choice_image', 'order']),
        ];
    }

    private function authorizeAssessment(Request $request, Assessment $assessment): void
    {
        $user = $request->user('sanctum');

        if ($user && $assessment->user_id === $user->user_id) {
            return;
        }

        $sessionToken = (string) $request->header('X-Session-Token');
        abort_if(
            $assessment->session_token === null
                || $sessionToken === ''
                || ! hash_equals($assessment->session_token, $sessionToken),
            403
        );
    }
}
