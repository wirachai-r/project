<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\GoogleAvatarStorage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GoogleAvatarStorageTest extends TestCase
{
    public function test_it_caches_a_google_avatar_on_the_image_disk(): void
    {
        config(['filesystems.image_disk' => 'public']);
        Storage::fake('public');
        Http::fake([
            'https://lh3.googleusercontent.com/*' => Http::response(
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);

        $user = new User;
        $user->user_id = '11111111-1111-4111-8111-111111111111';

        $path = GoogleAvatarStorage::cache(
            $user,
            'https://lh3.googleusercontent.com/a/example=s96-c',
        );

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_it_rejects_non_google_avatar_urls(): void
    {
        Http::preventStrayRequests();

        $user = new User;
        $user->user_id = '11111111-1111-4111-8111-111111111111';

        $this->assertNull(GoogleAvatarStorage::cache($user, 'https://example.com/avatar.png'));
    }
}
