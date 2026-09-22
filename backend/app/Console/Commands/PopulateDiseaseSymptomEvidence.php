<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateDiseaseSymptomEvidence extends Command
{
    private const SOURCE_PREFIX = 'ตำราการตรวจรักษาโรคทั่วไป — ';

    protected $signature = 'medical:populate-disease-symptom-evidence
        {--write : Persist sources; without this option the command is a dry run}
        {--overwrite : Replace an existing evidence source}';

    protected $description = 'Populate disease-symptom provenance from diagnosis diagrams without inventing medical evidence';

    public function handle(): int
    {
        $references = DB::table('symptom_diagrams as sd')
            ->join('diagnosis_rules as dr', 'dr.diagram_id', '=', 'sd.diagram_id')
            ->join('rule_diseases as rd', 'rd.rule_id', '=', 'dr.rule_id')
            ->whereNotNull('dr.medical_reference')
            ->where('dr.medical_reference', '!=', '')
            ->get(['sd.symptom_id', 'rd.disease_id', 'dr.medical_reference'])
            ->groupBy(fn ($row) => $row->disease_id.'|'.$row->symptom_id)
            ->map(fn ($rows) => self::SOURCE_PREFIX.$rows
                ->pluck('medical_reference')
                ->unique()
                ->sort()
                ->implode('; '));

        $relationships = DB::table('disease_symptoms')
            ->get(['disease_id', 'symptom_id', 'evidence_source', 'evidence_status']);
        $eligible = $relationships->filter(function ($row) use ($references) {
            $hasManagedSource = str_starts_with((string) $row->evidence_source, self::SOURCE_PREFIX);
            if (
                ! $this->option('overwrite')
                && filled($row->evidence_source)
                && ! ($hasManagedSource && $row->evidence_status === 'unreviewed')
            ) {
                return false;
            }

            return $references->has($row->disease_id.'|'.$row->symptom_id);
        });
        $unsupported = $relationships->filter(
            fn ($row) => ! $references->has($row->disease_id.'|'.$row->symptom_id),
        );

        $this->table(['รายการ', 'จำนวน'], [
            ['ความสัมพันธ์ทั้งหมด', $relationships->count()],
            ['มีหลักฐานจากแผนภูมิในระบบ', $relationships->count() - $unsupported->count()],
            ['พร้อมอัปเดตในการรันนี้', $eligible->count()],
            ['ยังไม่มีหลักฐานจากแผนภูมิ', $unsupported->count()],
        ]);

        if (! $this->option('write')) {
            $this->warn('Dry run เท่านั้น: เพิ่ม --write เพื่อบันทึกข้อมูล');

            return self::SUCCESS;
        }

        $timestamp = now();
        $rows = $eligible->map(fn ($row) => [
            'disease_id' => $row->disease_id,
            'symptom_id' => $row->symptom_id,
            'evidence_source' => $references->get($row->disease_id.'|'.$row->symptom_id),
            'evidence_status' => 'source_linked',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        DB::transaction(function () use ($rows): void {
            $rows->chunk(500)->each(fn ($chunk) => DB::table('disease_symptoms')->upsert(
                $chunk->all(),
                ['disease_id', 'symptom_id'],
                ['evidence_source', 'evidence_status', 'updated_at'],
            ));
        });

        $this->info("บันทึกแหล่งที่มา {$eligible->count()} ความสัมพันธ์แล้ว");
        $this->warn('แหล่งที่มานี้รับรองการย้อนกลับไปยังแผนภูมิเดิม ไม่ใช่การอนุมัติความถูกต้องทางคลินิกใหม่');

        return self::SUCCESS;
    }
}
