<?php

namespace Tests\Unit;

use App\Contracts\AiClient;
use App\Services\Ai\HealthTrendSummaryService;
use RuntimeException;
use Tests\TestCase;

class HealthTrendSummaryServiceTest extends TestCase
{
    public function test_ai_summary_is_used_when_optional_list_fields_are_missing(): void
    {
        $client = new class implements AiClient
        {
            public function generateStructured(string $instructions, array $input, array $schema): array
            {
                return ['summary' => 'AI สรุปว่าข้อมูลยังน้อยและควรบันทึกเพิ่ม'];
            }
        };

        $result = (new HealthTrendSummaryService($client))->generate([
            'follow_up_series' => [],
            'assessments' => [],
            'daily_records' => [],
        ], ['from' => '2026-09-01', 'to' => '2026-09-07']);

        $this->assertSame('ai', $result['source']);
        $this->assertSame('AI สรุปว่าข้อมูลยังน้อยและควรบันทึกเพิ่ม', $result['summary']);
        $this->assertSame([], $result['observations']);
        $this->assertSame([], $result['self_care']);
        $this->assertSame([], $result['warning_signs']);
        $this->assertSame(['continue_follow_up', 'open_health_report'], $result['allowed_actions']);
        $this->assertNotEmpty($result['disclaimer']);
    }

    public function test_fallback_can_summarize_a_single_follow_up_record(): void
    {
        $client = new class implements AiClient
        {
            public function generateStructured(string $instructions, array $input, array $schema): array
            {
                throw new RuntimeException('Provider unavailable');
            }
        };

        $result = (new HealthTrendSummaryService($client))->generate([
            'follow_up_series' => [[
                'symptom_name' => 'ไข้',
                'record_count' => 1,
                'first_severity' => 3,
                'latest_severity' => 3,
                'records' => [['severity' => 3]],
            ]],
            'assessments' => [],
            'daily_records' => [],
        ], ['from' => '2026-09-01', 'to' => '2026-09-07']);

        $this->assertSame('backend_fallback', $result['source']);
        $this->assertSame(
            'มีข้อมูลไข้เพียงครั้งเดียว จึงยังเปรียบเทียบการเปลี่ยนแปลงไม่ได้',
            $result['observations'][0],
        );
    }

    public function test_fallback_uses_care_context_from_a_tracked_episode(): void
    {
        $client = new class implements AiClient
        {
            public function generateStructured(string $instructions, array $input, array $schema): array
            {
                throw new RuntimeException('Provider unavailable');
            }
        };

        $result = (new HealthTrendSummaryService($client))->generate([
            'follow_up_series' => [],
            'assessments' => [],
            'daily_records' => [],
            'care_context' => [[
                'name' => 'ภาวะจากผลประเมินต้นทาง',
                'self_care' => 'คำแนะนำที่ผ่านการจัดเก็บในระบบ',
                'when_to_see_doctor' => 'สัญญาณที่บันทึกไว้ในระบบ',
            ]],
        ], ['from' => '2026-09-01', 'to' => '2026-09-07']);

        $this->assertSame(['คำแนะนำที่ผ่านการจัดเก็บในระบบ'], $result['self_care']);
        $this->assertSame(['สัญญาณที่บันทึกไว้ในระบบ'], $result['warning_signs']);
    }

    public function test_fallback_recommends_continuing_follow_up_and_daily_records(): void
    {
        $client = new class implements AiClient
        {
            public function generateStructured(string $instructions, array $input, array $schema): array
            {
                throw new RuntimeException('Provider unavailable');
            }
        };

        $result = (new HealthTrendSummaryService($client))->generate([
            'follow_up_series' => [[
                'symptom_name' => 'อาการที่ติดตาม',
                'record_count' => 1,
                'first_severity' => 2,
                'latest_severity' => 2,
                'records' => [['severity' => 2]],
            ]],
            'assessments' => [],
            'daily_records' => [['recorded_on' => '2026-09-07', 'status' => 'unwell']],
        ], ['from' => '2026-09-01', 'to' => '2026-09-07']);

        $this->assertCount(2, $result['self_care']);
        $this->assertStringContainsString('ระดับอาการเดิม', $result['self_care'][0]);
        $this->assertStringContainsString('บันทึกสุขภาพรายวัน', $result['self_care'][1]);
    }
}
