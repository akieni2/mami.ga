<?php

namespace Tests\Feature\Municipality;

use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class FiscalAssignmentTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
    }

    public function test_admin_can_assign_tax_to_operator(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);

        $response = $this->postJson('/api/municipality/fiscal/assignments', [
            'operator_id' => $operator->id,
            'tax_type_id' => $taxType->id,
            'notes' => 'Affectation initiale',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('operator_tax_assignments', [
            'operator_id' => $operator->id,
            'tax_type_id' => $taxType->id,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('fiscal_obligations', [
            'operator_id' => $operator->id,
            'tax_type_id' => $taxType->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'tax_assignment.created']);
    }

    public function test_assignment_requires_active_tax_rate(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $operator = $this->createOperator($user);

        $this->postJson('/api/municipality/fiscal/assignments', [
            'operator_id' => $operator->id,
            'tax_type_id' => $taxType->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['tax_type_id']);
    }

    public function test_operator_can_have_multiple_active_taxes(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxA = $this->createTaxType($user, 'TAX-A', 'Taxe A');
        $taxB = $this->createTaxType($user, 'TAX-B', 'Taxe B');
        $this->createTaxRate($user, $taxA);
        $this->createTaxRate($user, $taxB);
        $operator = $this->createOperator($user);

        $this->postJson('/api/municipality/fiscal/assignments', [
            'operator_id' => $operator->id,
            'tax_type_id' => $taxA->id,
        ])->assertCreated();

        $this->postJson('/api/municipality/fiscal/assignments', [
            'operator_id' => $operator->id,
            'tax_type_id' => $taxB->id,
        ])->assertCreated();

        $this->assertDatabaseCount('operator_tax_assignments', 2);
    }

    public function test_duplicate_active_assignment_rejected(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);

        $response = $this->postJson('/api/municipality/fiscal/assignments', [
            'operator_id' => $operator->id,
            'tax_type_id' => $taxType->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tax_type_id']);
    }

    public function test_admin_can_deactivate_assignment(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $assignment = $this->assignTax($user, $operator, $taxType);

        $this->postJson('/api/municipality/fiscal/assignments/'.$assignment->id.'/deactivate')
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    public function test_admin_can_reactivate_assignment(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $assignment = $this->assignTax($user, $operator, $taxType);
        $assignment->update(['is_active' => false]);

        $this->postJson('/api/municipality/fiscal/assignments/'.$assignment->id.'/activate')
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
    }

    public function test_inactive_tax_type_cannot_be_assigned(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $taxType->update(['is_active' => false]);
        $operator = $this->createOperator($user);

        $this->postJson('/api/municipality/fiscal/assignments', [
            'operator_id' => $operator->id,
            'tax_type_id' => $taxType->id,
        ])->assertUnprocessable();
    }
}
