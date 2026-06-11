<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DiagnosisRuleRequest;
use App\Http\Resources\Admin\DiagnosisRuleResource;
use App\Models\DiagnosisRule;
use Illuminate\Http\Request;

class DiagnosisRuleController extends Controller
{
    public function index(Request $request)
    {
        $rules = DiagnosisRule::query()
            ->with(['disease', 'answerChoice'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->disease_id, fn($q) => $q->where('disease_id', $request->disease_id))
            ->when($request->choice_id, fn($q) => $q->where('choice_id', $request->choice_id))
            ->paginate(20);

        return DiagnosisRuleResource::collection($rules);
    }

    public function store(DiagnosisRuleRequest $request)
    {
        $rule = DiagnosisRule::create([
            'rule_id'    => $this->generateId(),
            'disease_id' => $request->disease_id,
            'choice_id'  => $request->choice_id,
            'score'      => $request->score ?? 0,
            'status'     => $request->status ?? '1',
            'created_by' => $request->user()->user_id,
            'updated_by' => $request->user()->user_id,
        ]);

        return new DiagnosisRuleResource($rule->load(['disease', 'answerChoice']));
    }

    public function show(DiagnosisRule $diagnosisRule)
    {
        return new DiagnosisRuleResource($diagnosisRule->load(['disease', 'answerChoice']));
    }

    public function update(DiagnosisRuleRequest $request, DiagnosisRule $diagnosisRule)
    {
        $diagnosisRule->update([
            'disease_id' => $request->disease_id,
            'choice_id'  => $request->choice_id,
            'score'      => $request->score ?? $diagnosisRule->score,
            'status'     => $request->status ?? $diagnosisRule->status,
            'updated_by' => $request->user()->user_id,
        ]);

        return new DiagnosisRuleResource($diagnosisRule->load(['disease', 'answerChoice']));
    }

    public function destroy(DiagnosisRule $diagnosisRule)
    {
        $diagnosisRule->delete();

        return response()->json(['message' => 'ลบกฎการวินิจฉัยสำเร็จ']);
    }

    private function generateId(): string
    {
        $last = DiagnosisRule::max('rule_id');
        $next = $last ? (int)$last + 1 : 1;
        return str_pad($next, 10, '0', STR_PAD_LEFT);
    }
}
