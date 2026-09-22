<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditDiseaseSymptoms extends Command
{
    protected $signature = 'medical:audit-disease-symptoms {--symptom= : Inspect Adaptive questions linked to an initial symptom name}';

    protected $description = 'Audit completeness and review metadata of disease-symptom relationships';

    public function handle(): int
    {
        $metrics = [
            ['โรคทั้งหมด', DB::table('diseases')->count()],
            ['อาการทั้งหมด', DB::table('main_symptoms')->count()],
            ['ความสัมพันธ์โรค-อาการ', DB::table('disease_symptoms')->count()],
            ['โรคที่ไม่มีอาการเชื่อมโยง', DB::table('diseases as d')
                ->leftJoin('disease_symptoms as ds', 'ds.disease_id', '=', 'd.disease_id')
                ->whereNull('ds.symptom_id')->count()],
            ['อาการที่ไม่เชื่อมโยงกับโรค', DB::table('main_symptoms as s')
                ->leftJoin('disease_symptoms as ds', 'ds.symptom_id', '=', 's.symptom_id')
                ->whereNull('ds.disease_id')->count()],
            ['ความสัมพันธ์ที่ไม่มีแหล่งอ้างอิง', DB::table('disease_symptoms')
                ->where(fn ($query) => $query->whereNull('evidence_source')->orWhere('evidence_source', ''))->count()],
            ['อ้างอิงแผนภูมิการตรวจรักษาในระบบ', DB::table('disease_symptoms')
                ->where('evidence_source', 'like', 'ตำราการตรวจรักษาโรคทั่วไป — %')->count()],
            ['อ้างอิง URL ภายนอก', DB::table('disease_symptoms')
                ->where('evidence_source', 'like', '%http%')->count()],
            ['เชื่อมโยงที่มาแล้ว แต่ยังไม่ยืนยัน', DB::table('disease_symptoms')
                ->where('evidence_status', 'source_linked')->count()],
            ['ผู้เชี่ยวชาญยืนยันแล้ว', DB::table('disease_symptoms')
                ->where('evidence_status', 'verified')->count()],
            ['ปฏิเสธความสัมพันธ์แล้ว', DB::table('disease_symptoms')
                ->where('evidence_status', 'rejected')->count()],
            ['ความสัมพันธ์ที่เป็นอาการสำคัญ', DB::table('disease_symptoms')->where('is_key_symptom', true)->count()],
            ['ความสัมพันธ์ที่กำหนดน้ำหนักแล้ว', DB::table('disease_symptoms')->where('assessment_weight', '!=', 1)->count()],
            ['ความสัมพันธ์ที่กำหนดค่าปรับเมื่อไม่พบ', DB::table('disease_symptoms')->where('absence_penalty', '>', 0)->count()],
        ];

        $this->table(['รายการตรวจสอบ', 'จำนวน'], $metrics);
        $diseasesWithoutSymptoms = DB::table('diseases as d')
            ->leftJoin('disease_symptoms as ds', 'ds.disease_id', '=', 'd.disease_id')
            ->whereNull('ds.symptom_id')
            ->get(['d.disease_id', 'd.disease_name']);
        if ($diseasesWithoutSymptoms->isNotEmpty()) {
            $this->newLine();
            $this->warn('โรคที่ยังไม่มีอาการเชื่อมโยง');
            $this->table(['รหัสโรค', 'ชื่อโรค'], $diseasesWithoutSymptoms->map(fn ($item) => [
                $item->disease_id, $item->disease_name,
            ])->all());
        }

        if ($this->option('symptom')) {
            $matches = DB::table('main_symptoms')
                ->where('symptom_name', 'like', '%'.$this->option('symptom').'%')
                ->orderBy('symptom_name')
                ->get(['symptom_id', 'symptom_name']);
            foreach ($matches as $symptom) {
                $questions = DB::table('adaptive_question_rules as rule')
                    ->join('adaptive_questions as question', 'question.id', '=', 'rule.adaptive_question_id')
                    ->join('main_symptoms as target', 'target.symptom_id', '=', 'question.question_symptom_id')
                    ->where('rule.initial_symptom_id', $symptom->symptom_id)
                    ->orderByRaw("CASE rule.question_stage WHEN 'local' THEN 1 WHEN 'associated' THEN 2 WHEN 'safety' THEN 3 ELSE 4 END")
                    ->orderBy('rule.priority')
                    ->get([
                        'question.id', 'question.question_text', 'target.symptom_name as target_symptom',
                        'rule.question_stage', 'rule.priority', 'rule.is_required', 'question.status',
                    ]);
                $this->newLine();
                $this->info("เส้นทาง Adaptive สำหรับ {$symptom->symptom_name} ({$symptom->symptom_id})");
                $this->table(
                    ['ID', 'คำถาม', 'อาการที่ตรวจ', 'กลุ่ม', 'ลำดับ', 'ต้องถาม', 'สถานะ'],
                    $questions->map(fn ($item) => [
                        $item->id, $item->question_text, $item->target_symptom,
                        $item->question_stage, $item->priority,
                        $item->is_required ? 'ใช่' : 'ไม่', $item->status,
                    ])->all(),
                );
                $this->line("รวม {$questions->count()} คำถาม; อนุมัติแล้ว {$questions->where('status', 'approved')->count()} คำถาม");
            }
        }
        $this->newLine();
        $this->warn('คำสั่งนี้ตรวจความครบถ้วนเชิงโครงสร้าง ไม่สามารถยืนยันความถูกต้องทางการแพทย์แทนผู้เชี่ยวชาญได้');

        return self::SUCCESS;
    }
}
