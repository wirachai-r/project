<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_upload_is_namespaced_to_the_authenticated_user(): void
    {
        Storage::fake('public');
        $user = $this->user('000000001');

        $response = $this->actingAs($user)->post('/api/uploads/image', [
            'folder' => 'profiles',
            'image' => UploadedFile::fake()->image('profile.jpg'),
        ]);

        $response->assertCreated();
        $path = $response->json('path');

        $this->assertStringStartsWith("profiles/{$user->user_id}/", $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_user_can_delete_a_profile_image_in_their_namespace(): void
    {
        Storage::fake('public');
        $user = $this->user('000000001');
        $path = "profiles/{$user->user_id}/00000000-0000-4000-8000-000000000001.webp";
        Storage::disk('public')->put($path, 'image');

        $this->actingAs($user)->deleteJson('/api/uploads/image', ['path' => $path])->assertOk();

        Storage::disk('public')->assertMissing($path);
    }

    public function test_user_cannot_delete_another_users_profile_image(): void
    {
        Storage::fake('public');
        $user = $this->user('000000001');
        $other = $this->user('000000002');
        $path = "profiles/{$other->user_id}/00000000-0000-4000-8000-000000000002.webp";
        Storage::disk('public')->put($path, 'image');

        $this->actingAs($user)->deleteJson('/api/uploads/image', ['path' => $path])->assertForbidden();

        Storage::disk('public')->assertExists($path);
    }

    public function test_user_can_delete_their_legacy_profile_image(): void
    {
        Storage::fake('public');
        $path = 'profiles/00000000-0000-4000-8000-000000000003.webp';
        $user = $this->user('000000001', $path);
        Storage::disk('public')->put($path, 'image');

        $this->actingAs($user)->deleteJson('/api/uploads/image', ['path' => $path])->assertOk();

        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_delete_another_users_profile_image(): void
    {
        Storage::fake('public');
        $admin = $this->user('000000001', role: 'Admin');
        $path = 'profiles/000000002/00000000-0000-4000-8000-000000000004.webp';
        Storage::disk('public')->put($path, 'image');

        $this->actingAs($admin)->deleteJson('/api/uploads/image', ['path' => $path])->assertOk();

        Storage::disk('public')->assertMissing($path);
    }

    public function test_invalid_or_missing_profile_image_is_not_deleted(): void
    {
        Storage::fake('public');
        $user = $this->user('000000001');

        $this->actingAs($user)
            ->deleteJson('/api/uploads/image', ['path' => '../profiles/file.webp'])
            ->assertUnprocessable();

        $path = "profiles/{$user->user_id}/00000000-0000-4000-8000-000000000005.webp";
        $this->actingAs($user)->deleteJson('/api/uploads/image', ['path' => $path])->assertNotFound();
    }

    private function user(string $id, ?string $profileImage = null, string $role = 'User'): User
    {
        return User::create([
            'user_id' => $id,
            'first_name' => 'Upload',
            'last_name' => 'Tester',
            'email' => "upload-{$id}@example.com",
            'password' => 'password',
            'profile_image' => $profileImage,
            'role' => $role,
        ]);
    }
}
