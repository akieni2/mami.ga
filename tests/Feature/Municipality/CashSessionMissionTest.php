<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use App\Modules\Municipality\Enums\CashSessionStatus;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class CashSessionMissionTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
        config(['mami.municipality_finance.require_mission_for_cash_session' => true]);
    }

    public function test_open_session_requires_active_mission_when_enforced(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 10000,
            'latitude' => 0.3380,
            'longitude' => 9.4710,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['mission']);
    }

    public function test_open_session_succeeds_with_authorized_mission(): void
    {
        $daf = $this->dafUser();
        $agent = $this->municipalAgentUser();

        Sanctum::actingAs($daf);
        $missionId = $this->postJson('/api/municipality/finance/missions', [
            'title' => 'Mission caisse',
            'agent_id' => $agent->id,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
        ])->json('data.id');
        $this->postJson("/api/municipality/finance/missions/{$missionId}/authorize")->assertOk();

        Sanctum::actingAs($agent);
        $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 10000,
            'latitude' => 0.3380,
            'longitude' => 9.4710,
        ])->assertCreated()
            ->assertJsonPath('data.status', CashSessionStatus::Open->value);

        $this->assertDatabaseHas('cash_sessions', [
            'agent_id' => $agent->id,
            'financial_mission_id' => $missionId,
            'status' => CashSessionStatus::Open->value,
        ]);
    }

    public function test_controleur_can_admin_close_open_session(): void
    {
        $agent = $this->municipalAgentUser();
        $controller = $this->controleurUser();

        Sanctum::actingAs($agent);
        config(['mami.municipality_finance.require_mission_for_cash_session' => false]);
        $sessionId = $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 0,
            'latitude' => 0.3380,
            'longitude' => 9.4710,
        ])->json('data.id');

        Sanctum::actingAs($controller);
        $this->postJson("/api/municipality/fiscal/cash-sessions/{$sessionId}/admin-close", [
            'notes' => 'Clôture de fin de journée',
        ])->assertOk()
            ->assertJsonPath('data.status', CashSessionStatus::Closed->value)
            ->assertJsonPath('data.closure_type', 'administrative');
    }

    protected function dafUser(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', MamiRole::Daf->value)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user;
    }

    protected function controleurUser(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', MamiRole::ControleurFinancier->value)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user;
    }
}
