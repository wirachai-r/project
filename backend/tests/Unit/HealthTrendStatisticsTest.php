<?php

namespace Tests\Unit;

use App\Services\HealthTrendStatistics;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class HealthTrendStatisticsTest extends TestCase
{
    public function test_it_summarizes_record_coverage_and_numeric_series(): void
    {
        $followUps = collect([
            (object) ['severity' => 2, 'temperature' => 36.8],
            (object) ['severity' => 4, 'temperature' => 37.2],
        ]);
        $dailyRecords = collect([
            (object) ['status' => 'well', 'recorded_on' => CarbonImmutable::parse('2026-08-01')],
            (object) ['status' => 'unwell', 'recorded_on' => CarbonImmutable::parse('2026-08-02')],
            (object) ['status' => 'well', 'recorded_on' => CarbonImmutable::parse('2026-08-03')],
        ]);

        $result = (new HealthTrendStatistics)->analyze(
            $followUps,
            $dailyRecords,
            CarbonImmutable::parse('2026-08-01'),
            CarbonImmutable::parse('2026-08-10'),
        );

        $this->assertSame(10, $result['period']['days']);
        $this->assertSame(30.0, $result['data_completeness']['coverage_percent']);
        $this->assertSame(3.0, $result['severity']['average']);
        $this->assertSame(2.0, $result['severity']['change']);
        $this->assertSame('increased', $result['severity']['direction']);
        $this->assertSame(37.0, $result['temperature']['average']);
        $this->assertSame(2, $result['daily_status']['well_days']);
        $this->assertSame(1, $result['daily_status']['unwell_days']);
    }

    public function test_it_reports_insufficient_data_for_empty_series(): void
    {
        $result = (new HealthTrendStatistics)->analyze(
            collect(),
            collect(),
            CarbonImmutable::parse('2026-08-01'),
            CarbonImmutable::parse('2026-08-07'),
        );

        $this->assertSame(0.0, $result['data_completeness']['coverage_percent']);
        $this->assertNull($result['severity']['average']);
        $this->assertSame('insufficient_data', $result['severity']['direction']);
        $this->assertNull($result['temperature']['average']);
    }
}
