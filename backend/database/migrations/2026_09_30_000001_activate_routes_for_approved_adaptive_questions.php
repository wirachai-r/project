<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const GENERATED_EVIDENCE_PREFIX = 'Generated candidate from internal disease-symptom co-occurrence and taxonomy';

    public function up(): void
    {
        DB::table('adaptive_questions')
            ->where('status', 'approved')
            ->whereNotNull('evidence_source')
            ->where('evidence_source', '!=', '')
            ->where('evidence_source', 'not like', self::GENERATED_EVIDENCE_PREFIX.'%')
            ->orderBy('id')
            ->each(function ($question): void {
                $rules = DB::table('adaptive_question_rules')
                    ->where('adaptive_question_id', $question->id)
                    ->where('status', '1')
                    ->whereIn('evidence_status', ['unreviewed', 'source_linked']);

                (clone $rules)
                    ->where(fn ($query) => $query
                        ->whereNull('evidence_source')
                        ->orWhere('evidence_source', ''))
                    ->update(['evidence_source' => $question->evidence_source]);

                $rules->update([
                    'evidence_status' => 'reviewed',
                    'reviewed_by' => $question->approved_by,
                    'reviewed_at' => $question->approved_at ?? now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // Preserve explicit administrator approvals and review audit data.
    }
};
