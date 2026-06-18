<?php

namespace Tests\Feature\Municipality;

use App\Modules\Municipality\Enums\BillingPeriod;
use App\Modules\Municipality\Enums\FiscalObligationStatus;
use App\Modules\Municipality\Models\FiscalObligation;
use App\Modules\Municipality\Services\FiscalObligationGeneratorService;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class FiscalObligationGenerationTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
    }

    public function test_generate_creates_monthly_obligation(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType, 15000, BillingPeriod::Monthly);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);

        $result = app(FiscalObligationGeneratorService::class)->generate($user);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('fiscal_obligations', [
            'operator_id' => $operator->id,
            'tax_type_id' => $taxType->id,
            'amount_due' => 15000,
            'balance_due' => 15000,
            'status' => FiscalObligationStatus::Open->value,
        ]);
        $this->assertMatchesRegularExpression('/^OWE-FO-\d{4}-\d{6}$/', FiscalObligation::query()->first()->reference);
    }

    public function test_generate_is_idempotent(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);

        $service = app(FiscalObligationGeneratorService::class);
        $first = $service->generate($user);
        $second = $service->generate($user);

        $this->assertSame(1, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['skipped']);
        $this->assertDatabaseCount('fiscal_obligations', 1);
    }

    public function test_artisan_command_generates_obligations(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);

        $this->artisan('municipality:fiscal-generate')
            ->assertSuccessful();

        $this->assertDatabaseCount('fiscal_obligations', 1);
    }

    public function test_api_generate_endpoint(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);

        $this->postJson('/api/municipality/fiscal/obligations/generate')
            ->assertOk()
            ->assertJsonPath('data.created', 1);

        $this->assertDatabaseCount('fiscal_obligations', 1);
    }

    public function test_quarterly_obligation_covers_quarter_period(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user, 'TAX-PME');
        $this->createTaxRate($user, $taxType, 150000, BillingPeriod::Quarterly);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);

        app(FiscalObligationGeneratorService::class)->generate($user);

        $obligation = FiscalObligation::query()->first();
        $this->assertSame(now()->firstOfQuarter()->toDateString(), $obligation->period_start->toDateString());
        $this->assertSame(now()->lastOfQuarter()->toDateString(), $obligation->period_end->toDateString());
    }

    public function test_no_obligation_without_active_rate(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);

        $result = app(FiscalObligationGeneratorService::class)->generate($user);

        $this->assertSame(0, $result['created']);
        $this->assertDatabaseCount('fiscal_obligations', 0);
    }

    public function test_admin_can_list_obligations(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        app(FiscalObligationGeneratorService::class)->generate($user);

        $this->getJson('/api/municipality/fiscal/obligations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', FiscalObligation::query()->first()->reference);
    }

    public function test_admin_can_cancel_open_obligation(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        app(FiscalObligationGeneratorService::class)->generate($user);
        $obligation = FiscalObligation::query()->first();

        $this->postJson('/api/municipality/fiscal/obligations/'.$obligation->id.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('audit_logs', ['action' => 'obligation.cancelled']);
    }
}
