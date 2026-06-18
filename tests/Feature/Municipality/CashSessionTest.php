<?php

namespace Tests\Feature\Municipality;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Municipality\Enums\CashSessionStatus;
use App\Modules\Municipality\Enums\VisitType;
use App\Modules\Municipality\Models\CashSession;
use App\Modules\Municipality\Models\FieldVisit;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class CashSessionTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
    }

    public function test_agent_can_open_cash_session(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 50000,
            'latitude' => 0.3380,
            'longitude' => 9.4710,
            'device_id' => 'pixel-test',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', CashSessionStatus::Open->value);

        $this->assertDatabaseHas('cash_sessions', [
            'agent_id' => $user->id,
            'opening_amount_xaf' => 50000,
            'status' => CashSessionStatus::Open->value,
        ]);

        $this->assertMatchesRegularExpression('/^OWE-CS-\d{4}-\d{6}$/', $response->json('data.reference'));
    }

    public function test_open_session_creates_field_visit_and_audit(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 0,
            'latitude' => 0.3380,
            'longitude' => 9.4710,
        ])->assertCreated();

        $this->assertDatabaseHas('field_visits', [
            'agent_id' => $user->id,
            'visit_type' => VisitType::SessionOpen->value,
            'operator_id' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'cash_session.opened',
            'module' => 'municipality',
        ]);
    }

    public function test_agent_cannot_open_two_sessions(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 0,
            'latitude' => 0.3380,
            'longitude' => 9.4710,
        ])->assertCreated();

        $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 0,
            'latitude' => 0.3380,
            'longitude' => 9.4710,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['session']);
    }

    public function test_current_returns_open_session(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);

        $session = $this->openCashSession($user, ['opening_amount_xaf' => 25000]);

        $this->getJson('/api/municipality/fiscal/cash-sessions/current')
            ->assertOk()
            ->assertJsonPath('data.id', $session->id)
            ->assertJsonPath('data.opening_amount_xaf', '25000.00');
    }

    public function test_current_returns_null_when_no_session(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);

        $this->getJson('/api/municipality/fiscal/cash-sessions/current')
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_agent_can_close_session(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);

        $session = $this->openCashSession($user, ['opening_amount_xaf' => 10000]);

        $this->postJson("/api/municipality/fiscal/cash-sessions/{$session->id}/close", [
            'actual_amount_xaf' => 10000,
            'latitude' => 0.3380,
            'longitude' => 9.4710,
        ])->assertOk()
            ->assertJsonPath('data.status', CashSessionStatus::Closed->value);

        $this->assertDatabaseHas('cash_sessions', [
            'id' => $session->id,
            'status' => CashSessionStatus::Closed->value,
            'actual_amount_xaf' => 10000,
        ]);
    }

    public function test_close_session_creates_field_visit_and_audit(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);

        $session = $this->openCashSession($user);

        $this->postJson("/api/municipality/fiscal/cash-sessions/{$session->id}/close", [
            'actual_amount_xaf' => 0,
        ])->assertOk();

        $this->assertDatabaseHas('field_visits', [
            'cash_session_id' => $session->id,
            'visit_type' => VisitType::SessionClose->value,
        ]);

        $this->assertTrue(
            AuditLog::query()->where('action', 'cash_session.closed')->exists()
        );
    }

    public function test_cannot_close_another_agents_session(): void
    {
        $owner = $this->fiscalManager();
        $intruder = $this->fiscalManager();
        $session = $this->openCashSession($owner);

        Sanctum::actingAs($intruder);

        $this->postJson("/api/municipality/fiscal/cash-sessions/{$session->id}/close", [
            'actual_amount_xaf' => 0,
        ])->assertStatus(422);
    }

    public function test_cannot_close_already_closed_session(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);

        $session = $this->openCashSession($user);

        $this->postJson("/api/municipality/fiscal/cash-sessions/{$session->id}/close", [
            'actual_amount_xaf' => 0,
        ])->assertOk();

        $this->postJson("/api/municipality/fiscal/cash-sessions/{$session->id}/close", [
            'actual_amount_xaf' => 0,
        ])->assertStatus(422);
    }

    public function test_expected_amount_includes_collections(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $this->generateObligations($user);

        $session = $this->openCashSession($user, ['opening_amount_xaf' => 5000]);
        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertCreated();

        $session->refresh();
        $this->assertSame(20000.0, (float) $session->expected_amount_xaf);
    }

    public function test_citizen_cannot_open_session(): void
    {
        Sanctum::actingAs($this->citizenUser());

        $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 0,
            'latitude' => 0.3380,
            'longitude' => 9.4710,
        ])->assertForbidden();
    }

    public function test_supervisor_can_list_sessions(): void
    {
        $user = $this->fiscalManager();
        $this->openCashSession($user);
        Sanctum::actingAs($user);

        $this->getJson('/api/municipality/fiscal/cash-sessions?status=open')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
