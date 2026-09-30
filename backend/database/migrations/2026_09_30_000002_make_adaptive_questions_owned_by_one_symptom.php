<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $questions = DB::table('adaptive_questions')
                ->orderBy('id')
                ->get(['id', 'question_symptom_id', 'status', 'evidence_source', 'approved_by', 'approved_at']);

            $symptomRows = $questions->map(fn (object $question): array => [
                'adaptive_question_id' => $question->id,
                'symptom_id' => $question->question_symptom_id,
                'display_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $ruleRows = $questions->map(function (object $question) use ($now): array {
                $reviewed = in_array($question->status, ['reviewed', 'approved'], true);

                return [
                    'initial_symptom_id' => $question->question_symptom_id,
                    'adaptive_question_id' => $question->id,
                    'question_stage' => 'local',
                    'priority' => 1,
                    'is_required' => true,
                    'status' => '1',
                    'evidence_source' => $question->evidence_source,
                    'evidence_status' => $reviewed ? 'reviewed' : 'unreviewed',
                    'reviewed_by' => $reviewed ? $question->approved_by : null,
                    'reviewed_at' => $reviewed ? ($question->approved_at ?? $now) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            });

            DB::table('adaptive_question_symptoms')->delete();
            DB::table('adaptive_question_rules')->delete();
            $symptomRows->chunk(500)->each(fn ($rows) => DB::table('adaptive_question_symptoms')->insert($rows->all()));
            $ruleRows->chunk(500)->each(fn ($rows) => DB::table('adaptive_question_rules')->insert($rows->all()));
        });

        Schema::table('adaptive_questions', function ($table): void {
            $table->unique('question_symptom_id', 'adaptive_questions_symptom_unique');
        });
    }

    public function down(): void
    {
        Schema::table('adaptive_questions', function ($table): void {
            $table->dropUnique('adaptive_questions_symptom_unique');
        });

        // Previous many-starting-symptom routes cannot be reconstructed safely.
    }
};
