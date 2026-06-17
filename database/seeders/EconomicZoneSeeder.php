<?php

namespace Database\Seeders;

use App\Modules\Municipality\Enums\EconomicZoneKind;
use App\Modules\Municipality\Models\EconomicZone;
use App\Modules\Municipality\Models\MunicipalSector;
use App\Modules\Municipality\Models\MunicipalTerritory;
use Illuminate\Database\Seeder;
use RuntimeException;

class EconomicZoneSeeder extends Seeder
{
    public function run(): void
    {
        $territory = MunicipalTerritory::query()->where('code', 'OWE')->first();
        if ($territory === null) {
            throw new RuntimeException(
                'OwendoTerritorySeeder must be executed before EconomicZoneSeeder.',
            );
        }

        $zop = fn (string $slug): ?int => MunicipalSector::query()
            ->where('territory_id', $territory->id)
            ->where('slug', $slug)
            ->value('id');

        $quartier = fn (string $slug): ?int => MunicipalSector::query()
            ->where('territory_id', $territory->id)
            ->where('slug', $slug)
            ->value('id');

        $zones = [
            ['code' => 'OWE-ZEC-01', 'name' => 'Marché Carrefour SNI', 'slug' => 'marche-carrefour-sni', 'kind' => EconomicZoneKind::Marche, 'zop' => 'zop-centre-sni', 'quartier' => 'cite-sni', 'lat' => 0.3375, 'lng' => 9.4705],
            ['code' => 'OWE-ZEC-02', 'name' => 'Marché Port', 'slug' => 'marche-port', 'kind' => EconomicZoneKind::Marche, 'zop' => 'zop-port-industrie', 'quartier' => 'owendo-port', 'lat' => 0.2930, 'lng' => 9.4530],
            ['code' => 'OWE-ZEC-03', 'name' => 'Zone portuaire OPRAG', 'slug' => 'zone-portuaire-oprag', 'kind' => EconomicZoneKind::ZonePortuaire, 'zop' => 'zop-port-industrie', 'quartier' => 'alenakiri', 'lat' => 0.2950, 'lng' => 9.4510],
            ['code' => 'OWE-ZEC-04', 'name' => 'Zone industrielle Port', 'slug' => 'zone-industrielle-port', 'kind' => EconomicZoneKind::ZoneIndustrielle, 'zop' => 'zop-port-industrie', 'quartier' => 'owendo-port', 'lat' => 0.2900, 'lng' => 9.4500],
            ['code' => 'OWE-ZEC-05', 'name' => 'Zone industrielle Awoungou', 'slug' => 'zone-industrielle-awoungou', 'kind' => EconomicZoneKind::ZoneIndustrielle, 'zop' => 'zop-port-industrie', 'quartier' => 'awoungou', 'lat' => 0.3170, 'lng' => 9.4640],
            ['code' => 'OWE-ZEC-06', 'name' => 'Zone commerciale SNI', 'slug' => 'zone-commerciale-sni', 'kind' => EconomicZoneKind::ZoneCommerciale, 'zop' => 'zop-centre-sni', 'quartier' => 'cite-sni', 'lat' => 0.3385, 'lng' => 9.4715],
            ['code' => 'OWE-ZEC-07', 'name' => 'Zone commerciale Service Civique', 'slug' => 'zone-commerciale-service-civique', 'kind' => EconomicZoneKind::ZoneCommerciale, 'zop' => 'zop-centre-sni', 'quartier' => 'service-civique', 'lat' => 0.3245, 'lng' => 9.4715],
            ['code' => 'OWE-ZEC-08', 'name' => 'Zone commerciale Akournam', 'slug' => 'zone-commerciale-akournam', 'kind' => EconomicZoneKind::ZoneCommerciale, 'zop' => 'zop-akournam', 'quartier' => 'akournam-1', 'lat' => 0.3500, 'lng' => 9.5010],
        ];

        foreach ($zones as $zone) {
            EconomicZone::query()->updateOrCreate(
                ['code' => $zone['code']],
                [
                    'territory_id' => $territory->id,
                    'name' => $zone['name'],
                    'slug' => $zone['slug'],
                    'zone_kind' => $zone['kind'],
                    'operational_zone_id' => $zop($zone['zop']),
                    'primary_sector_id' => $quartier($zone['quartier']),
                    'center_latitude' => $zone['lat'],
                    'center_longitude' => $zone['lng'],
                    'is_active' => true,
                ],
            );
        }
    }
}
