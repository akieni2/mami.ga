<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Enums\FinancialMissionStatus;
use App\Modules\Municipality\Enums\FinancialMissionWorkflowStatus;
use App\Modules\Municipality\Enums\TreasuryRemittanceStatus;
use App\Modules\Municipality\Models\CashSession;
use App\Modules\Municipality\Models\FinancialMission;
use App\Modules\Municipality\Models\MunicipalFinanceJournalEntry;
use App\Modules\Municipality\Models\MunicipalTreasuryRemittance;

class DafDashboardService
{
    public function __construct(
        private readonly FiscalSupervisorDashboardService $supervisorDashboard,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(?string $date = null): array
    {
        $date ??= now()->toDateString();
        $supervisor = $this->supervisorDashboard->build($date);

        $workflowCounts = FinancialMission::query()
            ->selectRaw('workflow_status, COUNT(*) as total')
            ->groupBy('workflow_status')
            ->pluck('total', 'workflow_status');

        $pendingValidationCount = FinancialMission::query()
            ->whereIn('workflow_status', FinancialMissionWorkflowStatus::pendingValidationStatuses())
            ->count();

        $activeMissionsToday = FinancialMission::query()
            ->with(['agent:id,name', 'operationalZone:id,name'])
            ->where('workflow_status', FinancialMissionWorkflowStatus::Approved)
            ->whereDate('valid_from', '<=', $date)
            ->whereDate('valid_until', '>=', $date)
            ->orderBy('valid_until')
            ->get();

        $pendingMissionIds = FinancialMission::query()
            ->whereIn('workflow_status', FinancialMissionWorkflowStatus::pendingValidationStatuses())
            ->pluck('id');

        $collectedPendingValidationXaf = CashSession::query()
            ->whereIn('financial_mission_id', $pendingMissionIds)
            ->sum('expected_amount_xaf');

        $recentJournal = MunicipalFinanceJournalEntry::query()
            ->with(['actor:id,name', 'mission:id,reference', 'cashSession:id,reference'])
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get()
            ->map(fn (MunicipalFinanceJournalEntry $entry) => [
                'id' => $entry->id,
                'event_type' => $entry->event_type,
                'occurred_at' => $entry->occurred_at?->toIso8601String(),
                'actor_name' => $entry->actor?->name,
                'mission_reference' => $entry->mission?->reference,
                'cash_session_reference' => $entry->cashSession?->reference,
                'payload' => $entry->payload,
            ]);

        $remittances = [
            'draft_count' => MunicipalTreasuryRemittance::query()
                ->where('status', TreasuryRemittanceStatus::Draft)
                ->count(),
            'controlled_count' => MunicipalTreasuryRemittance::query()
                ->where('status', TreasuryRemittanceStatus::Controlled)
                ->count(),
            'daf_validated_count' => MunicipalTreasuryRemittance::query()
                ->where('status', TreasuryRemittanceStatus::DafValidated)
                ->count(),
            'receveur_validated_count' => MunicipalTreasuryRemittance::query()
                ->where('status', TreasuryRemittanceStatus::ReceveurValidated)
                ->count(),
            'deposited_count' => MunicipalTreasuryRemittance::query()
                ->where('status', TreasuryRemittanceStatus::Deposited)
                ->count(),
            'confirmed_total_xaf' => (string) MunicipalTreasuryRemittance::query()
                ->where('status', TreasuryRemittanceStatus::Confirmed)
                ->sum('amount_xaf'),
            'pending_count' => MunicipalTreasuryRemittance::query()
                ->whereIn('status', [
                    TreasuryRemittanceStatus::Draft,
                    TreasuryRemittanceStatus::Controlled,
                    TreasuryRemittanceStatus::DafValidated,
                    TreasuryRemittanceStatus::ReceveurValidated,
                    TreasuryRemittanceStatus::Deposited,
                ])
                ->count(),
        ];

        $legacyStatusCounts = FinancialMission::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'date' => $date,
            'missions' => [
                'draft_count' => (int) ($workflowCounts[FinancialMissionWorkflowStatus::Draft->value] ?? 0),
                'pending_validation_count' => $pendingValidationCount,
                'approved_count' => (int) ($workflowCounts[FinancialMissionWorkflowStatus::Approved->value] ?? 0),
                'rejected_count' => (int) ($workflowCounts[FinancialMissionWorkflowStatus::Rejected->value] ?? 0),
                'closed_count' => (int) ($workflowCounts[FinancialMissionWorkflowStatus::Closed->value] ?? 0),
                'authorized_count' => (int) ($legacyStatusCounts[FinancialMissionStatus::Authorized->value] ?? 0),
                'active_today' => $activeMissionsToday->map(fn (FinancialMission $mission) => [
                    'id' => $mission->id,
                    'reference' => $mission->reference,
                    'title' => $mission->title,
                    'agent_name' => $mission->agent?->name,
                    'zone_name' => $mission->operationalZone?->name,
                    'valid_from' => $mission->valid_from?->toDateString(),
                    'valid_until' => $mission->valid_until?->toDateString(),
                    'workflow_status' => $mission->workflow_status->value,
                ])->values()->all(),
            ],
            'validation' => [
                'pending_count' => $pendingValidationCount,
                'approved_count' => (int) ($workflowCounts[FinancialMissionWorkflowStatus::Approved->value] ?? 0),
                'rejected_count' => (int) ($workflowCounts[FinancialMissionWorkflowStatus::Rejected->value] ?? 0),
                'closed_count' => (int) ($workflowCounts[FinancialMissionWorkflowStatus::Closed->value] ?? 0),
                'collected_today_xaf' => $supervisor['collected_today_xaf'],
                'pending_validation_amount_xaf' => (string) $collectedPendingValidationXaf,
            ],
            'cash_supervision' => [
                'open_sessions_count' => $supervisor['open_sessions_count'],
                'open_sessions' => $supervisor['open_sessions']->map(fn (CashSession $session) => [
                    'id' => $session->id,
                    'reference' => $session->reference,
                    'agent_id' => $session->agent_id,
                    'agent_name' => $session->agent?->name,
                    'opened_at' => $session->opened_at?->toIso8601String(),
                    'opening_amount_xaf' => (string) $session->opening_amount_xaf,
                    'expected_amount_xaf' => (string) $session->expected_amount_xaf,
                ])->values()->all(),
                'collected_today_xaf' => $supervisor['collected_today_xaf'],
                'collections_by_agent' => collect($supervisor['collections_by_agent'])->map(fn ($row) => [
                    'agent_id' => $row->agent_id,
                    'agent_name' => $row->agent?->name,
                    'total_xaf' => (string) $row->total,
                    'count' => $row->count,
                ])->values()->all(),
                'collections_by_quartier' => collect($supervisor['collections_by_quartier'])->map(fn ($row) => [
                    'quartier' => $row->quartier,
                    'total_xaf' => (string) $row->total,
                    'count' => $row->count,
                ])->values()->all(),
                'active_agents' => collect($supervisor['active_agents'])->map(fn ($row) => [
                    'agent_id' => $row->agent_id,
                    'agent_name' => $row->agent_name,
                    'has_open_session' => $row->has_open_session,
                ])->values()->all(),
            ],
            'recent_journal' => $recentJournal,
            'treasury_remittances' => $remittances,
        ];
    }
}
