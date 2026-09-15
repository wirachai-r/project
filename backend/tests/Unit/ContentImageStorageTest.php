<?php

namespace Tests\Unit;

use App\Support\ContentImageStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentImageStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.image_disk' => 'public']);
    }

    public function test_it_extracts_supported_image_paths_from_html_urls_and_plain_paths(): void
    {
        $paths = ContentImageStorage::paths([
            '<img src="/storage/articles/842d2c21-b1f1-4cd9-b7ec-95ec1f70c84e.webp">',
            '<img src="https://example.test/storage/diseases/11111111-1111-1111-1111-111111111111.webp">',
            'first_aids/22222222-2222-2222-2222-222222222222.webp',
            '/storage/profiles/000000001-44444444-4444-4444-4444-444444444444.webp',
            '/storage/profiles/user-1/33333333-3333-3333-3333-333333333333.webp',
            '/storage/profiles/1/33333333-3333-3333-3333-333333333333.webp',
        ]);

        $this->assertSame([
            'articles/842d2c21-b1f1-4cd9-b7ec-95ec1f70c84e.webp',
            'diseases/11111111-1111-1111-1111-111111111111.webp',
            'first_aids/22222222-2222-2222-2222-222222222222.webp',
            'profiles/000000001-44444444-4444-4444-4444-444444444444.webp',
            'profiles/user-1/33333333-3333-3333-3333-333333333333.webp',
            'profiles/1/33333333-3333-3333-3333-333333333333.webp',
        ], $paths);
    }

    public function test_it_deletes_only_paths_removed_from_the_new_values(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('articles/aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa.webp', 'old');
        Storage::disk('public')->put('articles/bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb.webp', 'kept');

        ContentImageStorage::deleteRemoved(
            [
                '/storage/articles/aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa.webp',
                '/storage/articles/bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb.webp',
            ],
            ['/storage/articles/bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb.webp'],
        );

        Storage::disk('public')->assertMissing('articles/aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa.webp');
        Storage::disk('public')->assertExists('articles/bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb.webp');
    }
}
