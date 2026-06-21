<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use Laravel\Sanctum\Sanctum;

class DafDashboardTest extends MunicipalityTestCase
{
    public function test_daf_can_view_dashboard(): void
    {
        $daf = $this->dafUser();
        Sanctum::actingAs($daf);

        $this->getJson('/api/municipality/finance/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'missions' => ['draft_count', 'authorized_count', 'closed_count', 'active_today'],
                    'cash_supervision' => ['open_sessions_count', 'open_sessions', 'collected_today_xaf'],
                    'recent_journal',
                    'treasury_remittances',
                ],
            ]);
    }

    protected function dafUser(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', MamiRole::Daf->value)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user;
    }
}
