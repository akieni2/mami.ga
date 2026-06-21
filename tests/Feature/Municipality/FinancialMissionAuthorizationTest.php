<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use App\Modules\Municipality\Enums\FinancialMissionStatus;
use App\Modules\Municipality\Enums\FinancialMissionWorkflowStatus;
use Laravel\Sanctum\Sanctum;

class FinancialMissionAuthorizationTest extends MunicipalityTestCase
{
    public function test_legacy_authorize_still_works_when_enabled(): void
    {
        config(['mami.municipality_finance.legacy_mission_authorize' => true]);

        $daf = $this->dafUser();
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($daf);

        $missionId = $this->postJson('/api/municipality/finance/missions', [
            'title' => 'Legacy authorize',
            'agent_id' => $agent->id,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
        ])->json('data.id');

        $this->postJson("/api/municipality/finance/missions/{$missionId}/authorize")
            ->assertOk()
            ->assertJsonPath('data.status', FinancialMissionStatus::Authorized->value)
            ->assertJsonPath('data.workflow_status', FinancialMissionWorkflowStatus::Approved->value);
    }

    public function test_legacy_authorize_blocked_when_disabled(): void
    {
        config(['mami.municipality_finance.legacy_mission_authorize' => false]);

        $daf = $this->dafUser();
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($daf);

        $missionId = $this->postJson('/api/municipality/finance/missions', [
            'title' => 'No legacy',
            'agent_id' => $agent->id,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
        ])->json('data.id');

        $this->postJson("/api/municipality/finance/missions/{$missionId}/authorize")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['legacy']);
    }

    protected function dafUser(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', MamiRole::Daf->value)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user;
    }
}
