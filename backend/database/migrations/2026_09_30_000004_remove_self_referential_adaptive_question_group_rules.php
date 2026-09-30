<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('adaptive_question_rules')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('adaptive_questions')
                    ->whereColumn('adaptive_questions.id', 'adaptive_question_rules.adaptive_question_id')
                    ->whereColumn('adaptive_questions.question_symptom_id', 'adaptive_question_rules.initial_symptom_id');
            })
            ->delete();
    }

    public function down(): void
    {
        // Self-referential group members are intentionally not restored.
    }
};
