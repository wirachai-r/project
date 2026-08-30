<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $links = DB::table('rule_diseases')
            ->join('diagnosis_rules', 'diagnosis_rules.rule_id', '=', 'rule_diseases.rule_id')
            ->join('symptom_diagrams', 'symptom_diagrams.diagram_id', '=', 'diagnosis_rules.diagram_id')
            ->select('rule_diseases.disease_id', 'symptom_diagrams.symptom_id')
            ->distinct()
            ->get();

        foreach ($links->chunk(500) as $chunk) {
            DB::table('disease_symptoms')->insertOrIgnore(
                $chunk->map(fn ($link) => [
                    'disease_id' => $link->disease_id,
                    'symptom_id' => $link->symptom_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all(),
            );
        }
    }

    public function down(): void
    {
        // Keep disease-symptom links because administrators may have edited
        // them after this migration and they are medical-content records.
    }
};
