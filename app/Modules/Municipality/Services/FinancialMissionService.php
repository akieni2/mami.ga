<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\FinancialMissionStatus;
use App\Modules\Municipality\Models\FinancialMission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialMissionService
{
    public function __construct(
        private readonly FinancialMissionReferenceGenerator $referenceGenerator,
        private readonly MunicipalFinanceJournalService $journal,
        private readonly FiscalAuditService $audit,
    ) {}

    public function activeForAgent(User $agent, ?string $date = null): ?FinancialMission
    {
        $date ??= now()->toDateString();

        return FinancialMission::query()
            ->where('agent_id', $agent->id)
            ->where('status', FinancialMissionStatus::Authorized)
            ->whereDate('valid_from', '<=', $date)
            ->whereDate('valid_until', '>=', $date)
            ->orderByDesc('authorized_at')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $creator, array $data): FinancialMission
    {
        return DB::transaction(function () use ($creator, $data): FinancialMission {
            $mission = FinancialMission::query()->create([
                'reference' => $this->referenceGenerator->next(),
                'title' => $data['title'],
                'agent_id' => $data['agent_id'],
                'operational_zone_id' => $data['operational_zone_id'] ?? null,
                'valid_from' => $data['valid_from'],
                'valid_until' => $data['valid_until'],
                'status' => FinancialMissionStatus::Draft,
                'created_by' => $creator->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->journal->record('mission.created', $mission, $creator, $mission, null, [
                'reference' => $mission->reference,
                'agent_id' => $mission->agent_id,
            ]);

            $this->audit->log($creator, $mission, 'financial_mission', 'financial_mission.created', [
                'reference' => $mission->reference,
            ]);

            return $mission->fresh(['agent', 'operationalZone', 'creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, FinancialMission $mission, array $data): FinancialMission
    {
        if ($mission->status !== FinancialMissionStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => ['Seules les missions en brouillon peuvent être modifiées.'],
            ]);
        }

        return DB::transaction(function () use ($actor, $mission, $data): FinancialMission {
            $mission->update([
                'title' => $data['title'] ?? $mission->title,
                'agent_id' => $data['agent_id'] ?? $mission->agent_id,
                'operational_zone_id' => $data['operational_zone_id'] ?? $mission->operational_zone_id,
                'valid_from' => $data['valid_from'] ?? $mission->valid_from,
                'valid_until' => $data['valid_until'] ?? $mission->valid_until,
                'notes' => $data['notes'] ?? $mission->notes,
            ]);

            $this->journal->record('mission.updated', $mission->fresh(), $actor, $mission);
            $this->audit->log($actor, $mission, 'financial_mission', 'financial_mission.updated');

            return $mission->fresh(['agent', 'operationalZone', 'creator', 'authorizer']);
        });
    }

    public function authorize(User $authorizer, FinancialMission $mission): FinancialMission
    {
        if ($mission->status !== FinancialMissionStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => ['Seules les missions en brouillon peuvent être autorisées.'],
            ]);
        }

        return DB::transaction(function () use ($authorizer, $mission): FinancialMission {
            $mission->update([
                'status' => FinancialMissionStatus::Authorized,
                'authorized_by' => $authorizer->id,
                'authorized_at' => now(),
            ]);

            $this->journal->record('mission.authorized', $mission, $authorizer, $mission, null, [
                'reference' => $mission->reference,
            ]);
            $this->audit->log($authorizer, $mission, 'financial_mission', 'financial_mission.authorized');

            return $mission->fresh(['agent', 'operationalZone', 'creator', 'authorizer']);
        });
    }

    public function close(User $closer, FinancialMission $mission, ?string $notes = null): FinancialMission
    {
        if ($mission->status === FinancialMissionStatus::Closed) {
            throw ValidationException::withMessages([
                'status' => ['La mission est déjà clôturée.'],
            ]);
        }

        return DB::transaction(function () use ($closer, $mission, $notes): FinancialMission {
            $mission->update([
                'status' => FinancialMissionStatus::Closed,
                'closed_by' => $closer->id,
                'closed_at' => now(),
                'notes' => trim(($mission->notes ?? '').' '.($notes ?? '')) ?: $mission->notes,
            ]);

            $this->journal->record('mission.closed', $mission, $closer, $mission);
            $this->audit->log($closer, $mission, 'financial_mission', 'financial_mission.closed');

            return $mission->fresh(['agent', 'operationalZone', 'creator', 'authorizer', 'closer']);
        });
    }
}
