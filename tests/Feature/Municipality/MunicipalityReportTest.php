<?php

namespace Tests\Feature\Municipality;

use App\Modules\Municipality\Models\MunicipalityReport;
use Laravel\Sanctum\Sanctum;

class MunicipalityReportTest extends MunicipalityTestCase
{
    public function test_citizen_can_create_report_with_reference(): void
    {
        $citizen = $this->citizenUser();
        Sanctum::actingAs($citizen);

        $response = $this->postJson('/api/municipality/reports', $this->validReportPayload());

        $response->assertCreated()
            ->assertJsonPath('data.reference', 'OWE-SIG-000001')
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.category', 'voirie');

        $this->assertDatabaseHas('municipality_reports', [
            'reference' => 'OWE-SIG-000001',
            'citizen_id' => $citizen->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'subject_type' => 'municipality_report',
            'action' => 'report.created',
            'module' => 'municipality',
        ]);
    }

    public function test_citizen_can_list_own_reports(): void
    {
        $citizen = $this->citizenUser();
        Sanctum::actingAs($citizen);

        $this->postJson('/api/municipality/reports', $this->validReportPayload())->assertCreated();

        $this->getJson('/api/municipality/reports?mine=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reference', 'OWE-SIG-000001');
    }

    public function test_disabled_module_returns_403(): void
    {
        config(['mami.modules.municipality' => false]);
        Sanctum::actingAs($this->citizenUser());

        $this->postJson('/api/municipality/reports', $this->validReportPayload())
            ->assertForbidden();
    }

    public function test_reference_increments(): void
    {
        Sanctum::actingAs($this->citizenUser());

        $this->postJson('/api/municipality/reports', $this->validReportPayload())->assertCreated();

        $payload = $this->validReportPayload();
        $payload['title'] = 'Autre problème';

        $this->postJson('/api/municipality/reports', $payload)
            ->assertCreated()
            ->assertJsonPath('data.reference', 'OWE-SIG-000002');
    }

    public function test_show_report_for_owner(): void
    {
        $citizen = $this->citizenUser();
        Sanctum::actingAs($citizen);

        $id = MunicipalityReport::query()->create([
            'reference' => 'OWE-SIG-000099',
            'citizen_id' => $citizen->id,
            'category' => 'dechets',
            'title' => 'Test',
            'description' => 'Desc',
            'latitude' => 0.338,
            'longitude' => 9.471,
            'territory_id' => $this->territoryId(),
            'status' => 'new',
        ])->id;

        $this->getJson("/api/municipality/reports/{$id}")
            ->assertOk()
            ->assertJsonPath('data.reference', 'OWE-SIG-000099');
    }
}
