<?php

namespace Tests\Feature\Municipality;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Municipality\Enums\VisitType;
use App\Modules\Municipality\Models\FieldVisit;
use App\Modules\Municipality\Services\QRCodeManagement;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class OperatorFiscalSummaryTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
    }

    public function test_fiscal_summary_returns_operator_details(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $this->generateObligations($user);

        Sanctum::actingAs($user);

        $this->getJson("/api/municipality/fiscal/operator/{$operator->id}/summary")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.operator.commercial_name', 'Boutique Test')
            ->assertJsonPath('data.totals.amount_due', '15000');
    }

    public function test_summary_lists_open_obligations(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $this->generateObligations($user);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/municipality/fiscal/operator/{$operator->id}/summary")
            ->assertOk();

        $this->assertCount(1, $response->json('data.obligations'));
        $this->assertNotEmpty($response->json('data.tax_assignments'));
    }

    public function test_summary_creates_consultation_field_visit(): void
    {
        $user = $this->fiscalManager();
        $operator = $this->createOperator($user);

        Sanctum::actingAs($user);

        $this->getJson("/api/municipality/fiscal/operator/{$operator->id}/summary?latitude=0.33&longitude=9.47")
            ->assertOk();

        $this->assertDatabaseHas('field_visits', [
            'operator_id' => $operator->id,
            'visit_type' => VisitType::Consultation->value,
        ]);
    }

    public function test_summary_writes_audit_log(): void
    {
        $user = $this->fiscalManager();
        $operator = $this->createOperator($user);

        Sanctum::actingAs($user);

        $this->getJson("/api/municipality/fiscal/operator/{$operator->id}/summary")
            ->assertOk();

        $this->assertTrue(
            AuditLog::query()->where('action', 'fiscal.consultation')->exists()
        );
    }

    public function test_summary_rejects_inactive_operator(): void
    {
        $user = $this->fiscalManager();
        $operator = $this->createOperator($user);
        $operator->update(['is_active' => false]);

        Sanctum::actingAs($user);

        $this->getJson("/api/municipality/fiscal/operator/{$operator->id}/summary")
            ->assertStatus(422);
    }

    public function test_qr_scan_records_field_visit(): void
    {
        $user = $this->fiscalManager();
        $operator = $this->createOperator($user);
        $qr = app(QRCodeManagement::class)->generateForOperator($operator);

        Sanctum::actingAs($user);

        $this->getJson('/api/municipality/operators/by-qr/'.$qr->qr_uuid.'?latitude=0.33&longitude=9.47')
            ->assertOk();

        $this->assertDatabaseHas('field_visits', [
            'operator_id' => $operator->id,
            'visit_type' => VisitType::Scan->value,
        ]);
    }

    public function test_qr_scan_writes_audit_log(): void
    {
        $user = $this->fiscalManager();
        $operator = $this->createOperator($user);
        $qr = app(QRCodeManagement::class)->generateForOperator($operator);

        Sanctum::actingAs($user);

        $this->getJson('/api/municipality/operators/by-qr/'.$qr->qr_uuid)
            ->assertOk();

        $this->assertTrue(
            AuditLog::query()->where('action', 'fiscal.scan')->exists()
        );
    }

    public function test_supervisor_dashboard_returns_open_sessions(): void
    {
        $user = $this->fiscalManager();
        $this->openCashSession($user);

        Sanctum::actingAs($user);

        $this->getJson('/api/municipality/fiscal/supervisor/dashboard')
            ->assertOk()
            ->assertJsonPath('data.open_sessions_count', 1);
    }

    public function test_supervisor_dashboard_shows_collected_today(): void
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

        $this->getJson('/api/municipality/fiscal/supervisor/dashboard')
            ->assertOk()
            ->assertJsonPath('data.collected_today_xaf', '15000');
    }

    public function test_citizen_cannot_view_fiscal_summary(): void
    {
        $user = $this->fiscalManager();
        $operator = $this->createOperator($user);

        Sanctum::actingAs($this->citizenUser());

        $this->getJson("/api/municipality/fiscal/operator/{$operator->id}/summary")
            ->assertForbidden();
    }

    public function test_summary_totals_reflect_partial_payments(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $rate = $this->createTaxRate($user, $taxType, 10000);
        $operator = $this->createOperator($user);
        $this->createManualObligation($operator, $taxType, $rate, 10000, '2026-01-01', 'OWE-FO-2026-000101');
        $session = $this->openCashSession($user);

        Sanctum::actingAs($user);
        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, 3000))
            ->assertCreated();

        $this->getJson("/api/municipality/fiscal/operator/{$operator->id}/summary")
            ->assertOk()
            ->assertJsonPath('data.totals.balance_remaining', '7000');
    }

    public function test_multiple_consultations_create_multiple_visits(): void
    {
        $user = $this->fiscalManager();
        $operator = $this->createOperator($user);

        Sanctum::actingAs($user);

        $this->getJson("/api/municipality/fiscal/operator/{$operator->id}/summary")->assertOk();
        $this->getJson("/api/municipality/fiscal/operator/{$operator->id}/summary")->assertOk();

        $count = FieldVisit::query()
            ->where('operator_id', $operator->id)
            ->where('visit_type', VisitType::Consultation)
            ->count();

        $this->assertSame(2, $count);
    }
}
