<?php

namespace Tests\Unit;

use App\Support\NotificationContent;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class NotificationContentTest extends TestCase
{
    public function test_it_unwraps_markdown_links_accidentally_saved_as_image_sources(): void
    {
        $url = 'https://example.supabase.co/storage/v1/object/public/uploads/articles/photo.webp';
        $html = '<img class="rounded" src="['.$url.']('.$url.')">';

        $this->assertSame(
            '<img class="rounded" src="'.$url.'">',
            NotificationContent::normalizeImageUrls($html),
        );
    }

    public function test_it_removes_the_origin_from_notification_images_before_storage(): void
    {
        $html = '<p>Content</p><img src="http://192.168.1.110:8000/storage/notifications/photo.webp">';

        $this->assertSame(
            '<p>Content</p><img src="/storage/notifications/photo.webp">',
            NotificationContent::normalizeImageUrls($html),
        );
    }

    public function test_it_resolves_notification_images_against_the_current_request_origin(): void
    {
        $request = Request::create('https://api.example.test/api/notifications');
        $html = '<img src="/storage/notifications/photo.webp">';

        $this->assertSame(
            '<img src="https://api.example.test/api/media/notifications/photo.webp">',
            NotificationContent::resolveImageUrls($html, $request),
        );
    }

    public function test_it_replaces_an_old_origin_when_returning_existing_content(): void
    {
        $request = Request::create('http://10.0.2.2:8000/api/notifications');
        $html = '<img src="http://localhost:8000/storage/notifications/photo.webp">';

        $this->assertSame(
            '<img src="http://10.0.2.2:8000/api/media/notifications/photo.webp">',
            NotificationContent::resolveImageUrls($html, $request),
        );
    }

    public function test_it_supports_every_rich_text_upload_folder(): void
    {
        foreach (['articles', 'diseases', 'first_aids', 'notifications'] as $folder) {
            $html = '<img src="http://localhost:8000/storage/'.$folder.'/photo.webp">';

            $this->assertSame(
                '<img src="/storage/'.$folder.'/photo.webp">',
                NotificationContent::normalizeImageUrls($html),
            );
        }
    }

    public function test_it_normalizes_media_urls_saved_back_from_the_editor(): void
    {
        $html = '<img src="http://localhost:8000/api/media/articles/photo.webp">';

        $this->assertSame(
            '<img src="/storage/articles/photo.webp">',
            NotificationContent::normalizeImageUrls($html),
        );
    }

    public function test_it_replaces_an_old_media_origin_for_mobile(): void
    {
        $request = Request::create('http://192.168.1.110:8000/api/articles/1');
        $html = '<img src="http://localhost:8000/api/media/articles/photo.webp">';

        $this->assertSame(
            '<img src="http://192.168.1.110:8000/api/media/articles/photo.webp">',
            NotificationContent::resolveImageUrls($html, $request),
        );
    }
}
