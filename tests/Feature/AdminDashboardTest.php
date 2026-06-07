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
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_admin_can_view_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee('Courses aujourd');
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_admin_can_view_all_exploitation_pages(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/drivers')->assertOk()->assertSee('Chauffeurs');
        $this->actingAs($admin)->get('/admin/rides')->assertOk()->assertSee('Courses');
        $this->actingAs($admin)->get('/admin/clients')->assertOk()->assertSee('Clients');
        $this->actingAs($admin)->get('/admin/map')->assertOk()->assertSee('Carte opérationnelle');
        $this->actingAs($admin)->get('/admin/reports')->assertOk()->assertSee('Rapports');
    }

    public function test_legacy_urls_redirect_to_admin_prefix(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin/dashboard');
        $this->actingAs($admin)->get('/drivers')->assertRedirect('/admin/drivers');
        $this->actingAs($admin)->get('/rides')->assertRedirect('/admin/rides');
        $this->actingAs($admin)->get('/map')->assertRedirect('/admin/map');
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
