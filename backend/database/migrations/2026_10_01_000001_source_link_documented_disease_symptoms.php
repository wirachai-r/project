<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SOURCE_PREFIX = 'ตำราการตรวจรักษาโรคทั่วไป — หน้าที่อ้างอิงในข้อมูลโรค: ';

    public function up(): void
    {
        $relationships = DB::table('disease_symptoms as ds')
            ->join('diseases as d', 'd.disease_id', '=', 'ds.disease_id')
            ->join('main_symptoms as s', 's.symptom_id', '=', 'ds.symptom_id')
            ->where(fn ($query) => $query
                ->whereNull('ds.evidence_source')
                ->orWhere('ds.evidence_source', ''))
            ->whereNotNull('d.reference')
            ->where('d.reference', '!=', '')
            ->get([
                'ds.disease_id',
                'ds.symptom_id',
                'd.reference',
                'd.symptom_description',
                's.symptom_name',
            ])
            ->filter(fn ($item) => str_contains(
                trim(strip_tags((string) $item->symptom_description)),
                (string) $item->symptom_name,
            ));

        $now = now();
        foreach ($relationships as $relationship) {
            DB::table('disease_symptoms')
                ->where('disease_id', $relationship->disease_id)
                ->where('symptom_id', $relationship->symptom_id)
                ->where(fn ($query) => $query
                    ->whereNull('evidence_source')
                    ->orWhere('evidence_source', ''))
                ->update([
                    'evidence_source' => self::SOURCE_PREFIX.$relationship->reference,
                    'evidence_status' => 'source_linked',
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        DB::table('disease_symptoms')
            ->where('evidence_source', 'like', self::SOURCE_PREFIX.'%')
            ->update([
                'evidence_source' => null,
                'evidence_status' => 'unreviewed',
                'updated_at' => now(),
            ]);
    }
};
