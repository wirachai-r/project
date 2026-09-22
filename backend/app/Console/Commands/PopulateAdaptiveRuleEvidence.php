<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateAdaptiveRuleEvidence extends Command
{
    protected $signature = 'adaptive:populate-rule-evidence {--write : Persist derived rule-level provenance}';

    protected $description = 'Attach traceable disease-symptom provenance to each initial-symptom/question route';

    public function handle(): int
    {
        $links = DB::table('adaptive_question_rules as rule')
            ->join('adaptive_question_symptoms as target', 'target.adaptive_question_id', '=', 'rule.adaptive_question_id')
            ->join('disease_symptoms as initial_ds', 'initial_ds.symptom_id', '=', 'rule.initial_symptom_id')
            ->join('disease_symptoms as target_ds', function ($join) {
                $join->on('target_ds.disease_id', '=', 'initial_ds.disease_id')
                    ->on('target_ds.symptom_id', '=', 'target.symptom_id');
            })
            ->join('diseases as disease', 'disease.disease_id', '=', 'initial_ds.disease_id')
            ->whereNotNull('target_ds.evidence_source')
            ->where('target_ds.evidence_source', '!=', '')
            ->get([
                'rule.id as rule_id',
                'disease.disease_name',
                'target_ds.evidence_source',
            ])
            ->groupBy('rule_id')
            ->map(function ($rows) {
                $diseases = $rows->pluck('disease_name')->unique()->sort()->take(8)->implode(', ');
                $sources = $rows->pluck('evidence_source')->unique()->sort()->implode('; ');

                return "โรคที่เชื่อมโยงร่วมกัน: {$diseases} | {$sources}";
            });

        $total = DB::table('adaptive_question_rules')->count();
        $this->table(['รายการ', 'จำนวน'], [
            ['เส้นทางคำถามทั้งหมด', $total],
            ['ย้อนกลับไปยังโรคและแผนภูมิได้', $links->count()],
            ['ยังไม่มีหลักฐานรองรับ', $total - $links->count()],
        ]);

        if (! $this->option('write')) {
            $this->warn('Dry run เท่านั้น: เพิ่ม --write เพื่อบันทึกข้อมูล');

            return self::SUCCESS;
        }

        $links->chunk(200)->each(function ($chunk): void {
            $cases = [];
            $bindings = [];
            foreach ($chunk as $ruleId => $source) {
                $cases[] = 'WHEN ? THEN ?';
                $bindings[] = $ruleId;
                $bindings[] = $source;
            }
            $ids = $chunk->keys()->map(fn () => '?')->implode(', ');
            $bindings[] = now();
            $bindings = [...$bindings, ...$chunk->keys()->all()];

            DB::update(
                'UPDATE adaptive_question_rules
                 SET evidence_source = CASE id '.implode(' ', $cases).' END,
                     evidence_status = ?, updated_at = ?
                 WHERE id IN ('.$ids.')',
                [
                    ...array_slice($bindings, 0, -$chunk->count() - 1),
                    'source_linked',
                    $bindings[count($bindings) - $chunk->count() - 1],
                    ...$chunk->keys()->all(),
                ],
            );
        });

        $this->info("บันทึกที่มาระดับเส้นทาง {$links->count()} รายการแล้ว");
        $this->warn('source_linked หมายถึงตรวจย้อนกลับที่มาได้ แต่ยังไม่ใช่ verified ทางคลินิก');

        return self::SUCCESS;
    }
}
