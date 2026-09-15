<?php

namespace App\Support;

class ContentImageStorage
{
    private const PATH_PATTERN = '~^(?:(?:diseases|articles|first_aids|notifications)/[a-f0-9-]+|profiles/(?:[^/]+/)?[a-f0-9-]+)\.webp$~i';

    /**
     * Return the public-disk image paths referenced by paths, URLs, or HTML values.
     *
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    public static function paths(array $values): array
    {
        $paths = [];

        foreach ($values as $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }

            preg_match_all(
                '~(?:https?://[^/"\']+)?/storage/((?:(?:diseases|articles|first_aids|notifications)/[a-f0-9-]+|profiles/(?:[^/]+/)?[a-f0-9-]+)\.webp)~i',
                $value,
                $matches,
            );

            foreach ($matches[1] ?? [] as $path) {
                $paths[] = $path;
            }

            $plainPath = ltrim($value, '/');
            if (preg_match(self::PATH_PATTERN, $plainPath)) {
                $paths[] = $plainPath;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * Delete images which were referenced before an update but no longer are.
     *
     * @param  array<int, mixed>  $before
     * @param  array<int, mixed>  $after
     */
    public static function deleteRemoved(array $before, array $after): void
    {
        self::delete(array_diff(self::paths($before), self::paths($after)));
    }

    /** @param array<int, string> $paths */
    public static function delete(array $paths): void
    {
        if ($paths !== []) {
            ImageStorage::disk()->delete($paths);
        }
    }
}
