<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class AdminTableQuery
{
    /**
     * Sort creation dates consistently across PostgreSQL/MySQL, keeping
     * imported legacy rows without a creation date at the end.
     */
    public static function orderByCreatedAt(
        Builder $query,
        string $direction,
        string $tieBreaker,
    ): Builder {
        return $query
            ->orderByRaw('CASE WHEN created_at IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('created_at', $direction)
            ->orderBy($tieBreaker, $direction);
    }

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
        ?string $idColumn,
        array $textColumns,
    ): Builder {
        $term = SearchText::normalize($search);

        if ($term === '') {
            return $query;
        }

        $patterns = self::patterns($term);

        $query->where(function (Builder $nested) use ($idColumn, $textColumns, $patterns, $term) {
            if ($idColumn !== null) {
                $nested->orWhereRaw('LOWER('.$nested->getQuery()->getGrammar()->wrap($idColumn).') LIKE ?', ['%'.self::escapeLike($term).'%']);
            }
            foreach ($textColumns as $column) {
                foreach ($patterns as $pattern) {
                    $nested->orWhereRaw('LOWER('.$nested->getQuery()->getGrammar()->wrap($column).') LIKE ?', [$pattern]);
                }
            }
        });

        // Stable relevance tiers: exact, prefix, substring, then fuzzy.
        if ($textColumns !== []) {
            $grammar = $query->getQuery()->getGrammar();
            $exact = [];
            $prefix = [];
            $contains = [];
            foreach ($textColumns as $column) {
                $wrapped = 'LOWER('.$grammar->wrap($column).')';
                $exact[] = "$wrapped = ?";
                $prefix[] = "$wrapped LIKE ?";
                $contains[] = "$wrapped LIKE ?";
            }
            // Bindings are grouped by tier rather than by column.
            $bindings = array_merge(
                array_fill(0, count($textColumns), $term),
                array_fill(0, count($textColumns), self::escapeLike($term).'%'),
                array_fill(0, count($textColumns), '%'.self::escapeLike($term).'%'),
            );
            $query->orderByRaw(
                'CASE WHEN '.implode(' OR ', $exact).' THEN 0 WHEN '.implode(' OR ', $prefix).' THEN 1 WHEN '.implode(' OR ', $contains).' THEN 2 ELSE 3 END',
                $bindings,
            );
        }

        return $query;
    }

    private static function patterns(string $term): array
    {
        $characters = SearchText::graphemes($term);
        $patterns = ['%'.self::escapeLike($term).'%'];

        // Generate exact one-edit LIKE candidates. Unlike the former
        // "%left%right%" form, these patterns do not allow an arbitrary gap.
        if (count($characters) >= 2 && count($characters) <= 32) {
            foreach (array_keys($characters) as $index) {
                $left = self::escapeLike(implode('', array_slice($characters, 0, $index)));
                $right = self::escapeLike(implode('', array_slice($characters, $index + 1)));
                if (count($characters) > 2) {
                    $patterns[] = '%'.$left.$right.'%';       // extra query character
                }
                // Substitution is safe for two-character queries as it keeps
                // the matched word length unchanged. Preserve Thai combining
                // marks, so ไว้ becomes the precise pattern ไ_้ rather than ไ_.
                $replacement = preg_replace('/^\P{M}/u', '_', $characters[$index]) ?? '_';
                $patterns[] = '%'.$left.$replacement.$right.'%';
            }
            for ($index = 1; $index < count($characters); $index++) {
                $left = self::escapeLike(implode('', array_slice($characters, 0, $index)));
                $right = self::escapeLike(implode('', array_slice($characters, $index)));
                $patterns[] = '%'.$left.'_'.$right.'%';   // missing query character
            }
            for ($index = 0; $index < count($characters) - 1; $index++) {
                $swapped = $characters;
                [$swapped[$index], $swapped[$index + 1]] = [$swapped[$index + 1], $swapped[$index]];
                $patterns[] = '%'.self::escapeLike(implode('', $swapped)).'%';
            }
        }

        return array_values(array_unique($patterns));
    }

    private static function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
