<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserFeedbackAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.image_disk' => 'public']);
    }

    public function test_user_can_attach_images_and_only_owner_can_view_them(): void
    {
        Storage::fake('public');
        $owner = $this->user('000000101', 'owner@example.test');
        $other = $this->user('000000102', 'other@example.test');

        $response = $this->actingAs($owner)->post('/api/feedback', [
            'feedback_type' => 'general',
            'category' => 'bug',
            'message' => 'หน้าจอแสดงผลผิดปกติ',
            'attachments' => [UploadedFile::fake()->image('screen.png')],
        ])->assertCreated()->assertJsonCount(1, 'data.attachments');

        $feedbackId = $response->json('data.id');
        $attachmentPath = $response->json('data.attachments.0');

        $this->assertMatchesRegularExpression(
            '/^feedbacks\/[a-f0-9-]+\.png$/',
            $attachmentPath,
        );
        Storage::disk('public')->assertExists($attachmentPath);
        $this->actingAs($owner)->get("/api/feedback/{$feedbackId}/attachments/0")->assertOk();
        $this->actingAs($other)->get("/api/feedback/{$feedbackId}/attachments/0")->assertForbidden();
    }

    private function user(string $id, string $email): User
    {
        return User::create([
            'user_id' => $id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => 'password',
        ]);
    }
}
