<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_view_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Tableau de bord');
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_admin_can_view_drivers_and_rides_pages(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/drivers')->assertOk()->assertSee('Chauffeurs');
        $this->actingAs($admin)->get('/rides')->assertOk()->assertSee('Courses');
    }

    public function test_admin_can_view_live_map_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/map')
            ->assertOk()
            ->assertSee('Carte live');
    }

    public function test_breeze_login_flow_for_admin(): void
    {
        $admin = User::factory()->create([
            'email' => 'breeze-admin@mami.ga',
            'password' => 'password',
            'is_admin' => true,
        ]);

        $this->post('/login', [
            'email' => 'breeze-admin@mami.ga',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }
}
