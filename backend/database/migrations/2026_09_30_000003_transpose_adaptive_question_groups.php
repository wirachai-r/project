<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $questionBySymptom = DB::table('adaptive_questions')
                ->pluck('id', 'question_symptom_id');
            $questionSymptoms = DB::table('adaptive_questions')
                ->pluck('question_symptom_id', 'id');
            $rules = DB::table('adaptive_question_rules')->orderBy('id')->get();
            $now = now();
            $transposed = [];

            foreach ($rules as $rule) {
                $targetSymptomId = $questionSymptoms->get($rule->adaptive_question_id);
                if (! $targetSymptomId || $rule->initial_symptom_id === $targetSymptomId) {
                    continue;
                }

                $relatedQuestionId = $questionBySymptom->get($rule->initial_symptom_id);
                if (! $relatedQuestionId) {
                    continue;
                }

                $key = $targetSymptomId.'|'.$relatedQuestionId;
                $transposed[$key] = [
                    'initial_symptom_id' => $targetSymptomId,
                    'adaptive_question_id' => $relatedQuestionId,
                    'question_stage' => $rule->question_stage,
                    'priority' => $rule->priority,
                    'is_required' => $rule->is_required,
                    'status' => $rule->status,
                    'evidence_source' => $rule->evidence_source,
                    'evidence_status' => $rule->evidence_status,
                    'reviewed_by' => $rule->reviewed_by,
                    'reviewed_at' => $rule->reviewed_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('adaptive_question_rules')
                ->whereRaw('initial_symptom_id <> (SELECT question_symptom_id FROM adaptive_questions WHERE adaptive_questions.id = adaptive_question_rules.adaptive_question_id)')
                ->delete();

            if ($transposed !== []) {
                DB::table('adaptive_question_rules')->upsert(
                    array_values($transposed),
                    ['initial_symptom_id', 'adaptive_question_id'],
                    [
                        'question_stage', 'priority', 'is_required', 'status',
                        'evidence_source', 'evidence_status', 'reviewed_by',
                        'reviewed_at', 'updated_at',
                    ],
                );
            }
        });
    }

    public function down(): void
    {
        // The original direction is ambiguous after administrators edit groups.
    }
};
