<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicMediaController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        abort_if(
            str_contains($path, '..') || str_contains($path, '\\') || ! Storage::disk('public')->exists($path),
            404
        );

        return response()->file(Storage::disk('public')->path($path), [
            'Access-Control-Allow-Origin' => '*',
            'Cross-Origin-Resource-Policy' => 'cross-origin',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
