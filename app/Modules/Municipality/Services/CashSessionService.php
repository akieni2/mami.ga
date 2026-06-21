<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\CashSessionClosureType;
use App\Modules\Municipality\Enums\CashSessionStatus;
use App\Modules\Municipality\Enums\PaymentMethod;
use App\Modules\Municipality\Enums\PaymentStatus;
use App\Modules\Municipality\Enums\VisitType;
use App\Modules\Municipality\Models\CashSession;
use App\Modules\Municipality\Models\FieldVisit;
use App\Modules\Municipality\Models\MunicipalPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashSessionService
{
    public function __construct(
        private readonly CashSessionReferenceGenerator $referenceGenerator,
        private readonly FiscalAuditService $audit,
        private readonly FinancialMissionService $missionService,
        private readonly MunicipalFinanceJournalService $journal,
    ) {}

    public function currentOpenSession(User $agent): ?CashSession
    {
        return CashSession::query()
            ->where('agent_id', $agent->id)
            ->where('status', CashSessionStatus::Open)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function open(User $agent, array $data): CashSession
    {
        if ($this->currentOpenSession($agent) !== null) {
            throw ValidationException::withMessages([
                'session' => ['Une session de caisse est déjà ouverte.'],
            ]);
        }

        $mission = $this->resolveMissionForOpen($agent);

        return DB::transaction(function () use ($agent, $data, $mission): CashSession {
            $session = CashSession::query()->create([
                'reference' => $this->referenceGenerator->next(),
                'agent_id' => $agent->id,
                'financial_mission_id' => $mission?->id,
                'opened_at' => now(),
                'opening_amount_xaf' => $data['opening_amount_xaf'] ?? 0,
                'expected_amount_xaf' => $data['opening_amount_xaf'] ?? 0,
                'status' => CashSessionStatus::Open,
                'opening_latitude' => $data['latitude'] ?? null,
                'opening_longitude' => $data['longitude'] ?? null,
                'device_id' => $data['device_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            FieldVisit::query()->create([
                'operator_id' => null,
                'agent_id' => $agent->id,
                'cash_session_id' => $session->id,
                'visit_type' => VisitType::SessionOpen,
                'visit_date' => now()->toDateString(),
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->audit->log($agent, $session, 'cash_session', 'cash_session.opened', [
                'reference' => $session->reference,
                'opening_amount_xaf' => (string) $session->opening_amount_xaf,
                'financial_mission_id' => $mission?->id,
            ]);

            $this->journal->record('cash_session.opened', $session, $agent, $mission, $session, [
                'reference' => $session->reference,
                'opening_amount_xaf' => (string) $session->opening_amount_xaf,
            ]);

            return $session->fresh('agent');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function close(User $agent, CashSession $session, array $data): CashSession
    {
        if ($session->agent_id !== $agent->id) {
            throw ValidationException::withMessages(['session' => ['Session non autorisée.']]);
        }

        if (! $session->isOpen()) {
            throw ValidationException::withMessages(['session' => ['La session n\'est pas ouverte.']]);
        }

        return DB::transaction(function () use ($agent, $session, $data): CashSession {
            $expected = $this->calculateExpectedAmount($session);

            $session->update([
                'expected_amount_xaf' => $expected,
                'actual_amount_xaf' => $data['actual_amount_xaf'] ?? $expected,
                'closed_at' => now(),
                'status' => CashSessionStatus::Closed,
                'closure_type' => CashSessionClosureType::Agent,
                'closing_latitude' => $data['latitude'] ?? null,
                'closing_longitude' => $data['longitude'] ?? null,
                'notes' => trim(($session->notes ?? '').' '.($data['notes'] ?? '')) ?: null,
            ]);

            FieldVisit::query()->create([
                'operator_id' => null,
                'agent_id' => $agent->id,
                'cash_session_id' => $session->id,
                'visit_type' => VisitType::SessionClose,
                'visit_date' => now()->toDateString(),
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            $this->audit->log($agent, $session, 'cash_session', 'cash_session.closed', [
                'reference' => $session->reference,
                'expected_amount_xaf' => (string) $expected,
                'actual_amount_xaf' => (string) $session->actual_amount_xaf,
            ]);

            $session->refresh();

            $this->journal->record(
                'cash_session.closed',
                $session,
                $agent,
                $session->financialMission,
                $session,
                [
                    'reference' => $session->reference,
                    'expected_amount_xaf' => (string) $expected,
                    'actual_amount_xaf' => (string) $session->actual_amount_xaf,
                ],
            );

            return $session->fresh('agent');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function adminClose(User $supervisor, CashSession $session, array $data): CashSession
    {
        if (! $session->isOpen()) {
            throw ValidationException::withMessages(['session' => ['La session n\'est pas ouverte.']]);
        }

        return DB::transaction(function () use ($supervisor, $session, $data): CashSession {
            $expected = $this->calculateExpectedAmount($session);

            $session->update([
                'expected_amount_xaf' => $expected,
                'actual_amount_xaf' => $data['actual_amount_xaf'] ?? $expected,
                'closed_at' => now(),
                'status' => CashSessionStatus::Closed,
                'closure_type' => CashSessionClosureType::Administrative,
                'admin_closed_by' => $supervisor->id,
                'notes' => trim(($session->notes ?? '').' '.($data['notes'] ?? 'Clôture administrative')) ?: null,
            ]);

            $this->journal->record(
                'cash_session.admin_closed',
                $session,
                $supervisor,
                $session->financialMission,
                $session,
                [
                    'reference' => $session->reference,
                    'expected_amount_xaf' => (string) $expected,
                    'actual_amount_xaf' => (string) $session->actual_amount_xaf,
                    'reason' => $data['notes'] ?? null,
                ],
            );

            $this->audit->log($supervisor, $session, 'cash_session', 'cash_session.admin_closed', [
                'reference' => $session->reference,
            ]);

            return $session->fresh(['agent', 'adminClosedBy', 'financialMission']);
        });
    }

    private function resolveMissionForOpen(User $agent): ?FinancialMission
    {
        $mission = $this->missionService->activeForAgent($agent);
        $requireMission = (bool) config('mami.municipality_finance.require_mission_for_cash_session', false);
        $mustHaveMission = $requireMission
            || $agent->hasRole('caissier_central')
            || $agent->hasRole('receveur_municipal');

        if ($mustHaveMission && $mission === null && ! $this->canOpenWithoutMission($agent)) {
            throw ValidationException::withMessages([
                'mission' => ['Aucune mission financière active pour ouvrir la caisse.'],
            ]);
        }

        return $mission;
    }

    private function canOpenWithoutMission(User $agent): bool
    {
        return $agent->isAdmin() || $agent->hasPermission('municipal.cash_session.open_without_mission');
    }

    public function calculateExpectedAmount(CashSession $session): float
    {
        $collected = MunicipalPayment::query()
            ->where('cash_session_id', $session->id)
            ->where('payment_method', PaymentMethod::Cash)
            ->where('status', PaymentStatus::Completed)
            ->sum('amount');

        return round((float) $session->opening_amount_xaf + (float) $collected, 2);
    }

    public function assertSessionOpenForAgent(User $agent, int $cashSessionId): CashSession
    {
        $session = CashSession::query()->find($cashSessionId);

        if ($session === null || $session->agent_id !== $agent->id) {
            throw ValidationException::withMessages([
                'cash_session_id' => ['Session de caisse invalide.'],
            ]);
        }

        if (! $session->isOpen()) {
            throw ValidationException::withMessages([
                'cash_session_id' => ['La session de caisse est fermée.'],
            ]);
        }

        return $session;
    }
}
