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
}
