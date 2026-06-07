<?php

namespace Tests\Feature;

use App\Models\DriverApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverApplicationAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_applications(): void
    {
        $this->get('/admin/driver-applications')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_admin_applications(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/driver-applications')
            ->assertForbidden();
    }

    public function test_admin_can_list_and_view_applications(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $application = DriverApplication::factory()->create([
            'first_name' => 'Paul',
            'last_name' => 'Mba',
        ]);

        $this->actingAs($admin)
            ->get('/admin/driver-applications')
            ->assertOk()
            ->assertSee('Candidatures chauffeurs')
            ->assertSee('Paul');

        $this->actingAs($admin)
            ->get('/admin/driver-applications/'.$application->id)
            ->assertOk()
            ->assertSee('Paul Mba')
            ->assertSee('Approuver');
    }

    public function test_non_admin_cannot_approve_application(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $application = DriverApplication::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.driver-applications.approve', $application))
            ->assertForbidden();
    }
}
