<?php

namespace App\Support;

use Illuminate\Http\Request;

class NotificationContent
{
    private const CONTENT_FOLDERS = '(?:notifications|articles|diseases|first_aids)';

    private const MARKDOWN_IMAGE_SOURCE_PATTERN = '~(<img\b[^>]*\bsrc\s*=\s*)(["\'])\[[^\]]*\]\((https?://[^)"\']+)\)\2~i';

    private const RELATIVE_IMAGE_PATTERN = '~(<img\b[^>]*\bsrc\s*=\s*)(["\'])(/storage/'.self::CONTENT_FOLDERS.'/[^"\']+)\2~i';

    private const ABSOLUTE_IMAGE_PATTERN = '~(<img\b[^>]*\bsrc\s*=\s*)(["\'])https?://[^/"\']+(/storage/'.self::CONTENT_FOLDERS.'/[^"\']+)\2~i';

    private const MEDIA_IMAGE_PATTERN = '~(<img\b[^>]*\bsrc\s*=\s*)(["\'])(?:https?://[^/"\']+)?/api/media/('.self::CONTENT_FOLDERS.'/[^"\']+)\2~i';

    public static function normalizeImageUrls(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = preg_replace(
            self::MARKDOWN_IMAGE_SOURCE_PATTERN,
            '$1$2$3$2',
            $html,
        ) ?? $html;

        $html = preg_replace(
            self::ABSOLUTE_IMAGE_PATTERN,
            '$1$2$3$2',
            $html,
        ) ?? $html;

        return preg_replace(
            self::MEDIA_IMAGE_PATTERN,
            '$1$2/storage/$3$2',
            $html,
        ) ?? $html;
    }

    public static function resolveImageUrls(?string $html, Request $request): ?string
    {
        if ($html === null) {
            return null;
        }
        $origin = rtrim($request->getSchemeAndHttpHost(), '/');
        $html = self::normalizeImageUrls($html);

        return preg_replace_callback(
            self::RELATIVE_IMAGE_PATTERN,
            fn (array $matches) => $matches[1].$matches[2].$origin.'/api/media/'.ltrim(substr($matches[3], strlen('/storage/')), '/').$matches[2],
            $html,
        ) ?? $html;
    }
}
