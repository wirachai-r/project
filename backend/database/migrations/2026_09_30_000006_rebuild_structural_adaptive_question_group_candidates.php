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
            $symptoms = DB::table('main_symptoms')
                ->where('status', '1')
                ->get(['symptom_id', 'symptom_category_id']);
            $questionIds = DB::table('adaptive_questions')->pluck('id', 'question_symptom_id');
            $diseasesBySymptom = DB::table('disease_symptoms')
                ->get(['disease_id', 'symptom_id'])
                ->groupBy('symptom_id')
                ->map(fn (Collection $rows) => $rows->pluck('disease_id')->unique());
            $categories = $symptoms->pluck('symptom_category_id', 'symptom_id');
            $groups = DB::table('body_area_group_symptoms')
                ->get(['symptom_id', 'body_area_group_id'])
                ->groupBy('symptom_id')
                ->map(fn (Collection $rows) => $rows->pluck('body_area_group_id')->unique());
            $subgroups = DB::table('body_area_subgroup_symptoms')
                ->get(['symptom_id', 'body_area_subgroup_id'])
                ->groupBy('symptom_id')
                ->map(fn (Collection $rows) => $rows->pluck('body_area_subgroup_id')->unique());
            $existing = DB::table('adaptive_question_rules')
                ->get(['initial_symptom_id', 'adaptive_question_id', 'priority'])
                ->groupBy('initial_symptom_id');
            $now = now();
            $rows = collect();

            foreach ($symptoms as $initial) {
                $initialDiseases = $diseasesBySymptom->get($initial->symptom_id, collect());
                if ($initialDiseases->isEmpty()) {
                    continue;
                }

                $existingRules = $existing->get($initial->symptom_id, collect());
                $existingQuestionIds = $existingRules->pluck('adaptive_question_id');
                $remainingSlots = max(0, self::MAX_QUESTIONS_PER_GROUP - $existingRules->count());
                if ($remainingSlots === 0) {
                    continue;
                }

                $candidates = collect();
                foreach ($symptoms as $target) {
                    if ($initial->symptom_id === $target->symptom_id) {
                        continue;
                    }

                    $questionId = $questionIds->get($target->symptom_id);
                    if (! $questionId || $existingQuestionIds->contains($questionId)) {
                        continue;
                    }

                    $targetDiseases = $diseasesBySymptom->get($target->symptom_id, collect());
                    $shared = $initialDiseases->intersect($targetDiseases)->count();
                    $union = $initialDiseases->merge($targetDiseases)->unique()->count();
                    $jaccard = $union > 0 ? $shared / $union : 0;
                    $conditional = $shared / $initialDiseases->count();
                    $sameCategory = $categories->get($initial->symptom_id) === $categories->get($target->symptom_id);
                    $sameGroup = $groups->get($initial->symptom_id, collect())
                        ->intersect($groups->get($target->symptom_id, collect()))->isNotEmpty();
                    $sameSubgroup = $subgroups->get($initial->symptom_id, collect())
                        ->intersect($subgroups->get($target->symptom_id, collect()))->isNotEmpty();

                    if ($shared < 2 || $conditional < 0.10 || (! $sameCategory && ! $sameGroup && ! $sameSubgroup && $jaccard < 0.20)) {
                        continue;
                    }

                    $candidates->push([
                        'adaptive_question_id' => $questionId,
                        'score' => ($jaccard * 50) + ($conditional * 30) + min($shared, 10)
                            + ($sameSubgroup ? 15 : ($sameGroup ? 8 : 0)) + ($sameCategory ? 5 : 0),
                        'shared' => $shared,
                    ]);
                }

                $priority = ((int) $existingRules->max('priority')) + 1;
                $candidates->sortByDesc('score')->take($remainingSlots)->each(
                    function (array $candidate) use ($initial, $now, $rows, &$priority): void {
                        $rows->push([
                            'initial_symptom_id' => $initial->symptom_id,
                            'adaptive_question_id' => $candidate['adaptive_question_id'],
                            'question_stage' => 'associated',
                            'priority' => $priority++,
                            'is_required' => false,
                            'status' => '1',
                            'evidence_source' => "Candidate จากโรคร่วม {$candidate['shared']} โรค และบริบทหมวด/ตำแหน่งอาการ; ต้องตรวจสอบก่อนใช้งาน",
                            'evidence_status' => 'unreviewed',
                            'reviewed_by' => null,
                            'reviewed_at' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    },
                );
            }

            $rows->chunk(500)->each(fn (Collection $chunk) => DB::table('adaptive_question_rules')->insertOrIgnore($chunk->all()));
        });
    }

    public function down(): void
    {
        DB::table('adaptive_question_rules')
            ->where('evidence_source', 'like', 'Candidate จากโรคร่วม%')
            ->where('evidence_status', 'unreviewed')
            ->delete();
    }
};
