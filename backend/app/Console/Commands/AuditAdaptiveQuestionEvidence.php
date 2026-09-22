<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditAdaptiveQuestionEvidence extends Command
{
    private const GENERATED_PREFIXES = [
        'Generated candidate from internal disease-symptom co-occurrence and taxonomy',
        'สร้างอัตโนมัติจาก disease_symptoms ภายในระบบ',
    ];

    protected $signature = 'adaptive:audit-evidence {--repair : Demote approved generated placeholders to draft}';

    protected $description = 'Audit Adaptive questions and prevent generated provenance from being treated as medical evidence';

    public function handle(): int
    {
        $generated = DB::table('adaptive_questions')
            ->where(fn ($query) => collect(self::GENERATED_PREFIXES)->each(
                fn (string $prefix) => $query->orWhere('evidence_source', 'like', $prefix.'%'),
            ));
        $approvedGenerated = (clone $generated)->where('status', 'approved')->count();
        $withUrl = DB::table('adaptive_questions')->where('evidence_source', 'like', '%http%')->count();
        $withoutEvidence = DB::table('adaptive_questions')
            ->where(fn ($query) => $query->whereNull('evidence_source')->orWhere('evidence_source', ''))
            ->count();
        $ruleTotal = DB::table('adaptive_question_rules')->count();
        $ruleWithEvidence = DB::table('adaptive_question_rules')
            ->whereNotNull('evidence_source')->where('evidence_source', '!=', '')->count();
        $ruleVerified = DB::table('adaptive_question_rules')->where('evidence_status', 'verified')->count();
        $inactiveQuestions = DB::table('adaptive_questions')->where('status', 'inactive')->count();
        $rejectedRules = DB::table('adaptive_question_rules')->where('evidence_status', 'rejected')->count();
        $ruleReviewed = DB::table('adaptive_question_rules')->where('evidence_status', 'reviewed')->count();
        $ruleWithUrl = DB::table('adaptive_question_rules')->where('evidence_source', 'like', '%http%')->count();

        $this->table(['รายการ', 'จำนวน'], [
            ['คำถามทั้งหมด', DB::table('adaptive_questions')->count()],
            ['มี URL แหล่งอ้างอิง', $withUrl],
            ['ยังไม่มีแหล่งอ้างอิง', $withoutEvidence],
            ['เป็นข้อความที่มาจากระบบอัตโนมัติ', (clone $generated)->count()],
            ['อนุมัติผิดพลาดทั้งที่เป็นข้อความอัตโนมัติ', $approvedGenerated],
            ['เส้นทางอาการตั้งต้น → คำถามทั้งหมด', $ruleTotal],
            ['เส้นทางที่มีที่มาตรวจย้อนกลับได้', $ruleWithEvidence],
            ['เส้นทางที่มี URL ภายนอก', $ruleWithUrl],
            ['เส้นทางที่ตรวจแหล่งแล้ว', $ruleReviewed],
            ['เส้นทางที่ผู้เชี่ยวชาญยืนยันแล้ว', $ruleVerified],
            ['คำถามที่ปิดใช้พร้อมเหตุผล', $inactiveQuestions],
            ['เส้นทางที่ปฏิเสธพร้อมเหตุผล', $rejectedRules],
        ]);

        if (! $this->option('repair')) {
            return self::SUCCESS;
        }

        $updated = (clone $generated)->update([
            'status' => 'draft',
            'evidence_source' => null,
            'approved_by' => null,
            'approved_at' => null,
            'updated_at' => now(),
        ]);

        $this->info("ล้าง placeholder และเปลี่ยนกลับเป็น draft {$updated} คำถามแล้ว");

        return self::SUCCESS;
    }
}
