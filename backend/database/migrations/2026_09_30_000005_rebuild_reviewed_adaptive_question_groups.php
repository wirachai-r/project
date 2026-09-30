<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MAX_QUESTIONS_PER_GROUP = 12;

    public function up(): void
    {
        DB::transaction(function (): void {
            $questionsBySymptom = DB::table('adaptive_questions')
                ->where('status', 'approved')
                ->pluck('id', 'question_symptom_id');
            $existing = DB::table('adaptive_question_rules')
                ->where('status', '1')
                ->get(['initial_symptom_id', 'adaptive_question_id', 'priority'])
                ->groupBy('initial_symptom_id');

            $candidates = DB::table('disease_symptoms as initial')
                ->join('disease_symptoms as target', function ($join): void {
                    $join->on('target.disease_id', '=', 'initial.disease_id')
                        ->whereColumn('target.symptom_id', '!=', 'initial.symptom_id');
                })
                ->join('diseases as disease', 'disease.disease_id', '=', 'initial.disease_id')
                ->whereIn('initial.evidence_status', ['reviewed', 'verified'])
                ->whereIn('target.evidence_status', ['reviewed', 'verified'])
                ->where('disease.status', '1')
                ->groupBy('initial.symptom_id', 'target.symptom_id')
                ->get([
                    'initial.symptom_id as initial_symptom_id',
                    'target.symptom_id as target_symptom_id',
                    DB::raw('COUNT(DISTINCT initial.disease_id) as shared_disease_count'),
                    DB::raw('MAX(CASE WHEN target.is_key_symptom THEN 1 ELSE 0 END) as has_key_symptom'),
                    DB::raw('MAX(target.assessment_weight) as max_weight'),
                    DB::raw('MIN(disease.disease_name) as disease_names'),
                ])
                ->groupBy('initial_symptom_id');

            $now = now();
            $rows = collect();
            foreach ($candidates as $initialSymptomId => $items) {
                $existingRules = $existing->get($initialSymptomId, collect());
                $existingQuestionIds = $existingRules->pluck('adaptive_question_id');
                $remainingSlots = max(0, self::MAX_QUESTIONS_PER_GROUP - $existingRules->count());
                if ($remainingSlots === 0) {
                    continue;
                }

                $priority = ((int) $existingRules->max('priority')) + 1;
                $items->filter(fn (object $item): bool => $questionsBySymptom->has($item->target_symptom_id))
                    ->reject(fn (object $item): bool => $existingQuestionIds->contains($questionsBySymptom->get($item->target_symptom_id)))
                    ->sort(function (object $left, object $right): int {
                        return [
                            (int) $right->has_key_symptom,
                            (int) $right->shared_disease_count,
                            (float) $right->max_weight,
                            $left->target_symptom_id,
                        ] <=> [
                            (int) $left->has_key_symptom,
                            (int) $left->shared_disease_count,
                            (float) $left->max_weight,
                            $right->target_symptom_id,
                        ];
                    })
                    ->take($remainingSlots)
                    ->each(function (object $item) use (
                        $initialSymptomId,
                        $questionsBySymptom,
                        $now,
                        $rows,
                        &$priority,
                    ): void {
                        $rows->push([
                            'initial_symptom_id' => $initialSymptomId,
                            'adaptive_question_id' => $questionsBySymptom->get($item->target_symptom_id),
                            'question_stage' => 'associated',
                            'priority' => $priority++,
                            'is_required' => false,
                            'status' => '1',
                            'evidence_source' => 'สร้างกลุ่มจาก disease_symptoms ที่ผ่านการตรวจสอบ; โรคร่วม: '.mb_substr((string) $item->disease_names, 0, 4500),
                            'evidence_status' => 'reviewed',
                            'reviewed_by' => null,
                            'reviewed_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    });
            }

            $rows->chunk(500)->each(fn (Collection $chunk) => DB::table('adaptive_question_rules')->insertOrIgnore($chunk->all()));
        });
    }

    public function down(): void
    {
        DB::table('adaptive_question_rules')
            ->where('evidence_source', 'like', 'สร้างกลุ่มจาก disease_symptoms ที่ผ่านการตรวจสอบ;%')
            ->delete();
    }
};
