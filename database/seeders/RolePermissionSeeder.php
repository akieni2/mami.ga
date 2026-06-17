<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Permission;
use App\Modules\Core\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['slug' => 'taxi.rides.request', 'name' => 'Demander une course', 'module' => 'taxi'],
            ['slug' => 'taxi.rides.dispatch', 'name' => 'Dispatcher des courses', 'module' => 'taxi'],
            ['slug' => 'taxi.rides.manage', 'name' => 'Gérer les courses', 'module' => 'taxi'],
            ['slug' => 'carpool.trips.publish', 'name' => 'Publier un trajet', 'module' => 'carpool'],
            ['slug' => 'carpool.trips.book', 'name' => 'Réserver un trajet', 'module' => 'carpool'],
            ['slug' => 'transport.requests.create', 'name' => 'Demander un transport', 'module' => 'transport'],
            ['slug' => 'transport.missions.manage', 'name' => 'Gérer les missions', 'module' => 'transport'],
            ['slug' => 'commerce.merchants.manage', 'name' => 'Gérer les commerces', 'module' => 'commerce'],
            ['slug' => 'commerce.merchants.view', 'name' => 'Consulter les commerces', 'module' => 'commerce'],
            ['slug' => 'municipality.dashboard.view', 'name' => 'Voir le tableau de bord municipal', 'module' => 'municipality'],
            ['slug' => 'municipality.collections.manage', 'name' => 'Gérer les recouvrements', 'module' => 'municipality'],
            ['slug' => 'core.admin.access', 'name' => 'Accès administration', 'module' => 'core'],
            ['slug' => 'core.super_admin.access', 'name' => 'Accès super administration', 'module' => 'core'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                $permission,
            );
        }

        $rolePermissions = [
            MamiRole::Citizen->value => ['commerce.merchants.view'],
            MamiRole::TaxiCustomer->value => ['taxi.rides.request', 'commerce.merchants.view'],
            MamiRole::TaxiDriver->value => ['taxi.rides.dispatch'],
            MamiRole::CarpoolDriver->value => ['carpool.trips.publish'],
            MamiRole::CarpoolPassenger->value => ['carpool.trips.book', 'commerce.merchants.view'],
            MamiRole::TransportCustomer->value => ['transport.requests.create', 'commerce.merchants.view'],
            MamiRole::TransportDriver->value => ['transport.missions.manage'],
            MamiRole::Merchant->value => ['commerce.merchants.manage'],
            MamiRole::MunicipalAgent->value => ['municipality.dashboard.view', 'municipality.collections.manage'],
            MamiRole::Admin->value => ['core.admin.access', 'taxi.rides.manage'],
            MamiRole::SuperAdmin->value => Permission::query()->pluck('slug')->all(),
        ];

        foreach (MamiRole::cases() as $roleEnum) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $roleEnum->value],
                [
                    'name' => $roleEnum->label(),
                    'module' => $roleEnum->module(),
                    'description' => $roleEnum->label(),
                ],
            );

            $slugs = $rolePermissions[$roleEnum->value] ?? [];
            $permissionIds = Permission::query()->whereIn('slug', $slugs)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }

        User::query()->where('is_admin', true)->each(function (User $user): void {
            $adminRole = Role::query()->where('slug', MamiRole::Admin->value)->first();
            if ($adminRole !== null) {
                $user->roles()->syncWithoutDetaching([$adminRole->id => ['assigned_at' => now()]]);
            }
        });

        User::query()->whereHas('driver')->each(function (User $user): void {
            $driverRole = Role::query()->where('slug', MamiRole::TaxiDriver->value)->first();
            if ($driverRole !== null) {
                $user->roles()->syncWithoutDetaching([$driverRole->id => ['assigned_at' => now()]]);
            }
        });
    }
}
