<?php

namespace Tests\Unit;

use App\Services\Ai\ServiceAnalysisRules;
use PHPUnit\Framework\TestCase;

class ServiceAnalysisRulesTest extends TestCase
{
    public function test_clarification_rules_prioritize_unanswered_high_value_information(): void
    {
        $rules = ServiceAnalysisRules::clarification();

        $this->assertStringContainsString('ข้อมูลที่ทราบแล้ว', $rules);
        $this->assertStringContainsString('ช่วยแยก answer_choices ได้มากที่สุด', $rules);
        $this->assertStringContainsString('ห้ามแสดงกระบวนการคิดภายใน', $rules);
    }

    public function test_guidance_rules_keep_backend_results_authoritative(): void
    {
        $rules = ServiceAnalysisRules::guidance();

        $this->assertStringContainsString('results ซึ่ง backend คำนวณแล้วเป็นข้อสรุปหลัก', $rules);
        $this->assertStringContainsString('ห้ามคำนวณผลหรือระดับความเร่งด่วนใหม่', $rules);
        $this->assertStringContainsString('ข้อมูลคำตอบขัดกัน', $rules);
    }

    public function test_trend_rules_compare_only_compatible_longitudinal_data(): void
    {
        $rules = ServiceAnalysisRules::healthTrend();

        $this->assertStringContainsString('เป็นคนละแหล่งหลักฐาน', $rules);
        $this->assertStringContainsString('ดีขึ้น คงที่ แย่ลง ผันผวน หรือข้อมูลไม่พอ', $rules);
        $this->assertStringContainsString('ห้ามให้ค่าเฉลี่ยกลบเหตุการณ์สำคัญ', $rules);
    }
}
