<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

class GoogleAvatarStorage
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    public static function cache(User $user, ?string $url): ?string
    {
        if (! self::isGoogleAvatarUrl($url)) {
            return null;
        }

        try {
            $response = null;
            foreach (self::candidateUrls($url) as $candidateUrl) {
                $candidate = Http::accept('image/avif,image/webp,image/apng,image/*,*/*;q=0.8')
                    ->withUserAgent('Mozilla/5.0 (compatible; CheckupAvatarCache/1.0)')
                    ->timeout(8)
                    ->get($candidateUrl);
                if ($candidate->successful() && strlen($candidate->body()) <= self::MAX_BYTES) {
                    $response = $candidate;
                    break;
                }
            }

            if (! $response) {
                return null;
            }

            $contentType = strtolower($response->header('Content-Type', ''));
            if (! str_starts_with($contentType, 'image/')) {
                return null;
            }

            $image = (new ImageManager(new Driver))->read($response->body());
            $image->scaleDown(width: 500, height: 500);

            $filename = 'profiles/'.$user->getKey().'/'.sha1((string) $user->getKey()).'.webp';
            if (! ImageStorage::disk()->put($filename, (string) $image->toWebp(quality: 80))) {
                return null;
            }

            return $filename;
        } catch (Throwable) {
            return null;
        }
    }

    /** @return list<string> */
    private static function candidateUrls(string $url): array
    {
        $largerAvatar = preg_replace('/=s\d+(?:-c)?$/', '=s256-c', $url);

        return array_values(array_unique(array_filter([$largerAvatar, $url])));
    }

    private static function isGoogleAvatarUrl(?string $url): bool
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $scheme === 'https'
            && ($host === 'googleusercontent.com' || str_ends_with($host, '.googleusercontent.com'));
    }
}
