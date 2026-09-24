<?php

namespace Tests\Unit;

use App\Support\PdfImage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfImageTest extends TestCase
{
    public function test_it_embeds_a_supported_image_from_the_image_disk(): void
    {
        Storage::fake('public');
        config(['filesystems.image_disk' => 'public']);
        Storage::disk('public')->put('profiles/user.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));

        $image = PdfImage::fromImageDisk('/storage/profiles/user.png');

        $this->assertNotNull($image);
        $this->assertStringStartsWith('data:image/png;base64,', $image);
    }

    public function test_it_does_not_fetch_remote_images(): void
    {
        Storage::fake('public');
        config(['filesystems.image_disk' => 'public']);

        $this->assertNull(PdfImage::fromImageDisk('https://example.com/avatar.png'));
    }

    public function test_it_ignores_unsupported_files(): void
    {
        Storage::fake('public');
        config(['filesystems.image_disk' => 'public']);
        Storage::disk('public')->put('profiles/user.svg', '<svg></svg>');

        $this->assertNull(PdfImage::fromImageDisk('profiles/user.svg'));
    }
}
