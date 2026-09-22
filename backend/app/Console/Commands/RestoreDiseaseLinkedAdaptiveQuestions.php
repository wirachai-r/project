<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RestoreDiseaseLinkedAdaptiveQuestions extends Command
{
    private const QUESTION_IDS = [
        12, 31, 36, 39, 48, 49, 56, 63, 69,
        88, 92, 116, 133, 145, 146, 148, 156, 165,
    ];

    private const EXTERNAL_SOURCES = [
        31 => 'Merck Manual Professional, Fever of Unknown Origin (classic FUO is fever for more than 3 weeks): https://www.merckmanuals.com/professional/infectious-diseases/biology-of-infectious-disease/fever-of-unknown-origin-fuo',
        48 => 'WHO, Snakebite envenoming (paralysis, bleeding disorders, kidney failure and local tissue damage): https://www.who.int/health-topics/snakebite/snakebite',
    ];

    /** Explicitly curated where the source disease has unrelated legacy mappings. */
    private const ALLOWED_INITIAL_SYMPTOMS = [
        48 => [
            'แขนขาอ่อนแรง',
            'ไข้ + แขนขาอ่อนแรง',
            'ไข้ + จุดแดง-จ้ำเขียว',
            'จุดแดง-จ้ำเขียว',
            'ตาปรือ',
            'หนังตาตก',
            'อัมพาต',
        ],
    ];

    protected $signature = 'adaptive:restore-disease-linked-questions
        {--write : Save the reviewed routes and activate their questions}
        {--limit=12 : Maximum initial symptoms linked to each question}';

    protected $description = 'Restore inactive questions using traceable shared disease-symptom relationships';

    public function handle(): int
    {
        $limit = max(1, min(30, (int) $this->option('limit')));
        $questions = DB::table('adaptive_questions')
            ->whereIn('id', self::QUESTION_IDS)
            ->get(['id', 'question_symptom_id', 'question_text']);

        $rows = collect();
        $uncovered = collect();

        foreach ($questions as $question) {
            $externalSource = self::EXTERNAL_SOURCES[$question->id] ?? null;
            $candidates = $this->candidatesFor($question->question_symptom_id, $externalSource)
                ->when(
                    isset(self::ALLOWED_INITIAL_SYMPTOMS[$question->id]),
                    fn (Collection $items) => $items->whereIn(
                        'initial_symptom_name',
                        self::ALLOWED_INITIAL_SYMPTOMS[$question->id],
                    )->values(),
                )
                ->take($limit);

            if ($candidates->isEmpty()) {
                $uncovered->push($question);

                continue;
            }

            foreach ($candidates->values() as $index => $candidate) {
                $rows->push([
                    'initial_symptom_id' => $candidate['initial_symptom_id'],
                    'adaptive_question_id' => $question->id,
                    'question_stage' => 'associated',
                    'priority' => 20 + $index,
                    'is_required' => false,
                    'status' => '1',
                    'evidence_source' => $this->evidenceSource($candidate, $externalSource),
                    'evidence_status' => 'reviewed',
                    'reviewed_by' => null,
                    'reviewed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->table(
            ['รายการ', 'จำนวน'],
            [
                ['คำถามที่ตรวจ', $questions->count()],
                ['คำถามที่มีเส้นทางจากโรคร่วมและแหล่งข้อมูล', $questions->count() - $uncovered->count()],
                ['เส้นทางที่จะเปิดใช้', $rows->count()],
                ['คำถามที่ยังไม่มีหลักฐานเพียงพอ', $uncovered->count()],
            ],
        );

        if ($uncovered->isNotEmpty()) {
            $this->warn('ยังไม่เปิดใช้: '.$uncovered->pluck('question_text')->implode(', '));
        }

        if (! $this->option('write')) {
            $this->warn('Dry run เท่านั้น: เพิ่ม --write เพื่อบันทึก');

            return $uncovered->isEmpty() ? self::SUCCESS : self::FAILURE;
        }

        DB::transaction(function () use ($rows): void {
            $questionIds = $rows->pluck('adaptive_question_id')->unique();
            DB::table('adaptive_question_rules')
                ->whereIn('adaptive_question_id', $questionIds)
                ->where('evidence_source', 'like', 'เชื่อมผ่านโรคร่วมใน disease_symptoms:%')
                ->update([
                    'status' => '0',
                    'evidence_status' => 'rejected',
                    'reviewed_at' => now(),
                    'updated_at' => now(),
                ]);

            $rows->chunk(250)->each(fn (Collection $chunk) => DB::table('adaptive_question_rules')->upsert(
                $chunk->all(),
                ['initial_symptom_id', 'adaptive_question_id'],
                [
                    'question_stage',
                    'priority',
                    'is_required',
                    'status',
                    'evidence_source',
                    'evidence_status',
                    'reviewed_by',
                    'reviewed_at',
                    'updated_at',
                ],
            ));

            DB::table('adaptive_questions')
                ->whereIn('id', $questionIds)
                ->update([
                    'status' => 'approved',
                    'evidence_source' => 'ใช้แหล่งอ้างอิงระดับเส้นทางใน adaptive_question_rules ซึ่งเชื่อมผ่านโรคร่วมใน disease_symptoms',
                    'approved_by' => null,
                    'approved_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        $this->info("เปิดใช้ {$rows->pluck('adaptive_question_id')->unique()->count()} คำถาม ผ่าน {$rows->count()} เส้นทางแล้ว");
        $this->warn('สถานะ reviewed หมายถึงตรวจสอบที่มาและความสัมพันธ์ในฐานข้อมูลแล้ว; verified ยังสงวนไว้ให้ผู้เชี่ยวชาญทางคลินิกยืนยัน');

        return $uncovered->isEmpty() ? self::SUCCESS : self::FAILURE;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function candidatesFor(string $targetSymptomId, ?string $externalSource): Collection
    {
        $targetDiseaseCount = DB::table('disease_symptoms')
            ->where('symptom_id', $targetSymptomId)
            ->distinct()
            ->count('disease_id');

        if ($targetDiseaseCount === 0) {
            return collect();
        }

        $relationships = DB::table('disease_symptoms as target')
            ->join('disease_symptoms as initial', 'initial.disease_id', '=', 'target.disease_id')
            ->join('main_symptoms as symptom', 'symptom.symptom_id', '=', 'initial.symptom_id')
            ->join('diseases as disease', 'disease.disease_id', '=', 'target.disease_id')
            ->where('target.symptom_id', $targetSymptomId)
            ->whereColumn('initial.symptom_id', '!=', 'target.symptom_id')
            ->where('symptom.status', '1')
            ->get([
                'initial.symptom_id as initial_symptom_id',
                'symptom.symptom_name as initial_symptom_name',
                'disease.disease_id',
                'disease.disease_name',
                'disease.reference as disease_reference',
                'target.evidence_source as target_source',
                'initial.evidence_source as initial_source',
            ]);

        return $relationships
            ->groupBy('initial_symptom_id')
            ->map(function (Collection $sharedRows) use ($targetDiseaseCount, $externalSource): ?array {
                $traceable = $sharedRows->filter(fn ($row) => (filled($row->target_source) || filled($externalSource))
                    && (filled($row->initial_source) || filled($row->disease_reference))
                );

                if ($traceable->isEmpty()) {
                    return null;
                }

                $sharedCount = $sharedRows->pluck('disease_id')->unique()->count();
                $traceableCount = $traceable->pluck('disease_id')->unique()->count();

                return [
                    'initial_symptom_id' => $sharedRows->first()->initial_symptom_id,
                    'initial_symptom_name' => $sharedRows->first()->initial_symptom_name,
                    'shared_count' => $sharedCount,
                    'traceable_count' => $traceableCount,
                    'coverage' => $sharedCount / $targetDiseaseCount,
                    'disease_names' => $traceable->pluck('disease_name')->unique()->values(),
                    'sources' => $traceable->pluck('target_source')
                        ->merge($traceable->pluck('initial_source'))
                        ->filter()
                        ->unique()
                        ->values(),
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $candidate) => sprintf(
                '%04d-%04d-%08.5f-%s',
                $candidate['traceable_count'],
                $candidate['shared_count'],
                $candidate['coverage'],
                $candidate['initial_symptom_name'],
            ))
            ->values();
    }

    /** @param array<string, mixed> $candidate */
    private function evidenceSource(array $candidate, ?string $externalSource): string
    {
        $diseases = $candidate['disease_names']->take(5)->implode(', ');
        $sources = $candidate['sources']
            ->when($externalSource, fn (Collection $items) => $items->prepend($externalSource))
            ->take(3)
            ->implode('; ');

        return "เชื่อมผ่านโรคร่วมใน disease_symptoms: {$diseases} | แหล่งอ้างอิง: {$sources}";
    }
}
