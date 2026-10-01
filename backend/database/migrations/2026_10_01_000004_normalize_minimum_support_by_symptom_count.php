<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $diseases = DB::table('disease_symptoms')
            ->selectRaw('disease_id, COUNT(symptom_id) as symptom_count')
            ->groupBy('disease_id')
            ->get();

        $now = now();
        foreach ($diseases->groupBy(fn ($disease) => $this->minimumSupport(
            (int) $disease->symptom_count,
        )) as $minimumSupport => $items) {
            DB::table('diseases')
                ->whereIn('disease_id', $items->pluck('disease_id'))
                ->update([
                    'minimum_supporting_symptoms' => (int) $minimumSupport,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        // Do not guess previous administrator-configured thresholds.
    }

    private function minimumSupport(int $symptomCount): int
    {
        return match (true) {
            $symptomCount <= 4 => 1,
            $symptomCount <= 7 => 2,
            $symptomCount <= 10 => 3,
            $symptomCount <= 14 => 4,
            default => 5,
        };
    }
};
