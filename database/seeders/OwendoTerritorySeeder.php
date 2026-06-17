<?php

namespace Database\Seeders;

use App\Modules\Municipality\Models\MunicipalSector;
use App\Modules\Municipality\Models\MunicipalTerritory;
use Illuminate\Database\Seeder;

class OwendoTerritorySeeder extends Seeder
{
    public function run(): void
    {
        $territory = MunicipalTerritory::query()->updateOrCreate(
            ['code' => 'OWE'],
            [
                'name' => 'Owendo',
                'bounds_sw_lat' => 0.2750,
                'bounds_sw_lng' => 9.4450,
                'bounds_ne_lat' => 0.3650,
                'bounds_ne_lng' => 9.5250,
            ],
        );

        $arr1 = MunicipalSector::query()->updateOrCreate(
            ['territory_id' => $territory->id, 'slug' => 'arrondissement-1'],
            [
                'name' => '1er arrondissement',
                'code' => 'OWE-ARR-01',
                'sector_type' => 'secteur',
            ],
        );

        $arr2 = MunicipalSector::query()->updateOrCreate(
            ['territory_id' => $territory->id, 'slug' => 'arrondissement-2'],
            [
                'name' => '2e arrondissement',
                'code' => 'OWE-ARR-02',
                'sector_type' => 'secteur',
            ],
        );

        $quartiers = [
            ['slug' => 'akournam-1', 'name' => 'Akournam 1', 'code' => 'OWE-Q-001', 'parent' => $arr1, 'lat' => 0.3520, 'lng' => 9.4980],
            ['slug' => 'cite-sni', 'name' => 'Cité SNI', 'code' => 'OWE-Q-002', 'parent' => $arr1, 'lat' => 0.3380, 'lng' => 9.4710],
            ['slug' => 'cite-octra', 'name' => 'Cité OCTRA', 'code' => 'OWE-Q-003', 'parent' => $arr1, 'lat' => 0.3310, 'lng' => 9.4780],
            ['slug' => 'awoungou', 'name' => 'Awoungou', 'code' => 'OWE-Q-004', 'parent' => $arr1, 'lat' => 0.3180, 'lng' => 9.4650],
            ['slug' => 'service-civique', 'name' => 'Service Civique', 'code' => 'OWE-Q-005', 'parent' => $arr1, 'lat' => 0.3250, 'lng' => 9.4720],
            ['slug' => 'alenakiri', 'name' => 'Alénakiri', 'code' => 'OWE-Q-006', 'parent' => $arr1, 'lat' => 0.3050, 'lng' => 9.4580],
            ['slug' => 'owendo-port', 'name' => 'Owendo Port', 'code' => 'OWE-Q-007', 'parent' => $arr1, 'lat' => 0.2920, 'lng' => 9.4520],
            ['slug' => 'virie', 'name' => 'Virié', 'code' => 'OWE-Q-008', 'parent' => $arr1, 'lat' => 0.3280, 'lng' => 9.4880],
            ['slug' => 'akournam-2', 'name' => 'Akournam 2', 'code' => 'OWE-Q-009', 'parent' => $arr2, 'lat' => 0.3480, 'lng' => 9.5050],
            ['slug' => 'igoumie', 'name' => 'Igoumié', 'code' => 'OWE-Q-010', 'parent' => $arr2, 'lat' => 0.3350, 'lng' => 9.5100],
            ['slug' => 'mbila-nyambi', 'name' => 'Mbila-Nyambi', 'code' => 'OWE-Q-011', 'parent' => $arr2, 'lat' => 0.3220, 'lng' => 9.5020],
            ['slug' => 'pointe-claire', 'name' => 'Pointe Claire', 'code' => 'OWE-Q-012', 'parent' => $arr2, 'lat' => 0.3100, 'lng' => 9.5150],
            ['slug' => 'ile-coniquet', 'name' => 'Île Coniquet', 'code' => 'OWE-Q-013', 'parent' => $arr2, 'lat' => 0.2980, 'lng' => 9.5200],
        ];

        foreach ($quartiers as $q) {
            MunicipalSector::query()->updateOrCreate(
                ['territory_id' => $territory->id, 'slug' => $q['slug']],
                [
                    'name' => $q['name'],
                    'code' => $q['code'],
                    'sector_type' => 'quartier',
                    'parent_id' => $q['parent']->id,
                    'center_latitude' => $q['lat'],
                    'center_longitude' => $q['lng'],
                ],
            );
        }

        $zones = [
            ['slug' => 'zop-port-industrie', 'name' => 'Zone Port & Industrie', 'code' => 'OWE-ZOP-01', 'lat' => 0.2950, 'lng' => 9.4510],
            ['slug' => 'zop-centre-sni', 'name' => 'Zone Centre & SNI', 'code' => 'OWE-ZOP-02', 'lat' => 0.3380, 'lng' => 9.4710],
            ['slug' => 'zop-akournam', 'name' => 'Zone Akournam', 'code' => 'OWE-ZOP-03', 'lat' => 0.3500, 'lng' => 9.5010],
            ['slug' => 'zop-littoral-est', 'name' => 'Zone Littoral Est', 'code' => 'OWE-ZOP-04', 'lat' => 0.3150, 'lng' => 9.5120],
        ];

        foreach ($zones as $zone) {
            MunicipalSector::query()->updateOrCreate(
                ['territory_id' => $territory->id, 'slug' => $zone['slug']],
                [
                    'name' => $zone['name'],
                    'code' => $zone['code'],
                    'sector_type' => 'zone',
                    'center_latitude' => $zone['lat'],
                    'center_longitude' => $zone['lng'],
                ],
            );
        }
    }
}
