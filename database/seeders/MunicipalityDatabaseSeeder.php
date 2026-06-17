<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Installe l'intégralité des données de référence du module Municipality.
 *
 * Usage VPS vierge :
 *   php artisan db:seed --class=MunicipalityDatabaseSeeder --force
 */
class MunicipalityDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            OwendoTerritorySeeder::class,
            EconomicOperatorCategorySeeder::class,
            EconomicZoneSeeder::class,
        ]);
    }
}
