<?php

namespace Tests\Unit;

use App\Services\Ai\CoreAnalysisRules;
use PHPUnit\Framework\TestCase;

class CoreAnalysisRulesTest extends TestCase
{
    public function test_rules_require_analysis_before_follow_up_questions(): void
    {
        $instructions = CoreAnalysisRules::instructions();

        $this->assertStringContainsString('วิเคราะห์ข้อมูลที่มีอยู่ทั้งหมดก่อน', $instructions);
        $this->assertStringContainsString('ห้ามถามซ้ำ', $instructions);
        $this->assertStringContainsString('หากข้อมูลเพียงพอสำหรับคำแนะนำที่ปลอดภัย ให้ดำเนินการต่อโดยไม่ถามเพิ่ม', $instructions);
        $this->assertStringContainsString('สัญญาณฉุกเฉินหรือสัญญาณเตือน', $instructions);
        $this->assertStringContainsString('ไม่ใช่การวินิจฉัย', $instructions);
    }
}
