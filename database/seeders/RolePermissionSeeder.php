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
            ['slug' => 'municipality.reports.create', 'name' => 'Créer un signalement citoyen', 'module' => 'municipality'],
            ['slug' => 'municipality.reports.manage', 'name' => 'Gérer les signalements citoyens', 'module' => 'municipality'],
            ['slug' => 'municipality.operators.manage', 'name' => 'Gérer le registre économique', 'module' => 'municipality'],
            ['slug' => 'economic_operator.create', 'name' => 'Enrôler un opérateur économique', 'module' => 'municipality'],
            ['slug' => 'economic_operator.update', 'name' => 'Modifier un opérateur économique', 'module' => 'municipality'],
            ['slug' => 'economic_operator.view', 'name' => 'Consulter les opérateurs économiques', 'module' => 'municipality'],
            ['slug' => 'economic_operator.inspect', 'name' => 'Effectuer un contrôle terrain', 'module' => 'municipality'],
            ['slug' => 'municipal.tax.view', 'name' => 'Consulter le moteur fiscal', 'module' => 'municipality'],
            ['slug' => 'municipal.tax.manage', 'name' => 'Gérer taxes et objectifs', 'module' => 'municipality'],
            ['slug' => 'municipal.tax.assign', 'name' => 'Affecter les taxes aux opérateurs', 'module' => 'municipality'],
            ['slug' => 'municipal.cash_session.open', 'name' => 'Ouvrir une session de caisse', 'module' => 'municipality'],
            ['slug' => 'municipal.cash_session.close', 'name' => 'Fermer une session de caisse', 'module' => 'municipality'],
            ['slug' => 'municipal.payment.collect', 'name' => 'Encaisser des taxes terrain', 'module' => 'municipality'],
            ['slug' => 'municipal.payment.collect_without_gps', 'name' => 'Encaisser sans contrainte GPS (superviseur)', 'module' => 'municipality'],
            ['slug' => 'municipal.fiscal.view', 'name' => 'Consulter la situation fiscale', 'module' => 'municipality'],
            ['slug' => 'municipal.receipt.annul', 'name' => 'Annuler ou rembourser une quittance', 'module' => 'municipality'],
            ['slug' => 'municipal.cash_session.open_without_mission', 'name' => 'Ouvrir caisse sans mission active', 'module' => 'municipality'],
            ['slug' => 'municipal.finance.dashboard.view', 'name' => 'Consulter le tableau de bord DAF', 'module' => 'municipality'],
            ['slug' => 'municipal.finance.mission.view', 'name' => 'Consulter les missions financières', 'module' => 'municipality'],
            ['slug' => 'municipal.finance.mission.manage', 'name' => 'Gérer les missions financières', 'module' => 'municipality'],
            ['slug' => 'municipal.finance.mission.authorize', 'name' => 'Autoriser les missions financières', 'module' => 'municipality'],
            ['slug' => 'municipal.finance.cash_session.supervise', 'name' => 'Superviser les caisses terrain', 'module' => 'municipality'],
            ['slug' => 'municipal.finance.cash_session.admin_close', 'name' => 'Clôturer administrativement une caisse', 'module' => 'municipality'],
            ['slug' => 'municipal.finance.journal.view', 'name' => 'Consulter le journal financier municipal', 'module' => 'municipality'],
            ['slug' => 'municipal.finance.remittance.view', 'name' => 'Consulter les reversements trésor', 'module' => 'municipality'],
            ['slug' => 'municipal.finance.remittance.manage', 'name' => 'Préparer les reversements trésor', 'module' => 'municipality'],
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
            MamiRole::Citizen->value => ['commerce.merchants.view', 'municipality.reports.create'],
            MamiRole::TaxiCustomer->value => ['taxi.rides.request', 'commerce.merchants.view', 'municipality.reports.create'],
            MamiRole::TaxiDriver->value => ['taxi.rides.dispatch'],
            MamiRole::CarpoolDriver->value => ['carpool.trips.publish'],
            MamiRole::CarpoolPassenger->value => ['carpool.trips.book', 'commerce.merchants.view'],
            MamiRole::TransportCustomer->value => ['transport.requests.create', 'commerce.merchants.view'],
            MamiRole::TransportDriver->value => ['transport.missions.manage'],
            MamiRole::Merchant->value => ['commerce.merchants.manage'],
            MamiRole::MunicipalAgent->value => [
                'municipality.dashboard.view',
                'municipality.collections.manage',
                'municipality.reports.manage',
                'economic_operator.create',
                'economic_operator.update',
                'economic_operator.view',
                'economic_operator.inspect',
                'municipal.cash_session.open',
                'municipal.cash_session.close',
                'municipal.payment.collect',
                'municipal.fiscal.view',
            ],
            MamiRole::MunicipalSupervisor->value => [
                'municipality.operators.manage',
                'economic_operator.view',
                'municipal.fiscal.view',
                'municipal.finance.cash_session.supervise',
                'municipal.finance.journal.view',
            ],
            MamiRole::Daf->value => [
                'municipal.finance.dashboard.view',
                'municipal.finance.mission.view',
                'municipal.finance.mission.manage',
                'municipal.finance.mission.authorize',
                'municipal.finance.cash_session.supervise',
                'municipal.finance.cash_session.admin_close',
                'municipal.finance.journal.view',
                'municipal.finance.remittance.view',
                'municipal.finance.remittance.manage',
                'municipal.fiscal.view',
                'municipal.tax.view',
                'municipal.cash_session.open_without_mission',
            ],
            MamiRole::DafAdjoint->value => [
                'municipal.finance.dashboard.view',
                'municipal.finance.mission.view',
                'municipal.finance.mission.manage',
                'municipal.finance.mission.authorize',
                'municipal.finance.cash_session.supervise',
                'municipal.finance.cash_session.admin_close',
                'municipal.finance.journal.view',
                'municipal.finance.remittance.view',
                'municipal.fiscal.view',
                'municipal.tax.view',
            ],
            MamiRole::CaissierCentral->value => [
                'municipal.cash_session.open',
                'municipal.cash_session.close',
                'municipal.payment.collect',
                'municipal.fiscal.view',
                'municipal.finance.mission.view',
            ],
            MamiRole::ControleurFinancier->value => [
                'municipal.finance.dashboard.view',
                'municipal.finance.mission.view',
                'municipal.finance.cash_session.supervise',
                'municipal.finance.cash_session.admin_close',
                'municipal.finance.journal.view',
                'municipal.fiscal.view',
            ],
            MamiRole::ReceveurMunicipal->value => [
                'municipal.finance.dashboard.view',
                'municipal.finance.remittance.view',
                'municipal.finance.remittance.manage',
                'municipal.finance.journal.view',
                'municipal.fiscal.view',
                'municipal.cash_session.open',
                'municipal.cash_session.close',
                'municipal.payment.collect',
            ],
            MamiRole::Admin->value => [
                'core.admin.access',
                'taxi.rides.manage',
                'municipality.reports.manage',
                'municipality.operators.manage',
                'municipal.tax.view',
                'municipal.tax.manage',
                'municipal.tax.assign',
                'municipal.cash_session.open',
                'municipal.cash_session.close',
                'municipal.payment.collect',
                'municipal.payment.collect_without_gps',
                'municipal.fiscal.view',
                'municipal.receipt.annul',
                'municipal.cash_session.open_without_mission',
                'municipal.finance.dashboard.view',
                'municipal.finance.mission.view',
                'municipal.finance.mission.manage',
                'municipal.finance.mission.authorize',
                'municipal.finance.cash_session.supervise',
                'municipal.finance.cash_session.admin_close',
                'municipal.finance.journal.view',
                'municipal.finance.remittance.view',
                'municipal.finance.remittance.manage',
            ],
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
