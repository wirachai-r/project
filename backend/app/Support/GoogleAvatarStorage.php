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
            $response = Http::accept('image/*')->timeout(8)->get($url);

            if (! $response->successful() || strlen($response->body()) > self::MAX_BYTES) {
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
