<?php

namespace Tests\Feature\Municipality;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Municipality\Enums\CashSessionStatus;
use App\Modules\Municipality\Enums\FiscalObligationStatus;
use App\Modules\Municipality\Enums\PaymentStatus;
use App\Modules\Municipality\Models\CashSession;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\MunicipalPaymentAllocation;
use App\Modules\Municipality\Models\MunicipalReceipt;
use App\Modules\Municipality\Services\FiscalCollectionService;
use App\Modules\Municipality\Services\FiscalSupervisorDashboardService;
use App\Modules\Municipality\Services\MunicipalReceiptReferenceGenerator;
use App\Modules\Municipality\Services\QRCodeManagement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

/**
 * Validation finale Sprint 2 — intégrité, traçabilité, sécurité, performance.
 */
class Sprint2FinalValidationTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
    }

    // --- 1. INTÉGRITÉ FINANCIÈRE ---

    public function test_payment_requires_open_cash_session(): void
    {
        $agent = $this->municipalAgentUser();
        $operator = $this->createOperator($agent);
        Sanctum::actingAs($agent);

        $this->postJson('/api/municipality/fiscal/collections', [
            'operator_id' => $operator->id,
            'amount_xaf' => 1000,
            'cash_session_id' => 99999,
            'latitude' => 0.33,
            'longitude' => 9.47,
            'gps_accuracy_m' => 10,
        ])->assertStatus(422);
    }

    public function test_agent_cannot_have_two_open_sessions(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 0,
            'latitude' => 0.33,
            'longitude' => 9.47,
        ])->assertCreated();

        $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 0,
            'latitude' => 0.33,
            'longitude' => 9.47,
        ])->assertStatus(422);

        $this->assertSame(1, CashSession::query()
            ->where('agent_id', $agent->id)
            ->where('status', CashSessionStatus::Open)
            ->count());
    }

    public function test_cannot_close_already_closed_session(): void
    {
        $agent = $this->municipalAgentUser();
        $session = $this->openCashSession($agent);
        Sanctum::actingAs($agent);

        $this->postJson("/api/municipality/fiscal/cash-sessions/{$session->id}/close", [
            'actual_amount_xaf' => 0,
        ])->assertOk();

        $this->postJson("/api/municipality/fiscal/cash-sessions/{$session->id}/close", [
            'actual_amount_xaf' => 0,
        ])->assertStatus(422);
    }

    public function test_cannot_collect_for_inactive_operator(): void
    {
        $agent = $this->municipalAgentUser();
        $operator = $this->createOperator($agent);
        $operator->update(['is_active' => false]);
        $session = $this->openCashSession($agent);
        Sanctum::actingAs($agent);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertStatus(422);
    }

    public function test_cannot_collect_without_open_obligations(): void
    {
        $agent = $this->municipalAgentUser();
        $operator = $this->createOperator($agent);
        $session = $this->openCashSession($agent);
        Sanctum::actingAs($agent);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 1000))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount_xaf']);
    }

    public function test_cannot_collect_zero_or_negative_amount(): void
    {
        $agent = $this->municipalAgentUser();
        $taxType = $this->createTaxType($agent);
        $this->createTaxRate($agent, $taxType);
        $operator = $this->createOperator($agent);
        $this->assignTax($agent, $operator, $taxType);
        $this->generateObligations($agent);
        $session = $this->openCashSession($agent);
        Sanctum::actingAs($agent);

        $this->postJson('/api/municipality/fiscal/collections', array_merge(
            $this->validCollectionPayload($operator, $session, 0),
            ['amount_xaf' => 0],
        ))->assertStatus(422);
    }

    public function test_completed_payment_always_has_cash_session_id(): void
    {
        $agent = $this->fiscalManager();
        $taxType = $this->createTaxType($agent);
        $this->createTaxRate($agent, $taxType);
        $operator = $this->createOperator($agent);
        $this->assignTax($agent, $operator, $taxType);
        $this->generateObligations($agent);
        $session = $this->openCashSession($agent);

        app(FiscalCollectionService::class)->collectCash($agent, $this->validCollectionPayload($operator, $session));

        $payment = MunicipalPayment::query()->first();
        $this->assertNotNull($payment->cash_session_id);
        $this->assertSame($session->id, $payment->cash_session_id);
    }

    // --- 2. ALLOCATION 20 000 sur A/B/C ---

    public function test_allocation_settles_abc_with_exact_cent_breakdown(): void
    {
        $agent = $this->fiscalManager();
        $taxA = $this->createTaxType($agent, 'TAX-A', 'Taxe A');
        $taxB = $this->createTaxType($agent, 'TAX-B', 'Taxe B');
        $taxC = $this->createTaxType($agent, 'TAX-C', 'Taxe C');
        $rateA = $this->createTaxRate($agent, $taxA, 5000);
        $rateB = $this->createTaxRate($agent, $taxB, 7000);
        $rateC = $this->createTaxRate($agent, $taxC, 8000);
        $operator = $this->createOperator($agent);

        $obligationA = $this->createManualObligation($operator, $taxA, $rateA, 5000, '2026-01-01', 'OWE-FO-2026-100001');
        $obligationB = $this->createManualObligation($operator, $taxB, $rateB, 7000, '2026-02-01', 'OWE-FO-2026-100002');
        $obligationC = $this->createManualObligation($operator, $taxC, $rateC, 8000, '2026-03-01', 'OWE-FO-2026-100003');

        $session = $this->openCashSession($agent);
        Sanctum::actingAs($agent);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 20000))
            ->assertCreated();

        $this->assertDatabaseHas('municipal_payment_allocations', [
            'fiscal_obligation_id' => $obligationA->id,
            'amount_allocated' => 5000,
        ]);
        $this->assertDatabaseHas('municipal_payment_allocations', [
            'fiscal_obligation_id' => $obligationB->id,
            'amount_allocated' => 7000,
        ]);
        $this->assertDatabaseHas('municipal_payment_allocations', [
            'fiscal_obligation_id' => $obligationC->id,
            'amount_allocated' => 8000,
        ]);

        $totalAllocated = MunicipalPaymentAllocation::query()->sum('amount_allocated');
        $this->assertSame(20000.0, (float) $totalAllocated);

        foreach ([$obligationA, $obligationB, $obligationC] as $obligation) {
            $obligation->refresh();
            $this->assertSame(FiscalObligationStatus::Paid, $obligation->status);
            $this->assertSame(0.0, (float) $obligation->balance_due);
        }
    }

    public function test_overpayment_is_rejected(): void
    {
        $agent = $this->fiscalManager();
        $taxType = $this->createTaxType($agent);
        $this->createTaxRate($agent, $taxType, 5000);
        $operator = $this->createOperator($agent);
        $this->assignTax($agent, $operator, $taxType);
        $this->generateObligations($agent);
        $session = $this->openCashSession($agent);
        Sanctum::actingAs($agent);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 5001))
            ->assertStatus(422);
    }

    // --- 3. TRAÇABILITÉ AUDIT ---

    public function test_audit_logs_for_all_collection_actions(): void
    {
        $agent = $this->fiscalManager();
        $taxType = $this->createTaxType($agent);
        $this->createTaxRate($agent, $taxType);
        $operator = $this->createOperator($agent);
        $this->assignTax($agent, $operator, $taxType);
        $this->generateObligations($agent);
        Sanctum::actingAs($agent);

        $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 1000,
            'latitude' => 0.33,
            'longitude' => 9.47,
        ])->assertCreated();

        $session = CashSession::query()->first();
        $qr = app(QRCodeManagement::class)->generateForOperator($operator);

        $this->getJson('/api/municipality/operators/by-qr/'.$qr->qr_uuid)->assertOk();
        $this->getJson("/api/municipality/fiscal/operator/{$operator->id}/summary")->assertOk();

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertCreated();

        $this->postJson("/api/municipality/fiscal/cash-sessions/{$session->id}/close", [
            'actual_amount_xaf' => 16000,
        ])->assertOk();

        $actions = ['cash_session.opened', 'fiscal.scan', 'fiscal.consultation', 'payment.collected', 'receipt.issued', 'cash_session.closed'];
        foreach ($actions as $action) {
            $log = AuditLog::query()->where('action', $action)->where('actor_id', $agent->id)->first();
            $this->assertNotNull($log, "Missing audit for {$action}");
            $this->assertSame('municipality', $log->module);
        }
    }

    // --- 4. GPS ---

    public function test_collection_rejects_missing_gps_for_field_agent(): void
    {
        $agent = $this->municipalAgentUser();
        $taxType = $this->createTaxType($agent);
        $this->createTaxRate($agent, $taxType);
        $operator = $this->createOperator($agent);
        $this->assignTax($agent, $operator, $taxType);
        $this->generateObligations($agent);
        $session = $this->openCashSession($agent);

        $payload = $this->validCollectionPayload($operator, $session);
        unset($payload['latitude'], $payload['longitude']);

        $this->expectException(ValidationException::class);
        app(FiscalCollectionService::class)->collectCash($agent, $payload);
    }

    public function test_supervisor_can_collect_without_gps(): void
    {
        $agent = $this->fiscalManager();
        $taxType = $this->createTaxType($agent);
        $this->createTaxRate($agent, $taxType);
        $operator = $this->createOperator($agent);
        $this->assignTax($agent, $operator, $taxType);
        $this->generateObligations($agent);
        $session = $this->openCashSession($agent);

        $payload = $this->validCollectionPayload($operator, $session);
        unset($payload['latitude'], $payload['longitude'], $payload['gps_accuracy_m']);

        $result = app(FiscalCollectionService::class)->collectCash($agent, $payload);
        $this->assertSame(PaymentStatus::Completed, $result['municipal_payment']->status);
    }

    // --- 5. QR (déjà couvert partiellement, renforcement) ---

    public function test_qr_rejects_public_id_without_secure_token(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);
        $operator = $this->createOperator($agent);

        $this->getJson('/api/municipality/operators/by-qr/'.$operator->public_id)
            ->assertNotFound();
    }

    // --- 6. QUITTANCES ---

    public function test_collection_auto_creates_municipal_receipt(): void
    {
        $agent = $this->fiscalManager();
        $taxType = $this->createTaxType($agent);
        $this->createTaxRate($agent, $taxType);
        $operator = $this->createOperator($agent);
        $this->assignTax($agent, $operator, $taxType);
        $this->generateObligations($agent);
        $session = $this->openCashSession($agent);
        Sanctum::actingAs($agent);

        $response = $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertCreated();

        $paymentId = $response->json('data.id');
        $this->assertDatabaseHas('municipal_receipts', ['payment_id' => $paymentId]);
        $receipt = MunicipalReceipt::query()->where('payment_id', $paymentId)->first();
        $this->assertMatchesRegularExpression('/^OWE-RCP-\d{4}-\d{6}$/', $receipt->receipt_number);
    }

    public function test_receipt_reference_collision_is_prevented_by_unique_constraint(): void
    {
        $generator = app(MunicipalReceiptReferenceGenerator::class);
        $agent = $this->fiscalManager();
        $operator = $this->createOperator($agent);

        $payment = MunicipalPayment::query()->create([
            'operator_id' => $operator->id,
            'agent_id' => $agent->id,
            'amount' => 1000,
            'payment_method' => \App\Modules\Municipality\Enums\PaymentMethod::Cash,
            'status' => PaymentStatus::Completed,
        ]);

        $number = $generator->next(2026);
        MunicipalReceipt::query()->create([
            'payment_id' => $payment->id,
            'receipt_number' => $number,
            'receipt_qr_value' => $generator->buildReceiptQrValue($number),
            'generated_at' => now(),
        ]);

        $next = $generator->next(2026);
        $this->assertNotSame($number, $next);
        $this->assertSame('OWE-RCP-2026-000002', $next);
    }

    // --- 7. SESSIONS expected_amount ---

    public function test_session_expected_amount_matches_payments_sum(): void
    {
        $agent = $this->fiscalManager();
        $taxType = $this->createTaxType($agent);
        $this->createTaxRate($agent, $taxType, 5000);
        $operator = $this->createOperator($agent);
        $this->assignTax($agent, $operator, $taxType);
        $this->generateObligations($agent);

        $session = $this->openCashSession($agent, ['opening_amount_xaf' => 2000]);
        Sanctum::actingAs($agent);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 5000))
            ->assertCreated();

        $session->refresh();
        $collected = MunicipalPayment::query()
            ->where('cash_session_id', $session->id)
            ->where('status', PaymentStatus::Completed)
            ->sum('amount');

        $this->assertSame(7000.0, (float) $session->expected_amount_xaf);
        $this->assertSame(7000.0, 2000 + (float) $collected);
        $this->assertMatchesRegularExpression('/^OWE-CS-\d{4}-\d{6}$/', $session->reference);
    }

    // --- 8. DASHBOARD ---

    public function test_supervisor_dashboard_includes_quartier_and_bounded_queries(): void
    {
        $agent = $this->fiscalManager();
        $taxType = $this->createTaxType($agent);
        $this->createTaxRate($agent, $taxType);
        $operator = $this->createOperator($agent);
        $this->assignTax($agent, $operator, $taxType);
        $this->generateObligations($agent);
        $session = $this->openCashSession($agent);
        Sanctum::actingAs($agent);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertCreated();

        DB::enableQueryLog();
        $response = $this->getJson('/api/municipality/fiscal/supervisor/dashboard')->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertJsonStructure([
            'data' => [
                'collections_by_agent',
                'collections_by_day',
                'collections_by_quartier',
            ],
        ]);

        $this->assertLessThan(25, $queryCount, 'Dashboard should use eager loading and aggregated queries');
    }

    // --- 10. PERFORMANCE (échelle réduite, requêtes agrégées) ---

    public function test_dashboard_performance_with_large_dataset(): void
    {
        $agent = $this->fiscalManager();
        $categoryId = \App\Modules\Municipality\Models\EconomicOperatorCategory::query()->where('slug', 'boutique')->value('id');
        $territoryId = $this->territoryId();
        $now = now();

        $operators = [];
        for ($i = 1; $i <= 200; $i++) {
            $operators[] = [
                'public_id' => 'OWE-COM-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'territory_id' => $territoryId,
                'category_id' => $categoryId,
                'commercial_name' => "Commerce {$i}",
                'activity_label' => 'Test',
                'responsible_name' => 'Resp',
                'phone' => '+2410600'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'latitude' => 0.3380,
                'longitude' => 9.4710,
                'gps_accuracy_m' => 5,
                'gps_captured_at' => $now,
                'is_active' => true,
                'registration_date' => $now->toDateString(),
                'registered_by' => $agent->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('economic_operators')->insert($operators);
        $operatorIds = DB::table('economic_operators')->orderBy('id')->pluck('id')->all();

        $session = $this->openCashSession($agent);
        $payments = [];
        for ($i = 1; $i <= 500; $i++) {
            $payments[] = [
                'operator_id' => $operatorIds[($i - 1) % count($operatorIds)],
                'agent_id' => $agent->id,
                'cash_session_id' => $session->id,
                'amount' => 1000,
                'payment_method' => 'cash',
                'status' => 'completed',
                'collected_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($payments, 100) as $chunk) {
            DB::table('municipal_payments')->insert($chunk);
        }

        DB::enableQueryLog();
        $start = microtime(true);
        app(FiscalSupervisorDashboardService::class)->build($now->toDateString());
        $elapsedMs = (microtime(true) - $start) * 1000;
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(30, $queryCount);
        $this->assertLessThan(3000, $elapsedMs, 'Dashboard should remain fast with hundreds of payments');
    }

    // --- 11. SÉCURITÉ ---

    public function test_citizen_cannot_access_collection_endpoints(): void
    {
        $citizen = $this->citizenUser();
        $agent = $this->fiscalManager();
        $operator = $this->createOperator($agent);
        Sanctum::actingAs($citizen);

        $this->getJson('/api/municipality/fiscal/cash-sessions/current')->assertForbidden();
        $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 0,
            'latitude' => 0.33,
            'longitude' => 9.47,
        ])->assertForbidden();
        $this->getJson('/api/municipality/fiscal/collections')->assertForbidden();
        $this->postJson('/api/municipality/fiscal/collections', [])->assertForbidden();
        $this->getJson('/api/municipality/fiscal/supervisor/dashboard')->assertForbidden();
        $this->getJson("/api/municipality/fiscal/operator/{$operator->id}/summary")->assertForbidden();
    }
}
