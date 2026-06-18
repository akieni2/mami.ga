<?php

namespace Tests\Feature\Municipality;

use App\Modules\Municipality\Enums\BillingPeriod;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class FiscalRateTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    public function test_admin_can_create_tax_rate(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);

        $response = $this->postJson('/api/municipality/fiscal/rates', [
            'tax_type_id' => $taxType->id,
            'amount_xaf' => 25000,
            'billing_period' => BillingPeriod::Monthly->value,
            'valid_from' => '2026-01-01',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.amount_xaf', '25000.00');

        $this->assertDatabaseHas('municipal_tax_rates', [
            'tax_type_id' => $taxType->id,
            'amount_xaf' => 25000,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'tax_rate.created']);
    }

    public function test_new_rate_supersedes_old_rate_without_deletion(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $oldRate = $this->createTaxRate($user, $taxType, 10000);

        $this->postJson('/api/municipality/fiscal/rates', [
            'tax_type_id' => $taxType->id,
            'amount_xaf' => 15000,
            'billing_period' => BillingPeriod::Monthly->value,
            'valid_from' => now()->startOfMonth()->toDateString(),
        ])->assertCreated();

        $oldRate->refresh();
        $this->assertFalse($oldRate->is_active);
        $this->assertDatabaseCount('municipal_tax_rates', 2);
    }

    public function test_overlapping_rate_period_rejected(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType, 10000);

        $response = $this->postJson('/api/municipality/fiscal/rates', [
            'tax_type_id' => $taxType->id,
            'amount_xaf' => 12000,
            'billing_period' => BillingPeriod::Monthly->value,
            'valid_from' => now()->startOfYear()->toDateString(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['valid_from']);
    }

    public function test_admin_can_list_rates(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);

        $this->getJson('/api/municipality/fiscal/rates?tax_type_id='.$taxType->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_deactivate_rate(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $rate = $this->createTaxRate($user, $taxType);

        $this->postJson('/api/municipality/fiscal/rates/'.$rate->id.'/deactivate')
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('audit_logs', ['action' => 'tax_rate.deactivated']);
    }

    public function test_quarterly_billing_period_accepted(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user, 'TAX-PME');

        $this->postJson('/api/municipality/fiscal/rates', [
            'tax_type_id' => $taxType->id,
            'amount_xaf' => 150000,
            'billing_period' => BillingPeriod::Quarterly->value,
            'valid_from' => '2026-01-01',
        ])->assertCreated()
            ->assertJsonPath('data.billing_period', 'quarterly');
    }

    public function test_annual_billing_period_accepted(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user, 'TAX-ANNUEL');

        $this->postJson('/api/municipality/fiscal/rates', [
            'tax_type_id' => $taxType->id,
            'amount_xaf' => 500000,
            'billing_period' => BillingPeriod::Annual->value,
            'valid_from' => '2026-01-01',
        ])->assertCreated()
            ->assertJsonPath('data.billing_period', 'annual');
    }
}
