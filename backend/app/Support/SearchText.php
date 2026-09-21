<?php

namespace App\Support;

use Normalizer;

final class SearchText
{
    public static function normalize(?string $value): string
    {
        $value = trim((string) $value);
        $value = class_exists(Normalizer::class)
            ? (Normalizer::normalize($value, Normalizer::FORM_C) ?: $value)
            : $value;

        return mb_strtolower(preg_replace('/\s+/u', ' ', $value) ?? $value, 'UTF-8');
    }

    /** @return list<string> */
    public static function graphemes(string $value): array
    {
        preg_match_all('/\X/u', self::normalize($value), $matches);

        return $matches[0] ?? [];
    }

    public static function threshold(int $length): int
    {
        return match (true) {
            $length <= 1 => 0,
            $length <= 5 => 1,
            $length <= 10 => 2,
            default => max(2, (int) floor($length * 0.2)),
        };
    }

    public static function damerauLevenshtein(string $left, string $right, ?int $maximum = null): int
    {
        $a = self::graphemes($left);
        $b = self::graphemes($right);
        $aLength = count($a);
        $bLength = count($b);

        if ($maximum !== null && abs($aLength - $bLength) > $maximum) {
            return $maximum + 1;
        }

        $previousPrevious = null;
        $previous = range(0, $bLength);

        for ($i = 1; $i <= $aLength; $i++) {
            $current = [$i];
            $rowMinimum = $i;
            for ($j = 1; $j <= $bLength; $j++) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $current[$j] = min(
                    $current[$j - 1] + 1,
                    $previous[$j] + 1,
                    $previous[$j - 1] + $cost,
                );
                if ($i > 1 && $j > 1 && $a[$i - 1] === $b[$j - 2] && $a[$i - 2] === $b[$j - 1]) {
                    $current[$j] = min($current[$j], $previousPrevious[$j - 2] + 1);
                }
                $rowMinimum = min($rowMinimum, $current[$j]);
            }
            if ($maximum !== null && $rowMinimum > $maximum) {
                return $maximum + 1;
            }
            $previousPrevious = $previous;
            $previous = $current;
        }

        return $previous[$bLength];
    }

    public static function matches(string $value, string $query): bool
    {
        $value = self::normalize($value);
        $query = self::normalize($query);
        if ($query === '' || str_contains($value, $query)) {
            return true;
        }

        $queryLength = count(self::graphemes($query));
        $threshold = self::threshold($queryLength);
        if ($threshold === 0 || $queryLength > 64) {
            return false;
        }

        foreach (preg_split('/\s+/u', $value) ?: [] as $token) {
            if (self::distanceFromSubstring($token, $query, $threshold) <= $threshold) {
                return true;
            }
        }

        return self::distanceFromSubstring($value, $query, $threshold) <= $threshold;
    }

    private static function distanceFromSubstring(string $value, string $query, int $maximum): int
    {
        $haystack = self::graphemes($value);
        $needle = self::graphemes($query);
        $best = $maximum + 1;

        for ($length = max(1, count($needle) - $maximum); $length <= count($needle) + $maximum; $length++) {
            for ($start = 0; $start + $length <= count($haystack); $start++) {
                $distance = self::damerauLevenshtein(
                    implode('', array_slice($haystack, $start, $length)),
                    $query,
                    $maximum,
                );
                $best = min($best, $distance);
                if ($best === 0) {
                    return 0;
                }
            }
        }

        return $best;
    }
}
