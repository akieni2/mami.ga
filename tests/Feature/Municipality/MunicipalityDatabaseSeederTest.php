<?php

namespace Tests\Feature\Municipality;

use App\Modules\Municipality\Models\EconomicOperatorCategory;
use App\Modules\Municipality\Models\EconomicZone;
use App\Modules\Municipality\Models\MunicipalTerritory;
use Database\Seeders\EconomicZoneSeeder;
use Database\Seeders\MunicipalityDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class MunicipalityDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_economic_zone_seeder_fails_without_territory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'OwendoTerritorySeeder must be executed before EconomicZoneSeeder.',
        );

        $this->seed(EconomicZoneSeeder::class);
    }

    public function test_municipality_database_seeder_installs_full_reference_data(): void
    {
        $this->seed(MunicipalityDatabaseSeeder::class);

        $this->assertSame(1, MunicipalTerritory::query()->where('code', 'OWE')->count());
        $this->assertGreaterThanOrEqual(10, EconomicOperatorCategory::query()->count());
        $this->assertSame(8, EconomicZone::query()->count());
    }
}
