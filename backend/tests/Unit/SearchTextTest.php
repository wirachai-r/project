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
            'short Thai wrong first character' => ['คุง', 'ถุง'],
            'short Thai similar consonant' => ['ฝัน', 'ฟัน'],
            'single grapheme wrong tone mark' => ['น่ำ', 'น้ำ'],
            'missing tone mark' => ['เจบคอ', 'เจ็บคอ'],
            'wrong character' => ['ปวดห้ว', 'ปวดหัว'],
            'short Thai wrong character' => ['ไว้', 'ไข้'],
            'transposition' => ['migriane', 'Migraine'],
            'substring' => ['ปวดหัว', 'อาการปวดหัวมาก'],
        ];
    }

    public function test_it_rejects_unrelated_and_one_character_queries(): void
    {
        $this->assertFalse(SearchText::matches('ไข้หวัด', 'ปวดหัว'));
        $this->assertFalse(SearchText::matches('ถุง', 'ป'));
        $this->assertFalse(SearchText::matches('มีอาการปวดร่วมด้วยหรือไม่?', 'ไข้'));
    }

    public function test_it_ignores_accidental_spaces_inside_thai_words(): void
    {
        $this->assertTrue(SearchText::matches('รับประทานได้', 'ไ   ด้'));
        $this->assertTrue(SearchText::matches('ไข้', 'ไ ข้'));
    }

    public function test_database_candidates_support_short_thai_missing_vowels_without_broad_single_letter_patterns(): void
    {
        $method = new \ReflectionMethod(AdminTableQuery::class, 'patterns');
        $patterns = $method->invoke(null, 'ถง');

        $this->assertContains('%ถ_ง%', $patterns);
        $this->assertNotContains('%ถ%', $patterns);
        $this->assertNotContains('%ง%', $patterns);
    }

    public function test_database_candidates_support_a_wrong_character_in_short_thai_words(): void
    {
        $method = new \ReflectionMethod(AdminTableQuery::class, 'patterns');
        $patterns = $method->invoke(null, 'ไว้');

        $this->assertContains('%ไ_้%', $patterns);
        $this->assertNotContains('%ไ%', $patterns);

        $toothPatterns = $method->invoke(null, 'ฟัน');
        $this->assertContains('%ฟั_%', $toothPatterns);
        $this->assertContains('%_ัน%', $toothPatterns);

        $bagPatterns = $method->invoke(null, 'คุง');
        $this->assertContains('%_ุง%', $bagPatterns);

        $tonePatterns = $method->invoke(null, 'น่ำ');
        $this->assertContains('%น_ำ%', $tonePatterns);
    }
}
