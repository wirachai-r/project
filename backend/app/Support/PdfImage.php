<?php

namespace App\Support;

use Throwable;

class PdfImage
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    private const SUPPORTED_MIME_TYPES = [
        'image/gif',
        'image/jpeg',
        'image/png',
    ];

    public static function fromImageDisk(?string $path): ?string
    {
        if (! $path || filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }

        $path = self::normalizePath($path);
        $disk = ImageStorage::disk();

        try {
            if (! $disk->exists($path) || $disk->size($path) > self::MAX_BYTES) {
                return null;
            }

            $mimeType = $disk->mimeType($path);
            if (! in_array($mimeType, self::SUPPORTED_MIME_TYPES, true)) {
                return null;
            }

            return 'data:'.$mimeType.';base64,'.base64_encode($disk->get($path));
        } catch (Throwable) {
            return null;
        }
    }

    private static function normalizePath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        return str_starts_with($path, 'storage/')
            ? substr($path, strlen('storage/'))
            : $path;
    }
}
