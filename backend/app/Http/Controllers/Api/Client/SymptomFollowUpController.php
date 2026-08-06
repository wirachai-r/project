<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\SymptomFollowUp;
use Illuminate\Http\Request;

class SymptomFollowUpController extends Controller
{
    public function index(Request $request, Assessment $assessment)
    {
        $this->authorizeOwner($request, $assessment);
        return response()->json(['data' => SymptomFollowUp::where('assessment_id', $assessment->id)
            ->where('user_id', $request->user()->user_id)->latest('recorded_at')->get()]);
    }

    public function store(Request $request, Assessment $assessment)
    {
        $this->authorizeOwner($request, $assessment);
        $data = $request->validate([
            'severity' => 'required|integer|min:1|max:10',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'note' => 'nullable|string|max:2000',
            'recorded_at' => 'nullable|date',
        ]);

        $followUp = SymptomFollowUp::create($data + [
            'assessment_id' => $assessment->id,
            'user_id' => $request->user()->user_id,
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);
        return response()->json(['data' => $followUp], 201);
    }

    public function destroy(Request $request, SymptomFollowUp $followUp)
    {
        abort_if($followUp->user_id !== $request->user()->user_id, 403);
        $followUp->delete();
        return response()->json(['message' => 'ลบบันทึกเรียบร้อยแล้ว']);
    }

    private function authorizeOwner(Request $request, Assessment $assessment): void
    {
        abort_if($assessment->user_id !== $request->user()->user_id, 403);
    }
}
