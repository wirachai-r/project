<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ImageStorage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicMediaController extends Controller
{
    public function show(string $path): StreamedResponse
    {
        $disk = ImageStorage::disk();
        abort_if(
            str_contains($path, '..') || str_contains($path, '\\') || ! $disk->exists($path),
            404
        );

        return $disk->response($path, null, [
            'Access-Control-Allow-Origin' => '*',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
