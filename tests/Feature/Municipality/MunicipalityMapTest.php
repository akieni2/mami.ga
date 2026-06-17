<?php

namespace Tests\Feature\Municipality;

use Laravel\Sanctum\Sanctum;

class MunicipalityMapTest extends MunicipalityTestCase
{
    public function test_map_returns_geojson_layer(): void
    {
        $agent = $this->municipalAgent();
        Sanctum::actingAs($agent);

        $this->postJson('/api/municipality/reports', array_merge(
            $this->validReportPayload(),
            ['title' => 'Éclairage défaillant', 'category' => 'eclairage'],
        ));

        $response = $this->getJson('/api/municipality/reports/map');

        $response->assertOk()
            ->assertJsonPath('data.layer', 'signalements')
            ->assertJsonStructure([
                'data' => [
                    'geojson' => [
                        'type',
                        'features' => [
                            ['type', 'geometry', 'properties' => ['id', 'reference', 'category', 'status', 'color']],
                        ],
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data.geojson.features'));
        $this->assertSame('OWE-SIG-000001', $response->json('data.geojson.features.0.properties.reference'));
    }

    public function test_map_marker_colors_by_status(): void
    {
        Sanctum::actingAs($this->municipalAgent());

        $this->postJson('/api/municipality/reports', $this->validReportPayload());

        $color = $this->getJson('/api/municipality/reports/map')
            ->json('data.geojson.features.0.properties.color');

        $this->assertSame('#E53935', $color);
    }

    public function test_map_filters_by_category_status_and_quartier(): void
    {
        Sanctum::actingAs($this->municipalAgent());

        $this->postJson('/api/municipality/reports', $this->validReportPayload());

        $this->postJson('/api/municipality/reports', array_merge(
            $this->validReportPayload(),
            ['category' => 'dechets', 'title' => 'Poubelle renversée'],
        ));

        $this->getJson('/api/municipality/reports/map?category=voirie')
            ->assertOk()
            ->assertJsonCount(1, 'data.geojson.features');

        $this->getJson('/api/municipality/reports/map?status=new')
            ->assertOk()
            ->assertJsonCount(2, 'data.geojson.features');

        $this->getJson('/api/municipality/reports/map?quartier=cite-sni')
            ->assertOk()
            ->assertJsonCount(2, 'data.geojson.features');

        $sectorId = \App\Modules\Municipality\Models\MunicipalSector::query()
            ->where('slug', 'cite-sni')
            ->value('id');

        $this->getJson('/api/municipality/reports/map?sector_id='.$sectorId)
            ->assertOk()
            ->assertJsonCount(2, 'data.geojson.features');
    }
}
