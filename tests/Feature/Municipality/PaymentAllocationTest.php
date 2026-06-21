<?php

namespace Tests\Feature\Municipality;

use App\Modules\Municipality\Enums\FiscalObligationStatus;
use App\Modules\Municipality\Models\MunicipalPaymentAllocation;
use App\Modules\Municipality\Models\FiscalObligation;
use App\Modules\Municipality\Services\FiscalCollectionService;
use App\Modules\Municipality\Services\ObligationAllocationService;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class PaymentAllocationTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
    }

    public function test_fifo_allocation_settles_oldest_obligation_first(): void
    {
        $user = $this->fiscalManager();
        $taxA = $this->createTaxType($user, 'TAX-A', 'Taxe A');
        $taxB = $this->createTaxType($user, 'TAX-B', 'Taxe B');
        $rateA = $this->createTaxRate($user, $taxA, 5000);
        $rateB = $this->createTaxRate($user, $taxB, 7000);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxA);
        $this->assignTax($user, $operator, $taxB);

        $obligationA = $this->createManualObligation($operator, $taxA, $rateA, 5000, '2026-01-15', 'OWE-FO-2026-000001');
        $obligationB = $this->createManualObligation($operator, $taxB, $rateB, 7000, '2026-02-15', 'OWE-FO-2026-000002');

        $session = $this->openCashSession($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 5000))
            ->assertCreated();

        $obligationA->refresh();
        $obligationB->refresh();

        $this->assertSame(FiscalObligationStatus::Paid, $obligationA->status);
        $this->assertSame(FiscalObligationStatus::Open, $obligationB->status);
    }

    public function test_single_payment_allocates_across_multiple_obligations(): void
    {
        $user = $this->fiscalManager();
        $taxA = $this->createTaxType($user, 'TAX-A', 'Taxe A');
        $taxB = $this->createTaxType($user, 'TAX-B', 'Taxe B');
        $taxC = $this->createTaxType($user, 'TAX-C', 'Taxe C');
        $rateA = $this->createTaxRate($user, $taxA, 5000);
        $rateB = $this->createTaxRate($user, $taxB, 7000);
        $rateC = $this->createTaxRate($user, $taxC, 8000);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxA);
        $this->assignTax($user, $operator, $taxB);
        $this->assignTax($user, $operator, $taxC);

        $this->createManualObligation($operator, $taxA, $rateA, 5000, '2026-01-01', 'OWE-FO-2026-000011');
        $this->createManualObligation($operator, $taxB, $rateB, 7000, '2026-02-01', 'OWE-FO-2026-000012');
        $this->createManualObligation($operator, $taxC, $rateC, 8000, '2026-03-01', 'OWE-FO-2026-000013');

        $session = $this->openCashSession($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 20000))
            ->assertCreated();

        $this->assertDatabaseCount('municipal_payment_allocations', 3);
        $this->assertDatabaseMissing('fiscal_obligations', [
            'operator_id' => $operator->id,
            'status' => FiscalObligationStatus::Open->value,
        ]);
    }

    public function test_partial_payment_marks_obligation_partial(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $rate = $this->createTaxRate($user, $taxType, 10000);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $obligation = $this->createManualObligation($operator, $taxType, $rate, 10000, '2026-01-01', 'OWE-FO-2026-000021');

        $session = $this->openCashSession($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 4000))
            ->assertCreated();

        $obligation->refresh();
        $this->assertSame(FiscalObligationStatus::Partial, $obligation->status);
        $this->assertSame(6000.0, (float) $obligation->balance_due);
    }

    public function test_allocation_service_rejects_excess_amount(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $rate = $this->createTaxRate($user, $taxType, 3000);
        $operator = $this->createOperator($user);
        $this->createManualObligation($operator, $taxType, $rate, 3000, '2026-01-01', 'OWE-FO-2026-000031');

        $service = app(ObligationAllocationService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->allocate($operator, 5000);
    }

    public function test_allocation_records_match_payment_amount(): void
    {
        $user = $this->fiscalManager();
        $taxA = $this->createTaxType($user, 'TAX-A', 'Taxe A');
        $taxB = $this->createTaxType($user, 'TAX-B', 'Taxe B');
        $rateA = $this->createTaxRate($user, $taxA, 5000);
        $rateB = $this->createTaxRate($user, $taxB, 7000);
        $operator = $this->createOperator($user);
        $this->createManualObligation($operator, $taxA, $rateA, 5000, '2026-01-01', 'OWE-FO-2026-000041');
        $this->createManualObligation($operator, $taxB, $rateB, 7000, '2026-02-01', 'OWE-FO-2026-000042');

        $session = $this->openCashSession($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 12000))
            ->assertCreated();

        $totalAllocated = MunicipalPaymentAllocation::query()->sum('amount_allocated');
        $this->assertSame(12000.0, (float) $totalAllocated);
    }

    public function test_second_payment_completes_remaining_balance(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $rate = $this->createTaxRate($user, $taxType, 10000);
        $operator = $this->createOperator($user);
        $this->createManualObligation($operator, $taxType, $rate, 10000, '2026-01-01', 'OWE-FO-2026-000051');

        $session = $this->openCashSession($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 4000))
            ->assertCreated();
        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 6000))
            ->assertCreated();

        $this->assertDatabaseHas('fiscal_obligations', [
            'operator_id' => $operator->id,
            'status' => FiscalObligationStatus::Paid->value,
            'balance_due' => 0,
        ]);
    }

    public function test_allocation_via_service_creates_rows_on_apply(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $rate = $this->createTaxRate($user, $taxType, 8000);
        $operator = $this->createOperator($user);
        $obligation = $this->createManualObligation($operator, $taxType, $rate, 8000, '2026-01-01', 'OWE-FO-2026-000061');
        $session = $this->openCashSession($user);

        $result = app(FiscalCollectionService::class)->collectCash($user, $this->validCollectionPayload($operator, $session, 8000));

        $this->assertDatabaseHas('municipal_payment_allocations', [
            'municipal_payment_id' => $result['municipal_payment']->id,
            'fiscal_obligation_id' => $obligation->id,
            'amount_allocated' => 8000,
        ]);
    }

    public function test_same_due_date_uses_lower_id_first(): void
    {
        $user = $this->fiscalManager();
        $taxA = $this->createTaxType($user, 'TAX-A', 'Taxe A');
        $taxB = $this->createTaxType($user, 'TAX-B', 'Taxe B');
        $rateA = $this->createTaxRate($user, $taxA, 3000);
        $rateB = $this->createTaxRate($user, $taxB, 3000);
        $operator = $this->createOperator($user);

        $first = $this->createManualObligation($operator, $taxA, $rateA, 3000, '2026-01-01', 'OWE-FO-2026-000071');
        $second = $this->createManualObligation($operator, $taxB, $rateB, 3000, '2026-01-01', 'OWE-FO-2026-000072');

        $session = $this->openCashSession($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 3000))
            ->assertCreated();

        $first->refresh();
        $second->refresh();

        $this->assertSame(FiscalObligationStatus::Paid, $first->status);
        $this->assertSame(FiscalObligationStatus::Open, $second->status);
    }

    public function test_api_response_includes_allocations(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $rate = $this->createTaxRate($user, $taxType, 5000);
        $operator = $this->createOperator($user);
        $this->createManualObligation($operator, $taxType, $rate, 5000, '2026-01-01', 'OWE-FO-2026-000081');
        $session = $this->openCashSession($user);

        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 5000))
            ->assertCreated()
            ->assertJsonStructure(['data' => ['allocations']]);
    }

    public function test_zero_balance_obligations_are_skipped(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $rate = $this->createTaxRate($user, $taxType, 5000);
        $taxB = $this->createTaxType($user, 'TAX-B2', 'Taxe B2');
        $rateB = $this->createTaxRate($user, $taxB, 2000);
        $operator = $this->createOperator($user);
        $paid = $this->createManualObligation($operator, $taxType, $rate, 5000, '2026-01-01', 'OWE-FO-2026-000091');
        $paid->update([
            'amount_paid' => 5000,
            'balance_due' => 0,
            'status' => FiscalObligationStatus::Paid,
        ]);

        $open = FiscalObligation::query()->create([
            'operator_id' => $operator->id,
            'tax_type_id' => $taxB->id,
            'tax_rate_id' => $rateB->id,
            'obligation_type' => 'tax',
            'reference' => 'OWE-FO-2026-000092',
            'period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
            'amount_due' => 2000,
            'amount_paid' => 0,
            'balance_due' => 2000,
            'status' => FiscalObligationStatus::Open,
            'generated_at' => now(),
            'due_date' => '2026-02-01',
        ]);
        $session = $this->openCashSession($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 2000))
            ->assertCreated();

        $open->refresh();
        $this->assertSame(FiscalObligationStatus::Paid, $open->status);
        $this->assertDatabaseCount('municipal_payment_allocations', 1);
    }
}
