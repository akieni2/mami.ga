<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use Laravel\Sanctum\Sanctum;

class FinancialMissionApprovalTest extends MunicipalityTestCase
{
    public function test_pending_queue_for_controleur(): void
    {
        config(['mami.municipality_finance.legacy_mission_authorize' => false]);

        $dafAdjoint = $this->userWithRole(MamiRole::DafAdjoint);
        $controleur = $this->userWithRole(MamiRole::ControleurFinancier);
        $agent = $this->municipalAgentUser();

        Sanctum::actingAs($dafAdjoint);
        $missionId = $this->postJson('/api/municipality/finance/missions', [
            'title' => 'File validation',
            'agent_id' => $agent->id,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
        ])->json('data.id');
        $this->postJson("/api/municipality/finance/workflow/{$missionId}/submit")->assertOk();

        Sanctum::actingAs($controleur);
        $this->getJson('/api/municipality/finance/approvals/pending')
            ->assertOk()
            ->assertJsonPath('data.0.id', $missionId);
    }

    public function test_approval_history_is_paginated(): void
    {
        config(['mami.municipality_finance.legacy_mission_authorize' => false]);

        $dafAdjoint = $this->userWithRole(MamiRole::DafAdjoint);
        $controleur = $this->userWithRole(MamiRole::ControleurFinancier);
        $agent = $this->municipalAgentUser();

        Sanctum::actingAs($dafAdjoint);
        $missionId = $this->postJson('/api/municipality/finance/missions', [
            'title' => 'Historique',
            'agent_id' => $agent->id,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
        ])->json('data.id');
        $this->postJson("/api/municipality/finance/workflow/{$missionId}/submit")->assertOk();

        Sanctum::actingAs($controleur);
        $this->getJson('/api/municipality/finance/approvals/history')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['id', 'action', 'financial_mission_id', 'created_at'],
                ],
            ]);
    }

    public function test_mission_workflow_history_endpoint(): void
    {
        config(['mami.municipality_finance.legacy_mission_authorize' => false]);

        $dafAdjoint = $this->userWithRole(MamiRole::DafAdjoint);
        $agent = $this->municipalAgentUser();

        Sanctum::actingAs($dafAdjoint);
        $missionId = $this->postJson('/api/municipality/finance/missions', [
            'title' => 'Timeline',
            'agent_id' => $agent->id,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
        ])->json('data.id');
        $this->postJson("/api/municipality/finance/workflow/{$missionId}/submit")->assertOk();

        $this->getJson("/api/municipality/finance/workflow/{$missionId}/history")
            ->assertOk()
            ->assertJsonPath('data.0.action', 'submitted');
    }

    protected function userWithRole(MamiRole $role): User
    {
        $user = User::factory()->create();
        $roleModel = Role::query()->where('slug', $role->value)->firstOrFail();
        $user->roles()->attach($roleModel->id, ['assigned_at' => now()]);

        return $user;
    }
}
