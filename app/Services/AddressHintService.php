<?php

namespace App\Services;

use App\Support\Geo\GeoPoint;

/**
 * Résolution approximative d'un label texte en point de recherche dispatch.
 * P3 : fallback centre Libreville ; géocodage quartier en P3+.
 */
class AddressHintService
{
    public function resolve(?string $label): ?GeoPoint
    {
        if ($label === null || trim($label) === '') {
            return null;
        }

        // Extension future : table de hints quartiers Libreville.
        return null;
    }

    public function fallbackSearchPoint(): GeoPoint
    {
        return GeoPoint::librevilleCenter();
    }
}
