<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use App\Modules\Municipality\Enums\TreasuryRemittanceStatus;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\MunicipalTreasuryRemittance;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class TreasuryRemittanceWorkflowTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
    }

    public function test_full_remittance_cycle_draft_to_confirmed(): void
    {
        [$paymentIds, $periodStart, $periodEnd] = $this->seedPaymentsForPeriod(2, 10000);

        $receveur = $this->userWithRole(MamiRole::ReceveurMunicipal);
        $controleur = $this->userWithRole(MamiRole::ControleurFinancier);
        $daf = $this->userWithRole(MamiRole::Daf);

        Sanctum::actingAs($receveur);
        $remittanceId = $this->postJson('/api/municipality/finance/remittances', [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'payment_ids' => $paymentIds,
        ])->assertCreated()
            ->assertJsonPath('data.status', TreasuryRemittanceStatus::Draft->value)
            ->assertJsonPath('data.amount_xaf', '20000.00')
            ->json('data.id');

        $this->assertDatabaseHas('municipal_finance_journal_entries', [
            'event_type' => 'remittance.created',
            'subject_id' => $remittanceId,
        ]);

        Sanctum::actingAs($controleur);
        $this->postJson("/api/municipality/finance/remittances/{$remittanceId}/submit-control")
            ->assertOk()
            ->assertJsonPath('data.status', TreasuryRemittanceStatus::Controlled->value);

        Sanctum::actingAs($daf);
        $this->postJson("/api/municipality/finance/remittances/{$remittanceId}/validate-daf")
            ->assertOk()
            ->assertJsonPath('data.status', TreasuryRemittanceStatus::DafValidated->value);

        Sanctum::actingAs($receveur);
        $this->postJson("/api/municipality/finance/remittances/{$remittanceId}/validate-receveur")
            ->assertOk()
            ->assertJsonPath('data.status', TreasuryRemittanceStatus::ReceveurValidated->value);

        $this->postJson("/api/municipality/finance/remittances/{$remittanceId}/record-deposit", [
            'slip_number' => 'BORD-2026-001',
            'bank_name' => 'BGFI Bank',
            'deposit_reference' => 'DEP-12345',
            'deposited_at' => now()->toIso8601String(),
        ])->assertOk()
            ->assertJsonPath('data.status', TreasuryRemittanceStatus::Deposited->value);

        $this->postJson("/api/municipality/finance/remittances/{$remittanceId}/confirm", [
            'treasury_receipt_ref' => 'TRESOR-REF-9876',
        ])->assertOk()
            ->assertJsonPath('data.status', TreasuryRemittanceStatus::Confirmed->value)
            ->assertJsonPath('data.treasury_receipt_ref', 'TRESOR-REF-9876');

        foreach ([
            'remittance.created',
            'remittance.controlled',
            'remittance.daf_validated',
            'remittance.receveur_validated',
            'remittance.deposited',
            'remittance.confirmed',
        ] as $event) {
            $this->assertDatabaseHas('municipal_finance_journal_entries', [
                'event_type' => $event,
                'subject_id' => $remittanceId,
            ]);
        }
    }

    public function test_generate_from_period_allocates_payments(): void
    {
        $this->seedPaymentsForPeriod(1, 15000);

        $receveur = $this->userWithRole(MamiRole::ReceveurMunicipal);
        Sanctum::actingAs($receveur);

        $this->postJson('/api/municipality/finance/remittances/generate-from-period', [
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.amount_xaf', '15000.00')
            ->assertJsonPath('data.payment_count', 1);
    }

    public function test_reject_returns_to_draft(): void
    {
        [$paymentIds, $periodStart, $periodEnd] = $this->seedPaymentsForPeriod(1, 8000);

        $receveur = $this->userWithRole(MamiRole::ReceveurMunicipal);
        $controleur = $this->userWithRole(MamiRole::ControleurFinancier);
        $daf = $this->userWithRole(MamiRole::Daf);

        Sanctum::actingAs($receveur);
        $remittanceId = $this->postJson('/api/municipality/finance/remittances', [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'payment_ids' => $paymentIds,
        ])->json('data.id');

        Sanctum::actingAs($controleur);
        $this->postJson("/api/municipality/finance/remittances/{$remittanceId}/submit-control")->assertOk();

        Sanctum::actingAs($daf);
        $this->postJson("/api/municipality/finance/remittances/{$remittanceId}/reject", [
            'reason' => 'Pièces justificatives incomplètes pour validation',
        ])->assertOk()
            ->assertJsonPath('data.status', TreasuryRemittanceStatus::Draft->value);
    }

    public function test_same_actor_cannot_cumulate_validations(): void
    {
        [$paymentIds, $periodStart, $periodEnd] = $this->seedPaymentsForPeriod(1, 5000);

        $receveur = $this->userWithRole(MamiRole::ReceveurMunicipal);
        $daf = $this->userWithRole(MamiRole::Daf);

        Sanctum::actingAs($receveur);
        $remittanceId = $this->postJson('/api/municipality/finance/remittances', [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'payment_ids' => $paymentIds,
        ])->json('data.id');

        MunicipalTreasuryRemittance::query()->whereKey($remittanceId)->update([
            'status' => TreasuryRemittanceStatus::Controlled,
            'controlled_by' => $daf->id,
            'controlled_at' => now(),
        ]);

        Sanctum::actingAs($daf);
        $this->postJson("/api/municipality/finance/remittances/{$remittanceId}/validate-daf")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['actor']);
    }

    public function test_payment_cannot_be_allocated_twice(): void
    {
        [$paymentIds, $periodStart, $periodEnd] = $this->seedPaymentsForPeriod(1, 12000);

        $receveur = $this->userWithRole(MamiRole::ReceveurMunicipal);
        Sanctum::actingAs($receveur);

        $this->postJson('/api/municipality/finance/remittances', [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'payment_ids' => $paymentIds,
        ])->assertCreated();

        $this->postJson('/api/municipality/finance/remittances', [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'payment_ids' => $paymentIds,
        ])->assertStatus(422);
    }

    /**
     * @return array{0: list<int>, 1: string, 2: string}
     */
    private function seedPaymentsForPeriod(int $count, float $amountEach): array
    {
        $manager = $this->fiscalManager();
        $taxType = $this->createTaxType($manager);
        $this->createTaxRate($manager, $taxType, $amountEach);
        $operator = $this->createOperator($manager);
        $this->assignTax($manager, $operator, $taxType);
        $this->generateObligations($manager);

        $paymentIds = [];
        Sanctum::actingAs($manager);

        for ($i = 0; $i < $count; $i++) {
            $session = $this->openCashSession($manager);
            $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session, $amountEach))
                ->assertCreated();
            $paymentIds[] = (int) MunicipalPayment::query()->latest('id')->value('id');
        }

        $periodStart = now()->startOfMonth()->toDateString();
        $periodEnd = now()->endOfMonth()->toDateString();

        return [$paymentIds, $periodStart, $periodEnd];
    }

    protected function userWithRole(MamiRole $role): User
    {
        $user = User::factory()->create();
        $roleModel = Role::query()->where('slug', $role->value)->firstOrFail();
        $user->roles()->attach($roleModel->id, ['assigned_at' => now()]);

        return $user;
    }
}
