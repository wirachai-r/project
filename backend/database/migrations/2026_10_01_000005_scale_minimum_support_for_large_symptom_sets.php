<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateMinimums(fn (int $symptomCount): int => match (true) {
            $symptomCount <= 4 => 1,
            $symptomCount <= 7 => 2,
            $symptomCount <= 10 => 3,
            $symptomCount <= 14 => 4,
            default => 5 + intdiv($symptomCount - 15, 5),
        });
    }

    public function down(): void
    {
        $this->updateMinimums(fn (int $symptomCount): int => match (true) {
            $symptomCount <= 4 => 1,
            $symptomCount <= 7 => 2,
            $symptomCount <= 10 => 3,
            $symptomCount <= 14 => 4,
            default => 5,
        });
    }

    /** Update only diseases that have at least one related symptom. */
    private function updateMinimums(callable $minimumFor): void
    {
        DB::table('disease_symptoms')
            ->select('disease_id', DB::raw('COUNT(*) as symptom_count'))
            ->groupBy('disease_id')
            ->orderBy('disease_id')
            ->get()
            ->groupBy(fn ($row) => $minimumFor((int) $row->symptom_count))
            ->each(function ($rows, $minimum): void {
                DB::table('diseases')
                    ->whereIn('disease_id', $rows->pluck('disease_id'))
                    ->update([
                        'minimum_supporting_symptoms' => (int) $minimum,
                        'updated_at' => now(),
                    ]);
            });
    }
};
