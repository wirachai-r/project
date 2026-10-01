<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $diseaseIds = DB::table('diseases as d')
            ->join('disease_symptoms as ds', 'ds.disease_id', '=', 'd.disease_id')
            ->groupBy('d.disease_id')
            ->havingRaw('COUNT(ds.symptom_id) = 4')
            ->pluck('d.disease_id');

        DB::table('diseases')
            ->whereIn('disease_id', $diseaseIds)
            ->update([
                'minimum_supporting_symptoms' => 1,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Do not guess previous administrator-configured thresholds.
    }
};
