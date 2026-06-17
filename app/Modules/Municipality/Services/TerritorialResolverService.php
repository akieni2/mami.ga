<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\MunicipalSector;
use App\Modules\Municipality\Models\MunicipalTerritory;

class TerritorialResolverService
{
    /**
     * @return array{territory_id: int, sector_id: int|null, operational_zone_id: int|null}
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
        if ($sectorId !== null) {
            $quartier = $quartiers->firstWhere('id', $sectorId);
            if ($quartier !== null) {
                $zopSlug = config('municipality.quartier_zop_map.'.$quartier->slug);
                if (is_string($zopSlug)) {
                    $operationalZoneId = MunicipalSector::query()
                        ->where('territory_id', $territory->id)
                        ->where('sector_type', 'zone')
                        ->where('slug', $zopSlug)
                        ->value('id');
                }
            }
        }

        return [
            'territory_id' => $territory->id,
            'sector_id' => $minDistance <= 3000 ? $sectorId : null,
            'operational_zone_id' => $operationalZoneId,
        ];
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
