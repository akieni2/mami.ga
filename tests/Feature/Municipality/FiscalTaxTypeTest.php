<?php

namespace Tests\Feature\Municipality;

use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class FiscalTaxTypeTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    public function test_admin_can_list_tax_types(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $this->createTaxType($user);

        $response = $this->getJson('/api/municipality/fiscal/taxes');

        $response->assertOk()
            ->assertJsonPath('data.0.code', 'TAX-COMMERCE');
    }

    public function test_admin_can_create_tax_type(): void
    {
        Sanctum::actingAs($this->fiscalManager());

        $response = $this->postJson('/api/municipality/fiscal/taxes', [
            'code' => 'TAX-MARCHE',
            'name' => 'Taxe marché',
            'description' => 'Taxe d\'occupation marché',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'TAX-MARCHE');

        $this->assertDatabaseHas('municipal_tax_types', ['code' => 'TAX-MARCHE']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'tax_type.created',
            'subject_type' => 'municipal_tax_type',
        ]);
    }

    public function test_duplicate_tax_code_rejected(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $this->createTaxType($user, 'TAX-COMMERCE');

        $response = $this->postJson('/api/municipality/fiscal/taxes', [
            'code' => 'TAX-COMMERCE',
            'name' => 'Autre',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['code']);
    }

    public function test_admin_can_update_tax_type(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);

        $response = $this->putJson('/api/municipality/fiscal/taxes/'.$taxType->id, [
            'name' => 'Taxe commerce révisée',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Taxe commerce révisée');

        $this->assertDatabaseHas('audit_logs', ['action' => 'tax_type.updated']);
    }

    public function test_admin_can_deactivate_tax_type(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);

        $response = $this->postJson('/api/municipality/fiscal/taxes/'.$taxType->id.'/deactivate');

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('audit_logs', ['action' => 'tax_type.deactivated']);
    }

    public function test_admin_can_activate_tax_type(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $taxType->update(['is_active' => false]);

        $response = $this->postJson('/api/municipality/fiscal/taxes/'.$taxType->id.'/activate');

        $response->assertOk()
            ->assertJsonPath('data.is_active', true);
    }

    public function test_citizen_cannot_manage_tax_types(): void
    {
        Sanctum::actingAs($this->citizenUser());

        $this->postJson('/api/municipality/fiscal/taxes', [
            'code' => 'TAX-X',
            'name' => 'X',
        ])->assertForbidden();
    }
}
