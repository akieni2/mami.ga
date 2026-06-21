<?php

namespace Tests\Feature\Municipality;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Payment;
use App\Modules\Core\Models\Transaction;
use App\Modules\Municipality\Enums\FiscalObligationStatus;
use App\Modules\Municipality\Enums\PaymentStatus;
use App\Modules\Municipality\Enums\VisitType;
use App\Modules\Municipality\Models\MunicipalPayment;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class FiscalCollectionTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
    }

    public function test_collect_cash_with_selected_obligation_ids(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $rate = $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $obligation = $this->createManualObligation($operator, $taxType, $rate, 15000, '2026-06-30', 'OWE-FO-2026-000301');
        $session = $this->openCashSession($user);

        Sanctum::actingAs($user);

        $payload = $this->validCollectionPayload($operator, $session);
        unset($payload['amount_xaf']);
        $payload['obligation_ids'] = [$obligation->id];

        $this->postJson('/api/municipality/fiscal/collections', $payload)
            ->assertCreated()
            ->assertJsonPath('data.amount_xaf', '15000.00');

        $obligation->refresh();
        $this->assertSame(FiscalObligationStatus::Paid, $obligation->status);
    }

    public function test_collect_cash_creates_payment_records(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $this->generateObligations($user);
        $session = $this->openCashSession($user);

        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertCreated()
            ->assertJsonPath('data.amount_xaf', '15000.00');

        $this->assertDatabaseHas('municipal_payments', [
            'operator_id' => $operator->id,
            'agent_id' => $user->id,
            'cash_session_id' => $session->id,
            'status' => PaymentStatus::Completed->value,
        ]);

        $payment = MunicipalPayment::query()->first();
        $this->assertNotNull($payment->core_payment_id);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->core_payment_id,
            'payable_type' => 'municipal_payment',
            'status' => 'captured',
        ]);
        $this->assertDatabaseHas('transactions', [
            'payment_id' => $payment->core_payment_id,
            'type' => 'capture',
        ]);
    }

    public function test_collection_updates_obligation_status(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $this->generateObligations($user);
        $session = $this->openCashSession($user);

        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertCreated();

        $this->assertDatabaseHas('fiscal_obligations', [
            'operator_id' => $operator->id,
            'status' => FiscalObligationStatus::Paid->value,
            'balance_due' => 0,
        ]);
    }

    public function test_collection_creates_payment_field_visit_and_audit(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $this->generateObligations($user);
        $session = $this->openCashSession($user);

        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertCreated();

        $this->assertDatabaseHas('field_visits', [
            'operator_id' => $operator->id,
            'visit_type' => VisitType::Payment->value,
        ]);

        $this->assertTrue(
            AuditLog::query()->where('action', 'payment.collected')->exists()
        );
    }

    public function test_rejects_collection_without_open_session(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $this->generateObligations($user);
        $session = $this->openCashSession($user);
        $this->app->make(\App\Modules\Municipality\Services\CashSessionService::class)
            ->close($user, $session, ['actual_amount_xaf' => 0]);

        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cash_session_id']);
    }

    public function test_rejects_collection_without_gps(): void
    {
        $manager = $this->fiscalManager();
        $agent = $this->municipalAgentUser();
        $taxType = $this->createTaxType($manager);
        $this->createTaxRate($manager, $taxType);
        $operator = $this->createOperator($manager);
        $this->assignTax($manager, $operator, $taxType);
        $this->generateObligations($manager);
        $session = $this->openCashSession($agent);

        Sanctum::actingAs($agent);

        $payload = $this->validCollectionPayload($operator, $session);
        unset($payload['latitude'], $payload['longitude']);

        $this->postJson('/api/municipality/fiscal/collections', $payload)
            ->assertStatus(422);
    }

    public function test_rejects_collection_with_poor_gps_accuracy(): void
    {
        $manager = $this->fiscalManager();
        $agent = $this->municipalAgentUser();
        $taxType = $this->createTaxType($manager);
        $this->createTaxRate($manager, $taxType);
        $operator = $this->createOperator($manager);
        $this->assignTax($manager, $operator, $taxType);
        $this->generateObligations($manager);
        $session = $this->openCashSession($agent);

        Sanctum::actingAs($agent);

        $payload = $this->validCollectionPayload($operator, $session);
        $payload['gps_accuracy_m'] = 200;

        $this->postJson('/api/municipality/fiscal/collections', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['gps_accuracy_m']);
    }

    public function test_collection_with_existing_obligation_unchanged(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $this->generateObligations($user);
        $session = $this->openCashSession($user);

        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertCreated()
            ->assertJsonPath('data.amount_xaf', '15000.00');

        $this->assertDatabaseHas('fiscal_obligations', [
            'operator_id' => $operator->id,
            'status' => FiscalObligationStatus::Paid->value,
        ]);
    }

    public function test_collection_without_obligation_but_with_tax_assignment_creates_initial_obligation(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $session = $this->openCashSession($user);

        $this->assertDatabaseCount('fiscal_obligations', 0);

        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertCreated()
            ->assertJsonPath('data.amount_xaf', '15000.00');

        $this->assertDatabaseHas('fiscal_obligations', [
            'operator_id' => $operator->id,
            'tax_type_id' => $taxType->id,
            'status' => FiscalObligationStatus::Paid->value,
            'balance_due' => 0,
        ]);

        $this->assertTrue(
            AuditLog::query()->where('action', 'obligation.created')->exists()
        );
    }

    public function test_collection_without_tax_assignment_is_rejected(): void
    {
        $user = $this->fiscalManager();
        $operator = $this->createOperator($user);
        $session = $this->openCashSession($user);

        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['operator_id'])
            ->assertJsonPath('errors.operator_id.0', 'Aucune taxe n\'est affectée à ce commerce.');
    }

    public function test_rejects_collection_for_inactive_operator(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $this->generateObligations($user);
        $operator->update(['is_active' => false]);
        $session = $this->openCashSession($user);

        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['operator_id'])
            ->assertJsonPath('errors.operator_id.0', 'Commerce inactif — encaissement refusé.');
    }

    public function test_rejects_overpayment(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType, 5000);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $this->generateObligations($user);
        $session = $this->openCashSession($user);

        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 99999))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount_xaf']);
    }

    public function test_idempotent_client_operation_id(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $this->generateObligations($user);
        $session = $this->openCashSession($user);

        Sanctum::actingAs($user);

        $clientId = (string) Str::uuid();
        $payload = array_merge(
            $this->validCollectionPayload($operator, $session),
            ['client_operation_id' => $clientId],
        );

        $this->postJson('/api/municipality/fiscal/collections', $payload)->assertCreated();
        $this->postJson('/api/municipality/fiscal/collections', $payload)->assertCreated();

        $this->assertSame(1, MunicipalPayment::query()->count());
        $this->assertSame(1, Payment::query()->count());
        $this->assertSame(1, Transaction::query()->count());
    }

    public function test_agent_can_list_own_collections(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $this->generateObligations($user);
        $session = $this->openCashSession($user);

        Sanctum::actingAs($user);
        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertCreated();

        $this->getJson('/api/municipality/fiscal/collections')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_citizen_cannot_collect(): void
    {
        $user = $this->fiscalManager();
        $operator = $this->createOperator($user);
        $session = $this->openCashSession($user);

        Sanctum::actingAs($this->citizenUser());

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertForbidden();
    }
}
