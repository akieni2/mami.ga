<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\EconomicZone;
use App\Modules\Municipality\Models\MunicipalSector;
use App\Modules\Municipality\Models\MunicipalTerritory;

class TerritorialResolverService
{
    /**
     * @return array{
     *     territory_id: int,
     *     sector_id: int|null,
     *     operational_zone_id: int|null,
     *     arrondissement_sector_id: int|null,
     *     arrondissement_name: string|null,
     *     economic_zone_id: int|null
     * }
     */
    public function resolve(float $latitude, float $longitude, ?int $territoryId = null): array
    {
        $territory = $territoryId !== null
            ? MunicipalTerritory::query()->find($territoryId)
            : MunicipalTerritory::query()->where('code', 'OWE')->first();

        if ($territory === null) {
            throw new \RuntimeException('Territoire communal non configuré.');
        }

        $quartiers = MunicipalSector::query()
            ->where('territory_id', $territory->id)
            ->where('sector_type', 'quartier')
            ->whereNotNull('center_latitude')
            ->whereNotNull('center_longitude')
            ->get();

        $sectorId = null;
        $minDistance = PHP_FLOAT_MAX;
        $quartierMaxM = (int) config('municipality.quartier_max_distance_m', 3000);

        foreach ($quartiers as $quartier) {
            $distance = $this->haversineMeters(
                $latitude,
                $longitude,
                (float) $quartier->center_latitude,
                (float) $quartier->center_longitude,
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $sectorId = $quartier->id;
            }
        }

        $operationalZoneId = null;
        $arrondissementSectorId = null;
        $arrondissementName = null;

        if ($sectorId !== null && $minDistance <= $quartierMaxM) {
            $quartier = $quartiers->firstWhere('id', $sectorId);
            if ($quartier !== null) {
                if ($quartier->parent_id !== null) {
                    $arrondissement = MunicipalSector::query()->find($quartier->parent_id);
                    if ($arrondissement !== null) {
                        $arrondissementSectorId = $arrondissement->id;
                        $arrondissementName = $arrondissement->name;
                    }
                }

                $zopSlug = config('municipality.quartier_zop_map.'.$quartier->slug);
                if (is_string($zopSlug)) {
                    $operationalZoneId = MunicipalSector::query()
                        ->where('territory_id', $territory->id)
                        ->where('sector_type', 'zone')
                        ->where('slug', $zopSlug)
                        ->value('id');
                }
            }
        } else {
            $sectorId = null;
        }

        $economicZoneId = $this->resolveEconomicZoneId($territory->id, $latitude, $longitude);

        return [
            'territory_id' => $territory->id,
            'sector_id' => $sectorId,
            'operational_zone_id' => $operationalZoneId,
            'arrondissement_sector_id' => $arrondissementSectorId,
            'arrondissement_name' => $arrondissementName,
            'economic_zone_id' => $economicZoneId,
        ];
    }

    private function resolveEconomicZoneId(int $territoryId, float $latitude, float $longitude): ?int
    {
        $zones = EconomicZone::query()
            ->where('territory_id', $territoryId)
            ->where('is_active', true)
            ->whereNotNull('center_latitude')
            ->whereNotNull('center_longitude')
            ->get();

        $zoneId = null;
        $minDistance = PHP_FLOAT_MAX;
        $maxM = (int) config('municipality.economic_zone_max_distance_m', 5000);

        foreach ($zones as $zone) {
            $distance = $this->haversineMeters(
                $latitude,
                $longitude,
                (float) $zone->center_latitude,
                (float) $zone->center_longitude,
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $zoneId = $zone->id;
            }
        }

        return $minDistance <= $maxM ? $zoneId : null;
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }
}
