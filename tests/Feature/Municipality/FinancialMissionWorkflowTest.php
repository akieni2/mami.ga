<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use App\Modules\Municipality\Enums\FinancialMissionStatus;
use App\Modules\Municipality\Enums\FinancialMissionWorkflowStatus;
use Laravel\Sanctum\Sanctum;

class FinancialMissionWorkflowTest extends MunicipalityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['mami.municipality_finance.legacy_mission_authorize' => false]);
    }

    public function test_full_approval_chain(): void
    {
        $dafAdjoint = $this->userWithRole(MamiRole::DafAdjoint);
        $controleur = $this->userWithRole(MamiRole::ControleurFinancier);
        $daf = $this->userWithRole(MamiRole::Daf);
        $agent = $this->municipalAgentUser();

        Sanctum::actingAs($dafAdjoint);
        $missionId = $this->postJson('/api/municipality/finance/missions', [
            'title' => 'Recouvrement SNI',
            'agent_id' => $agent->id,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/municipality/finance/workflow/{$missionId}/submit")
            ->assertOk()
            ->assertJsonPath('data.workflow_status', FinancialMissionWorkflowStatus::Submitted->value);

        Sanctum::actingAs($controleur);
        $this->postJson("/api/municipality/finance/workflow/{$missionId}/review")
            ->assertOk()
            ->assertJsonPath('data.workflow_status', FinancialMissionWorkflowStatus::ControllerReview->value);

        Sanctum::actingAs($daf);
        $this->postJson("/api/municipality/finance/workflow/{$missionId}/review")
            ->assertOk()
            ->assertJsonPath('data.workflow_status', FinancialMissionWorkflowStatus::DafReview->value);

        $this->postJson("/api/municipality/finance/workflow/{$missionId}/approve")
            ->assertOk()
            ->assertJsonPath('data.workflow_status', FinancialMissionWorkflowStatus::Approved->value)
            ->assertJsonPath('data.status', FinancialMissionStatus::Authorized->value);

        $this->assertDatabaseHas('financial_mission_approvals', [
            'financial_mission_id' => $missionId,
            'action' => 'approved',
        ]);

        $this->assertDatabaseHas('municipal_finance_journal_entries', [
            'financial_mission_id' => $missionId,
            'event_type' => 'mission.approved',
        ]);
    }

    public function test_cannot_skip_controller_review(): void
    {
        $dafAdjoint = $this->userWithRole(MamiRole::DafAdjoint);
        $daf = $this->userWithRole(MamiRole::Daf);
        $agent = $this->municipalAgentUser();

        Sanctum::actingAs($dafAdjoint);
        $missionId = $this->postJson('/api/municipality/finance/missions', [
            'title' => 'Mission skip',
            'agent_id' => $agent->id,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
        ])->json('data.id');

        $this->postJson("/api/municipality/finance/workflow/{$missionId}/submit")->assertOk();

        Sanctum::actingAs($daf);
        $this->postJson("/api/municipality/finance/workflow/{$missionId}/approve")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['workflow_status']);
    }

    public function test_controller_rejects_mission_with_reason(): void
    {
        $dafAdjoint = $this->userWithRole(MamiRole::DafAdjoint);
        $controleur = $this->userWithRole(MamiRole::ControleurFinancier);
        $agent = $this->municipalAgentUser();

        Sanctum::actingAs($dafAdjoint);
        $missionId = $this->postJson('/api/municipality/finance/missions', [
            'title' => 'Mission rejet',
            'agent_id' => $agent->id,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
        ])->json('data.id');

        $this->postJson("/api/municipality/finance/workflow/{$missionId}/submit")->assertOk();

        Sanctum::actingAs($controleur);
        $this->postJson("/api/municipality/finance/workflow/{$missionId}/reject", [
            'reason' => 'Documents incomplets pour validation',
        ])->assertOk()
            ->assertJsonPath('data.workflow_status', FinancialMissionWorkflowStatus::Rejected->value);

        $this->assertDatabaseHas('financial_missions', [
            'id' => $missionId,
            'rejection_reason' => 'Documents incomplets pour validation',
        ]);
    }

    public function test_approved_mission_allows_cash_session_when_required(): void
    {
        config(['mami.municipality_finance.require_mission_for_cash_session' => true]);

        $dafAdjoint = $this->userWithRole(MamiRole::DafAdjoint);
        $controleur = $this->userWithRole(MamiRole::ControleurFinancier);
        $daf = $this->userWithRole(MamiRole::Daf);
        $agent = $this->municipalAgentUser();

        Sanctum::actingAs($dafAdjoint);
        $missionId = $this->postJson('/api/municipality/finance/missions', [
            'title' => 'Mission caisse workflow',
            'agent_id' => $agent->id,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
        ])->json('data.id');

        $this->postJson("/api/municipality/finance/workflow/{$missionId}/submit")->assertOk();

        Sanctum::actingAs($controleur);
        $this->postJson("/api/municipality/finance/workflow/{$missionId}/review")->assertOk();

        Sanctum::actingAs($daf);
        $this->postJson("/api/municipality/finance/workflow/{$missionId}/review")->assertOk();
        $this->postJson("/api/municipality/finance/workflow/{$missionId}/approve")->assertOk();

        Sanctum::actingAs($agent);
        $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 5000,
            'latitude' => 0.3380,
            'longitude' => 9.4710,
        ])->assertCreated();
    }

    protected function userWithRole(MamiRole $role): User
    {
        $user = User::factory()->create();
        $roleModel = Role::query()->where('slug', $role->value)->firstOrFail();
        $user->roles()->attach($roleModel->id, ['assigned_at' => now()]);

        return $user;
    }
}
