<?php

namespace Tests\Feature\Municipality;

use Laravel\Sanctum\Sanctum;

class MunicipalSyncStatusTest extends MunicipalityTestCase
{
    public function test_sync_status_returns_counts(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);

        $this->getJson('/api/municipality/sync/status')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'server_time',
                    'api_status',
                    'operators_count',
                    'payments_count',
                    'receipts_count',
                ],
            ])
            ->assertJsonPath('data.api_status', 'ok');
    }
}
