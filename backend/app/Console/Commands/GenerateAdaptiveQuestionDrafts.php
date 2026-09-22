<?php

namespace App\Console\Commands;

use App\Models\MainSymptom;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateAdaptiveQuestionDrafts extends Command
{
    protected $signature = 'adaptive:generate-drafts {--replace : Rebuild generated draft rules}';

    protected $description = 'Generate inactive Adaptive question candidates using conservative structural relevance rules';

    public function handle(): int
    {
        $symptoms = MainSymptom::query()->where('status', '1')->orderBy('symptom_name')
            ->get(['symptom_id', 'symptom_name', 'symptom_category_id']);
        $existingIds = DB::table('adaptive_questions')
            ->whereIn('question_symptom_id', $symptoms->pluck('symptom_id'))
            ->pluck('id', 'question_symptom_id');
        $now = now();
        $newRows = $symptoms->whereNotIn('symptom_id', $existingIds->keys())->map(fn (MainSymptom $symptom) => [
            'question_symptom_id' => $symptom->symptom_id,
            'question_text' => "มีอาการ{$symptom->symptom_name}ร่วมด้วยหรือไม่?",
            'explanation_text' => 'เลือกคำตอบที่ตรงกับอาการในขณะนี้มากที่สุด',
            'answer_type' => 'yes_no_unsure',
            'status' => 'draft',
            // Generated provenance is not medical evidence. Keep this empty
            // until a reviewer adds a traceable source.
            'evidence_source' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values();
        foreach ($newRows->chunk(100) as $chunk) {
            DB::table('adaptive_questions')->insert($chunk->all());
        }

        $questionIds = DB::table('adaptive_questions')
            ->whereIn('question_symptom_id', $symptoms->pluck('symptom_id'))->orderBy('id')
            ->get(['id', 'question_symptom_id'])->unique('question_symptom_id')
            ->pluck('id', 'question_symptom_id');
        $pivotRows = $questionIds->map(fn ($questionId, $symptomId) => [
            'adaptive_question_id' => $questionId,
            'symptom_id' => $symptomId,
            'display_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values();
        DB::table('adaptive_question_symptoms')->insertOrIgnore($pivotRows->all());

        $draftQuestionIds = DB::table('adaptive_questions')->where('status', 'draft')
            ->whereIn('id', $questionIds->values())->pluck('id');
        if ($this->option('replace')) {
            DB::table('adaptive_question_rules')->whereIn('adaptive_question_id', $draftQuestionIds)->delete();
        }

        // One shared disease is too broad. Candidates need repeated co-occurrence
        // and either shared taxonomy/body context or strong proportional overlap.
        // This only shortlists drafts and never constitutes clinical approval.
        $diseasesBySymptom = DB::table('disease_symptoms')->get(['disease_id', 'symptom_id'])
            ->groupBy('symptom_id')->map(fn ($rows) => $rows->pluck('disease_id')->unique());
        $categories = $symptoms->pluck('symptom_category_id', 'symptom_id');
        $groups = DB::table('body_area_group_symptoms')->get(['symptom_id', 'body_area_group_id'])
            ->groupBy('symptom_id')->map(fn ($rows) => $rows->pluck('body_area_group_id')->unique());
        $subgroups = DB::table('body_area_subgroup_symptoms')->get(['symptom_id', 'body_area_subgroup_id'])
            ->groupBy('symptom_id')->map(fn ($rows) => $rows->pluck('body_area_subgroup_id')->unique());

        $candidates = collect();
        foreach ($symptoms as $initial) {
            $initialDiseases = $diseasesBySymptom->get($initial->symptom_id, collect());
            if ($initialDiseases->isEmpty()) {
                continue;
            }
            foreach ($symptoms as $target) {
                if ($initial->symptom_id === $target->symptom_id) {
                    continue;
                }
                $questionId = $questionIds->get($target->symptom_id);
                if (! $questionId || ! $draftQuestionIds->contains($questionId)) {
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
                $score = ($jaccard * 50) + ($conditional * 30) + min($shared, 10)
                    + ($sameSubgroup ? 15 : ($sameGroup ? 8 : 0)) + ($sameCategory ? 5 : 0);
                $candidates->push([
                    'initial_symptom_id' => $initial->symptom_id,
                    'adaptive_question_id' => $questionId,
                    'score' => $score,
                ]);
            }
        }

        $rules = $candidates->groupBy('initial_symptom_id')->flatMap(function ($items) use ($now) {
            return $items->sortByDesc('score')->take(12)->values()->map(fn ($item, $index) => [
                'adaptive_question_id' => $item['adaptive_question_id'],
                'initial_symptom_id' => $item['initial_symptom_id'],
                'question_stage' => 'associated',
                'priority' => $index + 1,
                'is_required' => false,
                'status' => '1',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        })->values();
        foreach ($rules->chunk(500) as $chunk) {
            DB::table('adaptive_question_rules')->upsert(
                $chunk->all(),
                ['initial_symptom_id', 'adaptive_question_id'],
                ['question_stage', 'priority', 'is_required', 'status', 'updated_at'],
            );
        }

        $covered = $rules->pluck('initial_symptom_id')->unique()->count();
        $this->info("Created {$newRows->count()} question drafts; {$rules->count()} conservative candidate links cover {$covered} initial symptoms.");
        $this->warn('Generated questions stay draft and inactive until an authorized clinical reviewer approves them.');

        return self::SUCCESS;
    }
}
