<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAppArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_core_tables_exist_after_migration(): void
    {
        foreach ([
            'roles',
            'permissions',
            'user_roles',
            'permission_role',
            'addresses',
            'locations',
            'ratings',
            'attachments',
            'payments',
            'transactions',
            'audit_logs',
        ] as $table) {
            $this->assertTrue(
                \Schema::hasTable($table),
                "Missing table: {$table}"
            );
        }
    }

    public function test_roles_seeder_creates_global_roles(): void
    {
        foreach (MamiRole::slugs() as $slug) {
            $this->assertDatabaseHas('roles', ['slug' => $slug]);
        }
    }

    public function test_app_features_includes_module_flags_without_breaking_taxi(): void
    {
        $response = $this->getJson('/api/app/features');

        $response->assertOk()
            ->assertJsonPath('data.taxi_v2_enabled', false)
            ->assertJsonPath('data.modules.taxi', true)
            ->assertJsonPath('data.modules.carpool', false);
    }

    public function test_taxi_ride_request_api_still_works(): void
    {
        $client = User::factory()->create();

        Sanctum::actingAs($client);

        $this->postJson('/api/rides/request', [
            'pickup_label' => 'Carrefour STFO',
            'destination_label' => 'Owendo SNI',
            'proposed_price' => 3000,
            'payment_method' => 'cash',
            'pickup_latitude' => 0.4160,
            'pickup_longitude' => 9.4670,
            'destination_latitude' => 0.4200,
            'destination_longitude' => 9.4800,
        ])->assertCreated();
    }

    public function test_disabled_module_returns_403(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/carpool/status')->assertForbidden();
    }

    public function test_enabled_module_status_endpoint_works(): void
    {
        config(['mami.modules.carpool' => true]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/carpool/status')
            ->assertOk()
            ->assertJsonPath('data.module', 'carpool');
    }

    public function test_user_can_have_roles_assigned(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', MamiRole::Citizen->value)->firstOrFail();

        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        $this->assertTrue($user->fresh()->hasRole(MamiRole::Citizen->value));
    }
}
