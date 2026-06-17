<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use Database\Seeders\OwendoTerritorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class MunicipalityTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mami.modules.municipality' => true]);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OwendoTerritorySeeder::class);
    }

    protected function citizenUser(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', MamiRole::TaxiCustomer->value)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user;
    }

    protected function municipalAgent(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $role = Role::query()->where('slug', MamiRole::Admin->value)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    protected function validReportPayload(): array
    {
        return [
            'category' => 'voirie',
            'title' => 'Nid de poule',
            'description' => 'Trou profond sur la chaussée',
            'latitude' => 0.3380,
            'longitude' => 9.4710,
            'address' => 'Carrefour SNI',
        ];
    }

    protected function territoryId(): int
    {
        return (int) \App\Modules\Municipality\Models\MunicipalTerritory::query()
            ->where('code', 'OWE')
            ->value('id');
    }
}
