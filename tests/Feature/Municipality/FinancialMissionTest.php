<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use App\Modules\Municipality\Enums\FinancialMissionStatus;
use App\Modules\Municipality\Models\FinancialMission;
use Laravel\Sanctum\Sanctum;

class FinancialMissionTest extends MunicipalityTestCase
{
    public function test_daf_can_create_and_authorize_mission(): void
    {
        $daf = $this->dafUser();
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($daf);

        $response = $this->postJson('/api/municipality/finance/missions', [
            'title' => 'Recouvrement SNI semaine 25',
            'agent_id' => $agent->id,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
        ])->assertCreated();

        $missionId = $response->json('data.id');

        $this->postJson("/api/municipality/finance/missions/{$missionId}/authorize")
            ->assertOk()
            ->assertJsonPath('data.status', FinancialMissionStatus::Authorized->value);

        $this->assertDatabaseHas('financial_missions', [
            'id' => $missionId,
            'status' => FinancialMissionStatus::Authorized->value,
        ]);

        $this->assertDatabaseHas('municipal_finance_journal_entries', [
            'event_type' => 'mission.authorized',
            'financial_mission_id' => $missionId,
        ]);
    }

    public function test_agent_sees_current_active_mission(): void
    {
        $daf = $this->dafUser();
        $agent = $this->municipalAgentUser();

        Sanctum::actingAs($daf);
        $missionId = $this->postJson('/api/municipality/finance/missions', [
            'title' => 'Mission active',
            'agent_id' => $agent->id,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
        ])->json('data.id');

        $this->postJson("/api/municipality/finance/missions/{$missionId}/authorize")->assertOk();

        Sanctum::actingAs($agent);
        $this->getJson('/api/municipality/finance/missions/current')
            ->assertOk()
            ->assertJsonPath('data.reference', FinancialMission::query()->findOrFail($missionId)->reference);
    }

    protected function dafUser(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', MamiRole::Daf->value)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user;
    }
}
