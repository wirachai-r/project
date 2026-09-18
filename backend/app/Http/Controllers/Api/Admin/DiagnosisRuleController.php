<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiagnosisRuleRequest;
use App\Http\Resources\Admin\DiagnosisRuleResource;
use App\Models\DiagnosisRule;
use App\Models\QuestionBox;
use App\Models\RuleCondition;
use App\Support\AdminTableQuery;
use Illuminate\Http\Request;

/**
 * @tags Admin DiagnosisRuleController
 */
class DiagnosisRuleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $rules = DiagnosisRule::query()
            ->with(['diagram', 'diseases', 'conditions.box', 'conditions.choice', 'nextDiagrams'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->diagram_id, fn ($q) => $q->where('diagram_id', $request->diagram_id))
            ->when($request->disease_id, fn ($q) => $q->whereHas('diseases', fn ($q) => $q->where('diseases.disease_id', $request->disease_id)))
            ->when($request->urgency_level, fn ($q) => $q->where('urgency_level', $request->urgency_level))
            ->tap(fn ($q) => AdminTableQuery::fuzzySearch($q, $request->search, 'rule_id', ['medical_reference', 'time_frame', 'time_frame_en', 'note', 'note_en']))
            ->when(
                in_array($request->sort_by, ['urgency_level', 'created_at']),
                function ($q) use ($request) {
                    $direction = $request->sort_direction === 'asc' ? 'asc' : 'desc';

                    if ($request->sort_by === 'created_at') {
                        AdminTableQuery::orderByCreatedAt($q, $direction, 'rule_id');
                    } else {
                        $q->orderBy('urgency_level', $direction);
                    }
                },
                fn ($q) => $q->orderBy('rule_id', 'desc'),
            )
            ->orderBy('rule_id', 'desc')
            ->paginate($perPage);

        return DiagnosisRuleResource::collection($rules);
    }

    public function store(DiagnosisRuleRequest $request)
    {
        $this->validateNextDiagramTargets($request->input('next_diagrams', []));

        $rule = DiagnosisRule::create([
            'rule_id' => $this->generateId(),
            'medical_reference' => $request->medical_reference,
            'urgency_level' => $request->urgency_level,
            'time_frame' => $request->time_frame,
            'time_frame_en' => $request->time_frame_en,
            'note' => $request->note,
            'note_en' => $request->note_en,
            'status' => $request->status ?? '1',
            'diagram_id' => $request->diagram_id,
            'threshold_outcome' => $request->threshold_outcome,
            'threshold_box_id' => $request->threshold_box_id,
            'created_by' => $request->user()->user_id,
            'updated_by' => $request->user()->user_id,
        ]);

        // ผูก diseases (many-to-many)
        if ($request->has('disease_ids')) {
            $sync = [];
            foreach ($request->disease_ids as $order => $diseaseId) {
                $sync[$diseaseId] = ['display_order' => $order];
            }
            $rule->diseases()->sync($sync);
        }

        $this->syncNextDiagrams($rule, $request->input('next_diagrams', []));

        // บันทึก conditions
        if ($request->has('conditions')) {
            foreach ($request->conditions as $condition) {
                $rule->conditions()->create([
                    'condition_id' => $this->generateConditionId(),
                    'status' => $condition['status'] ?? '1',
                    'rule_id' => $rule->rule_id,
                    'box_id' => $condition['box_id'],
                    'choice_id' => $condition['choice_id'],
                    'logic_operator' => $condition['logic_operator'] ?? 'AND',
                    'created_by' => $request->user()->user_id,
                    'updated_by' => $request->user()->user_id,
                ]);
            }
        }

        return new DiagnosisRuleResource($rule->load(['diagram', 'diseases', 'conditions.box', 'conditions.choice', 'nextDiagrams']));
    }

    public function show(DiagnosisRule $diagnosisRule)
    {
        return new DiagnosisRuleResource($diagnosisRule->load(['diagram', 'diseases', 'conditions.box', 'conditions.choice', 'nextDiagrams']));
    }

    public function update(DiagnosisRuleRequest $request, DiagnosisRule $diagnosisRule)
    {
        $this->validateNextDiagramTargets($request->input('next_diagrams', []));

        $diagnosisRule->update([
            'medical_reference' => $request->medical_reference,
            'urgency_level' => $request->urgency_level,
            'time_frame' => $request->time_frame,
            'time_frame_en' => $request->time_frame_en,
            'note' => $request->note,
            'note_en' => $request->note_en,
            'status' => $request->status ?? $diagnosisRule->status,
            'diagram_id' => $request->diagram_id,
            'threshold_outcome' => $request->threshold_outcome,
            'threshold_box_id' => $request->threshold_box_id,
            'updated_by' => $request->user()->user_id,
        ]);

        if ($request->has('disease_ids')) {
            $sync = [];
            foreach ($request->disease_ids as $order => $diseaseId) {
                $sync[$diseaseId] = ['display_order' => $order];
            }
            $diagnosisRule->diseases()->sync($sync);
        }

        if ($request->has('next_diagrams')) {
            $this->syncNextDiagrams($diagnosisRule, $request->input('next_diagrams', []));
        }

        if ($request->has('conditions')) {
            $diagnosisRule->conditions()->delete();

            foreach ($request->conditions as $condition) {
                $diagnosisRule->conditions()->create([
                    'condition_id' => $this->generateConditionId(),
                    'status' => $condition['status'] ?? '1',
                    'rule_id' => $diagnosisRule->rule_id,
                    'box_id' => $condition['box_id'],
                    'choice_id' => $condition['choice_id'],
                    'logic_operator' => $condition['logic_operator'] ?? 'AND',
                    'created_by' => $request->user()->user_id,
                    'updated_by' => $request->user()->user_id,
                ]);
            }
        }

        return new DiagnosisRuleResource($diagnosisRule->load(['diagram', 'diseases', 'conditions.box', 'conditions.choice', 'nextDiagrams']));
    }

    public function destroy(DiagnosisRule $diagnosisRule)
    {
        $diagnosisRule->diseases()->detach(); // ลบ pivot ก่อน
        $diagnosisRule->delete();

        return response()->json(['message' => 'ลบกฎการวินิจฉัยสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = DiagnosisRule::max('rule_id');
        $next = $last ? (int) $last + 1 : 1;

        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }

    private function generateConditionId(): string
    {
        $last = RuleCondition::max('condition_id');
        $next = $last ? (int) $last + 1 : 1;

        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }

    private function syncNextDiagrams(DiagnosisRule $rule, array $nextDiagrams): void
    {
        $sync = [];
        foreach ($nextDiagrams as $index => $nextDiagram) {
            $targetBoxId = $nextDiagram['target_box_id'] ?? null;
            $sync[$nextDiagram['diagram_id']] = [
                'display_order' => $nextDiagram['display_order'] ?? $index,
                'prompt_text' => $nextDiagram['prompt_text'] ?? null,
                'target_box_id' => $targetBoxId,
            ];
        }
        $rule->nextDiagrams()->sync($sync);
    }

    private function validateNextDiagramTargets(array $nextDiagrams): void
    {
        foreach ($nextDiagrams as $nextDiagram) {
            $targetBoxId = $nextDiagram['target_box_id'] ?? null;
            if (! $targetBoxId) {
                continue;
            }

            $belongsToDiagram = QuestionBox::query()
                ->whereKey($targetBoxId)
                ->where('diagram_id', $nextDiagram['diagram_id'])
                ->exists();
            abort_unless($belongsToDiagram, 422, 'กรอบคำถามปลายทางต้องอยู่ในแผนภูมิที่เลือก');
        }
    }
}
