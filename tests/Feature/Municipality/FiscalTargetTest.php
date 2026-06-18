<?php

namespace Tests\Feature\Municipality;

use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class FiscalTargetTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    public function test_admin_can_create_collection_target(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);

        $response = $this->postJson('/api/municipality/fiscal/targets', [
            'tax_type_id' => $taxType->id,
            'fiscal_year' => 2026,
            'target_amount_xaf' => 120000000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.target_amount_xaf', '120000000.00');

        $this->assertDatabaseHas('municipal_collection_targets', [
            'tax_type_id' => $taxType->id,
            'fiscal_year' => 2026,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'collection_target.created']);
    }

    public function test_target_upsert_updates_existing_year(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);

        $this->postJson('/api/municipality/fiscal/targets', [
            'tax_type_id' => $taxType->id,
            'fiscal_year' => 2026,
            'target_amount_xaf' => 100000000,
        ])->assertCreated();

        $this->postJson('/api/municipality/fiscal/targets', [
            'tax_type_id' => $taxType->id,
            'fiscal_year' => 2026,
            'target_amount_xaf' => 150000000,
        ])->assertCreated();

        $this->assertDatabaseCount('municipal_collection_targets', 1);
        $this->assertDatabaseHas('municipal_collection_targets', [
            'target_amount_xaf' => 150000000,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'collection_target.updated']);
    }

    public function test_admin_can_list_targets(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);

        $this->postJson('/api/municipality/fiscal/targets', [
            'tax_type_id' => $taxType->id,
            'fiscal_year' => 2026,
            'target_amount_xaf' => 50000000,
        ]);

        $this->getJson('/api/municipality/fiscal/targets')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_target_requires_positive_amount(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);

        $this->postJson('/api/municipality/fiscal/targets', [
            'tax_type_id' => $taxType->id,
            'fiscal_year' => 2026,
            'target_amount_xaf' => -1,
        ])->assertUnprocessable();
    }

    public function test_citizen_cannot_create_target(): void
    {
        Sanctum::actingAs($this->citizenUser());

        $this->postJson('/api/municipality/fiscal/targets', [
            'tax_type_id' => 1,
            'fiscal_year' => 2026,
            'target_amount_xaf' => 1000,
        ])->assertForbidden();
    }
}
