<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class AdminTableQuery
{
    /**
     * Apply a small, database-portable fuzzy search.
     *
     * Besides a normal contains search, each generated pattern tolerates one
     * missing, inserted, or substituted character. This works for Thai text
     * as well as English and does not require a database-specific extension.
     */
    public static function fuzzySearch(
        Builder $query,
        ?string $search,
        string $idColumn,
        array $textColumns,
    ): Builder {
        $term = trim((string) $search);

        if ($term === '') {
            return $query;
        }

        $patterns = self::patterns($term);

        return $query->where(function (Builder $nested) use ($idColumn, $textColumns, $patterns) {
            foreach (array_merge([$idColumn], $textColumns) as $column) {
                foreach ($patterns as $pattern) {
                    $nested->orWhere($column, 'like', $pattern);
                }
            }
        });
    }

    private static function patterns(string $term): array
    {
        $characters = preg_split('//u', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $patterns = ['%' . self::escapeLike($term) . '%'];

        // Very short queries would otherwise be too broad.
        if (count($characters) >= 3) {
            foreach (array_keys($characters) as $index) {
                $left = self::escapeLike(implode('', array_slice($characters, 0, $index)));
                $right = self::escapeLike(implode('', array_slice($characters, $index + 1)));
                $patterns[] = '%' . $left . '%' . $right . '%';
            }
        }

        return array_values(array_unique($patterns));
    }

    private static function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
