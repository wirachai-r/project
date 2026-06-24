<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\AssessmentResource;
use App\Http\Resources\Client\AssessmentResultResource;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentResult;
use App\Models\AnswerChoice;
use App\Models\Diagram;
use App\Models\DiagnosisRule;
use App\Models\MainSymptom;
use App\Models\QuestionBox;
use Illuminate\Http\Request;

/**
 * @tags Client AssessmentController
 */

class AssessmentController extends Controller
{
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
                ->whereHas('symptoms', fn($q) => $q->where('main_symptoms.symptom_id', $symptom->symptom_id))
                ->where('status', '1')
                ->whereNotNull('entry_box_id')
                ->firstOrFail();

            return $this->createAssessment($request, $symptom, $diagram);
        }

        // ยังไม่ส่ง diagram_id → หา diagrams ที่ผูกกับ symptom นี้
        $diagrams = Diagram::whereHas('symptoms', fn($q) => $q->where('main_symptoms.symptom_id', $symptom->symptom_id))
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
            'symptom'        => ['symptom_id' => $symptom->symptom_id, 'symptom_name' => $symptom->symptom_name],
            'diagrams'       => $diagrams->map(fn($d) => [
                'diagram_id'   => $d->diagram_id,
                'diagram_name' => $d->diagram_name,
            ]),
        ]);
    }

    // แยก logic สร้าง assessment ออกมา
    private function createAssessment(Request $request, MainSymptom $symptom, Diagram $diagram): \Illuminate\Http\JsonResponse
    {
        $assessment = Assessment::create([
            'user_id'           => $request->user()?->user_id,
            'session_token'     => $request->user() ? null : $request->header('X-Session-Token'),
            'symptom_id'        => $symptom->symptom_id,
            'diagram_id'        => $diagram->diagram_id,
            'assessment_status' => 'P',
            'started_at'        => now(),
        ]);

        return response()->json([
            'assessment_id' => $assessment->id,
            'diagram_id'    => $diagram->diagram_id,
            'first_box'     => $this->formatBox($diagram->entryBox),
        ], 201);
    }

    public function answer(Request $request, Assessment $assessment)
    {
        $this->authorizeAssessment($request, $assessment);

        abort_if($assessment->assessment_status === 'C', 422, 'assessment นี้เสร็จสิ้นแล้ว');

        $request->validate([
            'answers'             => 'required|array|min:1',
            'answers.*.box_id'    => 'required|exists:question_boxes,box_id',
            'answers.*.choice_id' => 'required|exists:answer_choices,choice_id',
        ]);

        foreach ($request->answers as $ans) {
            AssessmentAnswer::updateOrCreate(
                [
                    'assessment_id' => $assessment->id,
                    'box_id'        => $ans['box_id'],
                    'choice_id'     => $ans['choice_id'],
                ],
                []
            );
        }

        $lastAnswer = collect($request->answers)->last();
        $lastChoice = AnswerChoice::find($lastAnswer['choice_id']);

        // กรณีกระโดดไป diagram อื่น
        if ($lastChoice?->next_diagram_id) {
            $nextDiagram = Diagram::with('entryBox')->find($lastChoice->next_diagram_id);

            abort_if(!$nextDiagram || !$nextDiagram->entry_box_id, 422, 'diagram ถัดไปยังไม่มีกรอบเริ่มต้น');

            // อัปเดต diagram_id ใน assessment ให้ชี้ไป diagram ใหม่
            $assessment->update(['diagram_id' => $nextDiagram->diagram_id]);

            return response()->json([
                'status'   => 'next',
                'next_box' => $this->formatBox($nextDiagram->entryBox),
            ]);
        }

        // กรณีไปกรอบถัดไปใน diagram เดิม
        if ($lastChoice?->next_box_id) {
            $nextBox = QuestionBox::with([
                'choices' => fn($q) => $q->where('status', '1')->orderBy('order')
            ])->find($lastChoice->next_box_id);

            return response()->json([
                'status'   => 'next',
                'next_box' => $this->formatBox($nextBox),
            ]);
        }

        // จบ flow → evaluate
        $results = $this->evaluate($assessment);

        return response()->json([
            'status'  => 'completed',
            'results' => $results,
        ]);
    }

    public function result(Request $request, Assessment $assessment)
    {
        $this->authorizeAssessment($request, $assessment);

        abort_if($assessment->assessment_status !== 'C', 422, 'assessment ยังไม่เสร็จสิ้น');

        $assessment->load('results.disease.category');

        return response()->json([
            'assessment_id' => $assessment->id,
            'completed_at'  => $assessment->completed_at,
            'results'       => AssessmentResultResource::collection($assessment->results),
        ]);
    }

    public function history(Request $request)
    {
        $assessments = Assessment::query()
            ->with(['symptom', 'results'])
            ->where('user_id', $request->user()->user_id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return AssessmentResource::collection($assessments);
    }

    public function show(Request $request, Assessment $assessment)
    {
        $this->authorizeAssessment($request, $assessment);

        return new AssessmentResource(
            $assessment->load(['symptom', 'answers', 'results.disease'])
        );
    }

    // --- Decision Engine ---
    private function evaluate(Assessment $assessment): array
    {
        $answers = AssessmentAnswer::where('assessment_id', $assessment->id)->get();
        $answeredChoices = $answers->pluck('choice_id')->toArray();

        // เอา rules จาก diagram_id ปัจจุบัน (อาจเปลี่ยนถ้าข้าม diagram)
        $rules = DiagnosisRule::with('conditions')
            ->where('diagram_id', $assessment->diagram_id)
            ->where('status', '1')
            ->get();

        $matchedRules = [];

        foreach ($rules as $rule) {
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
            fn($a, $b) => ($urgencyRank[$b->urgency_level] ?? 0) - ($urgencyRank[$a->urgency_level] ?? 0)
        );

        $savedResults = [];
        foreach ($matchedRules as $rule) {
            $savedResults[] = AssessmentResult::create([
                'assessment_id'     => $assessment->id,
                'urgency_level'     => $rule->urgency_level,
                'should_see_doctor' => in_array($rule->urgency_level, ['R', 'P']) ? 'Y' : 'N',
                'recommendation'    => $rule->description,
                'rule_id'           => $rule->rule_id,
                'disease_id'        => $rule->disease_id,
            ]);
        }

        $assessment->update([
            'assessment_status' => 'C',
            'completed_at'      => now(),
        ]);

        return AssessmentResultResource::collection(collect($savedResults))->resolve();
    }

    // --- Helpers ---
    private function formatBox($box): array
    {
        return [
            'box_id'         => $box->box_id,
            'question_text'  => $box->question_text,
            'question_image' => $box->question_image,
            'question_type'  => $box->question_type,
            'choices'        => $box->choices()
                ->where('status', '1')
                ->orderBy('order')
                ->get(['choice_id', 'choice_text', 'choice_text_en', 'choice_image', 'order']),
        ];
    }

    private function authorizeAssessment(Request $request, Assessment $assessment): void
    {
        $user = $request->user();

        if ($user) {
            abort_if($assessment->user_id !== $user->user_id, 403);
        } else {
            $sessionToken = $request->header('X-Session-Token');
            abort_if($assessment->session_token !== $sessionToken, 403);
        }
    }
}
