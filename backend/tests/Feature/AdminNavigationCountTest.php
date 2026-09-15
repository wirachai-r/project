<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_load_navigation_counts_in_one_request(): void
    {
        $admin = User::create([
            'user_id' => '000000901',
            'first_name' => 'Admin',
            'last_name' => 'Tester',
            'email' => 'navigation-counts@example.test',
            'password' => 'password',
            'role' => 'Admin',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/navigation-counts')
            ->assertOk()
            ->assertExactJson([
                'pending_comment_reports' => 0,
                'pending_feedback' => 0,
                'unread_notifications' => 0,
            ]);
    }
}
