<?php

namespace App\Support;

use Carbon\CarbonImmutable;

final class HealthTime
{
    public const TIMEZONE = 'Asia/Bangkok';

    public static function localDate(string $date, string $timezone = self::TIMEZONE): CarbonImmutable
    {
        return CarbonImmutable::parse($date, $timezone)->startOfDay();
    }

    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::TIMEZONE)->startOfDay();
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public static function utcRange(string $from, string $to, string $timezone = self::TIMEZONE): array
    {
        return [
            self::localDate($from, $timezone)->utc(),
            self::localDate($to, $timezone)->endOfDay()->utc(),
        ];
    }
}
