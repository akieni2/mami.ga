<?php

namespace Tests\Feature\Municipality;

use App\Modules\Municipality\Models\MunicipalSector;
use App\Modules\Municipality\Models\MunicipalTerritory;
use Database\Seeders\OwendoTerritorySeeder;

class OwendoTerritorySeederTest extends MunicipalityTestCase
{
    public function test_seeder_is_idempotent(): void
    {
        $this->seed(OwendoTerritorySeeder::class);
        $territoryCount = MunicipalTerritory::query()->count();
        $sectorCount = MunicipalSector::query()->count();

        $this->seed(OwendoTerritorySeeder::class);

        $this->assertSame($territoryCount, MunicipalTerritory::query()->count());
        $this->assertSame($sectorCount, MunicipalSector::query()->count());
        $this->assertSame(1, MunicipalTerritory::query()->where('code', 'OWE')->count());
        $this->assertSame(13, MunicipalSector::query()->where('sector_type', 'quartier')->count());
        $this->assertSame(4, MunicipalSector::query()->where('sector_type', 'zone')->count());
    }
}
