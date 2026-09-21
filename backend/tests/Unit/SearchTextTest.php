<?php

namespace Tests\Unit;

use App\Support\AdminTableQuery;
use App\Support\SearchText;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SearchTextTest extends TestCase
{
    public function test_it_normalizes_case_unicode_and_whitespace(): void
    {
        $this->assertSame('migraine ปวดหัว', SearchText::normalize("  MIGRAINE\t ปวดหัว  "));
    }

    #[DataProvider('matchingCases')]
    public function test_thai_aware_fuzzy_matching(string $query, string $value): void
    {
        $this->assertTrue(SearchText::matches($value, $query));
    }

    public static function matchingCases(): array
    {
        return [
            'short Thai missing vowel' => ['ถง', 'ถุง'],
            'missing tone mark' => ['เจบคอ', 'เจ็บคอ'],
            'wrong character' => ['ปวดห้ว', 'ปวดหัว'],
            'transposition' => ['migriane', 'Migraine'],
            'substring' => ['ปวดหัว', 'อาการปวดหัวมาก'],
        ];
    }

    public function test_it_rejects_unrelated_and_one_character_fuzzy_queries(): void
    {
        $this->assertFalse(SearchText::matches('ไข้หวัด', 'ปวดหัว'));
        $this->assertFalse(SearchText::matches('ถุง', 'ป'));
    }

    public function test_database_candidates_support_short_thai_missing_vowels_without_broad_single_letter_patterns(): void
    {
        $method = new \ReflectionMethod(AdminTableQuery::class, 'patterns');
        $patterns = $method->invoke(null, 'ถง');

        $this->assertContains('%ถ_ง%', $patterns);
        $this->assertNotContains('%ถ%', $patterns);
        $this->assertNotContains('%ง%', $patterns);
    }
}
