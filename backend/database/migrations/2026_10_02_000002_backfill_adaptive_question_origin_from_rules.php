<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $generatedQuestionIds = DB::table('adaptive_question_rules')
            ->where('evidence_source', 'like', 'Candidate จากโรคร่วม%')
            ->distinct()
            ->pluck('adaptive_question_id');

        if ($generatedQuestionIds->isNotEmpty()) {
            DB::table('adaptive_questions')
                ->whereIn('id', $generatedQuestionIds)
                ->update(['origin' => 'generated']);
        }
    }

    public function down(): void
    {
        // Provenance is intentionally not erased during rollback because
        // other migrations or later admin review may rely on it.
    }
};
