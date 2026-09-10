<?php

namespace Tests\Unit;

use App\Support\HealthTime;
use PHPUnit\Framework\TestCase;

class HealthTimeTest extends TestCase
{
    public function test_thailand_calendar_day_is_converted_to_the_correct_utc_range(): void
    {
        [$from, $to] = HealthTime::utcRange('2026-09-09', '2026-09-09');

        $this->assertSame('2026-09-08 17:00:00', $from->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-09 16:59:59', $to->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $from->timezoneName);
        $this->assertSame('UTC', $to->timezoneName);
    }
}
