<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('diseases')
            ->leftJoin('disease_symptoms', 'diseases.disease_id', '=', 'disease_symptoms.disease_id')
            ->select('diseases.disease_id', DB::raw('COUNT(disease_symptoms.symptom_id) as symptom_count'))
            ->groupBy('diseases.disease_id')
            ->orderBy('diseases.disease_id')
            ->get()
            ->each(function (object $disease): void {
                $symptomCount = (int) $disease->symptom_count;
                $minimum = match (true) {
                    $symptomCount <= 1 => 1,
                    $symptomCount <= 6 => 2,
                    $symptomCount <= 10 => 3,
                    default => 4,
                };

                DB::table('diseases')
                    ->where('disease_id', $disease->disease_id)
                    ->update(['minimum_supporting_symptoms' => $minimum]);
            });
    }

    public function down(): void
    {
        DB::table('diseases')->update(['minimum_supporting_symptoms' => 1]);
    }
};
