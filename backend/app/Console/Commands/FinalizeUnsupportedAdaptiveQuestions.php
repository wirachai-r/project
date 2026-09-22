<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FinalizeUnsupportedAdaptiveQuestions extends Command
{
    protected $signature = 'adaptive:finalize-unsupported-questions';

    protected $description = 'Deactivate unresolved adaptive questions and reject unsupported generated routes with an audit reason';

    public function handle(): int
    {
        $questions = $this->questions();
        $questionCount = 0;
        $ruleCount = 0;

        DB::transaction(function () use ($questions, &$questionCount, &$ruleCount): void {
            foreach ($questions as $questionId => $reason) {
                $questionCount += DB::table('adaptive_questions')
                    ->where('id', $questionId)
                    ->where('status', '!=', 'approved')
                    ->update([
                        'status' => 'inactive',
                        'evidence_source' => 'ไม่เปิดใช้: '.$reason,
                        'approved_by' => null,
                        'approved_at' => null,
                        'updated_at' => now(),
                    ]);

                $ruleCount += DB::table('adaptive_question_rules')
                    ->where('adaptive_question_id', $questionId)
                    ->whereNotIn('evidence_status', ['reviewed', 'verified'])
                    ->update([
                        'evidence_source' => 'ปฏิเสธเส้นทางอัตโนมัติ: '.$reason,
                        'evidence_status' => 'rejected',
                        'reviewed_by' => null,
                        'reviewed_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        });

        $this->info("Deactivated {$questionCount} unresolved questions and rejected {$ruleCount} unsupported routes.");

        return self::SUCCESS;
    }

    /** @return array<int, string> */
    private function questions(): array
    {
        return [
            12 => 'กฎเดิมเชื่อมไข้ร่วมกับแขนขาอ่อนแรงเข้ากับอาการตั้งต้นหลายกลุ่มที่ไม่สัมพันธ์กัน ต้องออกแบบบริบททางระบบประสาท/การติดเชื้อใหม่',
            31 => 'เกณฑ์ไข้นานกว่า 1 เดือนเป็นระยะเวลาเฉพาะที่ไม่มีที่มารองรับในกฎเดิม',
            36 => 'คอเอียงเชื่อมกับชัก สลบ ตะคริว และอาการเกร็งโดยไม่มีบริบทอายุ การบาดเจ็บ หรือระบบประสาท',
            39 => 'คันก้นเชื่อมจากอาการคันผิวหนังทั่วไป ไม่ได้เชื่อมจากอาการหรือหลักฐานของพยาธิเส้นด้าย',
            48 => 'ไม่มี adaptive_question_rules เชื่อมคำถามงูกัดกับอาการตั้งต้น',
            49 => 'จุดแดงหรือจ้ำเขียวเชื่อมกับอาการเลือดออกหลายชนิดโดยไม่มีบริบทสาเหตุหรือความรุนแรง',
            56 => 'คำว่า ช็อก ไม่เหมาะเป็นอาการที่ผู้ใช้ยืนยันเองและกฎเดิมรวมหลายสาเหตุฉุกเฉินเข้าด้วยกัน',
            63 => 'ไม่มี adaptive_question_rules และคำถามดีซ่านทารกแรกเกิดจำเป็นต้องมีบริบทอายุเฉพาะ',
            69 => 'ตะคริวเชื่อมกับชัก คอเอียง และอาการเกร็งหลายชนิดที่ไม่สามารถใช้แทนกันได้',
            88 => 'ทวารหนักโผล่เชื่อมจากพยาธิและปวดท้องโดยไม่มีหลักฐานรองรับเส้นทางตรง',
            92 => 'ท้องผูกเชื่อมจากบิด มูกปนเลือด และท้องเดินเรื้อรังโดยไม่มีบริบทการเปลี่ยนแปลงการขับถ่าย',
            116 => 'ปวดหลังเชื่อมจากเจ็บหน้าอกและปวดข้อโดยไม่มีตำแหน่ง ลักษณะ หรือสัญญาณร่วมที่จำเป็น',
            133 => 'ไม่มี adaptive_question_rules เชื่อมคำถามผมร่วงหรือผมบางกับอาการตั้งต้น',
            145 => 'มือจีบเกร็งเป็นคำเฉพาะที่ไม่ควรถูกใช้แทนตะคริว ชัก หรือแขนขาเคลื่อนไหวผิดปกติ',
            146 => 'มือเท้าเกร็งเชื่อมกับชัก ตะคริว และเท้าบวมโดยไม่มีบริบทสาเหตุที่จำเป็น',
            148 => 'ไม่มี adaptive_question_rules เชื่อมคำถามแมลงต่อยกับอาการตั้งต้น',
            156 => 'เลือดกำเดาไหลเชื่อมจากอาการฟัน เหงือก และเลือดออกทางเดินอาหารโดยไม่มีบริบทโรคเลือด',
            165 => 'สิ่งแปลกปลอมเข้าจมูกเชื่อมจากหวัด คัดจมูก และคันคอ ซึ่งไม่ใช่หลักฐานว่ามีสิ่งแปลกปลอม',
        ];
    }
}
