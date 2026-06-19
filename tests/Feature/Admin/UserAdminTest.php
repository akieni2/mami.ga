<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(2)->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Utilisateurs');
    }

    public function test_admin_can_create_municipal_agent(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.agents.store'), [
                'name' => 'Agent Owendo',
                'email' => 'agent.owendo@mami.ga',
                'phone' => '+24106000000',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $user = User::query()->where('email', 'agent.owendo@mami.ga')->first();
        $this->assertNotNull($user);
        $response->assertRedirect(route('admin.users.show', $user));

        $this->assertTrue($user->hasRole(MamiRole::MunicipalAgent->value));
        $this->assertFalse($user->is_admin);
    }

    public function test_admin_can_attach_and_detach_role(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.roles.attach', $user), [
                'role_slug' => MamiRole::TaxiCustomer->value,
            ])
            ->assertRedirect();

        $this->assertTrue($user->fresh()->hasRole(MamiRole::TaxiCustomer->value));

        $this->actingAs($admin)
            ->delete(route('admin.users.roles.detach', [$user, MamiRole::TaxiCustomer->value]))
            ->assertRedirect();

        $this->assertFalse($user->fresh()->hasRole(MamiRole::TaxiCustomer->value));
    }
}
