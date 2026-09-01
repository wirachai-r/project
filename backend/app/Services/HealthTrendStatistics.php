<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class HealthTrendStatistics
{
    public function analyze(
        Collection $followUps,
        Collection $dailyRecords,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        $periodDays = (int) $from->diffInDays($to) + 1;
        $severity = $followUps->pluck('severity')->filter(fn ($value) => $value !== null)->values();
        $temperature = $followUps->pluck('temperature')->filter(fn ($value) => $value !== null)->values();
        $recordedDays = $dailyRecords->pluck('recorded_on')
            ->filter()
            ->map(fn ($date) => method_exists($date, 'format') ? $date->format('Y-m-d') : (string) $date)
            ->unique()
            ->count();
        $wellDays = $dailyRecords->where('status', 'well')->pluck('recorded_on')
            ->filter()
            ->map(fn ($date) => method_exists($date, 'format') ? $date->format('Y-m-d') : (string) $date)
            ->unique()
            ->count();
        $unwellDays = $dailyRecords->where('status', 'unwell')->pluck('recorded_on')
            ->filter()
            ->map(fn ($date) => method_exists($date, 'format') ? $date->format('Y-m-d') : (string) $date)
            ->unique()
            ->count();

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'days' => $periodDays,
            ],
            'data_completeness' => [
                'recorded_days' => $recordedDays,
                'period_days' => $periodDays,
                'coverage_percent' => round(($recordedDays / $periodDays) * 100, 1),
            ],
            'severity' => $this->seriesStatistics($severity),
            'temperature' => $this->seriesStatistics($temperature),
            'daily_status' => [
                'well_days' => $wellDays,
                'unwell_days' => $unwellDays,
                'total_recorded_days' => $recordedDays,
            ],
        ];
    }

    private function seriesStatistics(Collection $values): array
    {
        if ($values->isEmpty()) {
            return [
                'count' => 0,
                'average' => null,
                'minimum' => null,
                'maximum' => null,
                'change' => null,
                'direction' => 'insufficient_data',
            ];
        }

        $change = $values->count() > 1
            ? round((float) $values->last() - (float) $values->first(), 2)
            : null;

        return [
            'count' => $values->count(),
            'average' => round((float) $values->average(), 2),
            'minimum' => (float) $values->min(),
            'maximum' => (float) $values->max(),
            'change' => $change,
            'direction' => match (true) {
                $change === null => 'insufficient_data',
                $change > 0 => 'increased',
                $change < 0 => 'decreased',
                default => 'unchanged',
            },
        ];
    }
}
