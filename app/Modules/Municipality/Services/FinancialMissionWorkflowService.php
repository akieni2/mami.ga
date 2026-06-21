<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\FinancialMissionApprovalAction;
use App\Modules\Municipality\Enums\FinancialMissionStatus;
use App\Modules\Municipality\Enums\FinancialMissionWorkflowStatus;
use App\Modules\Municipality\Models\FinancialMission;
use App\Modules\Municipality\Models\FinancialMissionApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialMissionWorkflowService
{
    public function __construct(
        private readonly MunicipalFinanceJournalService $journal,
        private readonly FiscalAuditService $audit,
    ) {}

    public function submit(User $actor, FinancialMission $mission, ?string $comments = null): FinancialMission
    {
        $this->assertTransition($mission, FinancialMissionWorkflowStatus::Submitted);
        $this->assertNotSameActorAsPriorApproval($mission, $actor, FinancialMissionWorkflowStatus::Approved);

        return $this->transition($mission, $actor, FinancialMissionWorkflowStatus::Submitted, [
            'submitted_at' => now(),
        ], FinancialMissionApprovalAction::Submitted, 'mission.submitted', $comments);
    }

    public function moveToControllerReview(User $actor, FinancialMission $mission, ?string $comments = null): FinancialMission
    {
        $this->assertTransition($mission, FinancialMissionWorkflowStatus::ControllerReview);

        return $this->transition($mission, $actor, FinancialMissionWorkflowStatus::ControllerReview, [
            'controller_reviewed_at' => now(),
            'controller_id' => $actor->id,
        ], FinancialMissionApprovalAction::Reviewed, 'mission.reviewed', $comments, [
            'review_stage' => 'controller',
        ]);
    }

    public function moveToDafReview(User $actor, FinancialMission $mission, ?string $comments = null): FinancialMission
    {
        $this->assertTransition($mission, FinancialMissionWorkflowStatus::DafReview);

        return $this->transition($mission, $actor, FinancialMissionWorkflowStatus::DafReview, [
            'daf_reviewed_at' => now(),
            'daf_id' => $actor->id,
        ], FinancialMissionApprovalAction::Reviewed, 'mission.reviewed', $comments, [
            'review_stage' => 'daf',
        ]);
    }

    public function review(User $actor, FinancialMission $mission, ?string $comments = null): FinancialMission
    {
        return match ($mission->workflow_status) {
            FinancialMissionWorkflowStatus::Submitted => $this->moveToControllerReview($actor, $mission, $comments),
            FinancialMissionWorkflowStatus::ControllerReview => $this->moveToDafReview($actor, $mission, $comments),
            default => throw ValidationException::withMessages([
                'workflow_status' => ['Cette mission n\'est pas en attente de revue.'],
            ]),
        };
    }

    public function approve(User $actor, FinancialMission $mission, ?string $comments = null): FinancialMission
    {
        $this->assertTransition($mission, FinancialMissionWorkflowStatus::Approved);
        $this->assertNotSameActorAsPriorApproval($mission, $actor, FinancialMissionWorkflowStatus::Submitted);

        return $this->transition($mission, $actor, FinancialMissionWorkflowStatus::Approved, [
            'approved_at' => now(),
            'authorized_by' => $actor->id,
            'authorized_at' => now(),
            'status' => FinancialMissionStatus::Authorized,
            'daf_id' => $mission->daf_id ?? $actor->id,
        ], FinancialMissionApprovalAction::Approved, 'mission.approved', $comments);
    }

    public function reject(User $actor, FinancialMission $mission, string $reason, ?string $comments = null): FinancialMission
    {
        if (! in_array(FinancialMissionWorkflowStatus::Rejected, $mission->workflow_status->allowedTransitions(), true)) {
            throw ValidationException::withMessages([
                'workflow_status' => ['Cette mission ne peut pas être rejetée dans son état actuel.'],
            ]);
        }

        if (strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages([
                'reason' => ['Le motif de rejet doit contenir au moins 10 caractères.'],
            ]);
        }

        return $this->transition($mission, $actor, FinancialMissionWorkflowStatus::Rejected, [
            'rejected_at' => now(),
            'rejection_reason' => trim($reason),
        ], FinancialMissionApprovalAction::Rejected, 'mission.rejected', $comments, [
            'reason' => trim($reason),
        ]);
    }

    public function close(User $actor, FinancialMission $mission, ?string $notes = null): FinancialMission
    {
        $this->assertTransition($mission, FinancialMissionWorkflowStatus::Closed);

        return DB::transaction(function () use ($actor, $mission, $notes): FinancialMission {
            $mission->update([
                'workflow_status' => FinancialMissionWorkflowStatus::Closed,
                'status' => FinancialMissionStatus::Closed,
                'closed_by' => $actor->id,
                'closed_at' => now(),
                'notes' => trim(($mission->notes ?? '').' '.($notes ?? '')) ?: $mission->notes,
            ]);

            $mission = $mission->fresh(['agent', 'operationalZone', 'creator', 'authorizer', 'closer', 'controller', 'daf']);

            $this->recordApproval($mission, FinancialMissionApprovalAction::Reviewed, $actor, $notes);
            $this->journal->record('mission.closed', $mission, $actor, $mission);
            $this->audit->log($actor, $mission, 'financial_mission', 'financial_mission.closed');

            return $mission;
        });
    }

    /**
     * @return list<FinancialMission>
     */
    public function pendingForUser(User $user): array
    {
        $query = FinancialMission::query()
            ->with(['agent:id,name', 'operationalZone:id,name'])
            ->whereIn('workflow_status', FinancialMissionWorkflowStatus::pendingValidationStatuses());

        if ($user->isAdmin() || $user->hasPermission('municipal.finance.mission.authorize')) {
            return $query->orderByDesc('submitted_at')->get()->all();
        }

        if ($user->hasPermission('municipal.finance.mission.controller_review')) {
            return $query
                ->where('workflow_status', FinancialMissionWorkflowStatus::Submitted)
                ->orderByDesc('submitted_at')
                ->get()
                ->all();
        }

        if ($user->hasPermission('municipal.finance.mission.daf_review')) {
            return $query
                ->whereIn('workflow_status', [
                    FinancialMissionWorkflowStatus::ControllerReview,
                    FinancialMissionWorkflowStatus::DafReview,
                ])
                ->orderByDesc('submitted_at')
                ->get()
                ->all();
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $extraPayload
     * @param  array<string, mixed>  $attributes
     */
    private function transition(
        FinancialMission $mission,
        User $actor,
        FinancialMissionWorkflowStatus $targetStatus,
        array $attributes,
        FinancialMissionApprovalAction $action,
        string $journalEvent,
        ?string $comments = null,
        array $extraPayload = [],
    ): FinancialMission {
        return DB::transaction(function () use ($mission, $actor, $targetStatus, $attributes, $action, $journalEvent, $comments, $extraPayload): FinancialMission {
            $mission->update(array_merge([
                'workflow_status' => $targetStatus,
            ], $attributes));

            $mission = $mission->fresh(['agent', 'operationalZone', 'creator', 'authorizer', 'controller', 'daf']);

            $this->recordApproval($mission, $action, $actor, $comments);
            $this->journal->record($journalEvent, $mission, $actor, $mission, null, array_merge([
                'reference' => $mission->reference,
                'workflow_status' => $targetStatus->value,
            ], $extraPayload));
            $this->audit->log($actor, $mission, 'financial_mission', 'financial_mission.'.$targetStatus->value);

            return $mission;
        });
    }

    private function assertTransition(FinancialMission $mission, FinancialMissionWorkflowStatus $target): void
    {
        if (! $mission->workflow_status->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'workflow_status' => [
                    sprintf(
                        'Transition interdite : %s → %s.',
                        $mission->workflow_status->value,
                        $target->value,
                    ),
                ],
            ]);
        }
    }

    private function recordApproval(
        FinancialMission $mission,
        FinancialMissionApprovalAction $action,
        User $actor,
        ?string $comments,
    ): void {
        FinancialMissionApproval::query()->create([
            'financial_mission_id' => $mission->id,
            'action' => $action->value,
            'performed_by' => $actor->id,
            'comments' => $comments,
            'created_at' => now(),
        ]);
    }

    private function assertNotSameActorAsPriorApproval(
        FinancialMission $mission,
        User $actor,
        FinancialMissionWorkflowStatus $priorStage,
    ): void {
        $submitterId = FinancialMissionApproval::query()
            ->where('financial_mission_id', $mission->id)
            ->where('action', FinancialMissionApprovalAction::Submitted->value)
            ->value('performed_by');

        if ($priorStage === FinancialMissionWorkflowStatus::Approved && $submitterId && (int) $submitterId === $actor->id) {
            throw ValidationException::withMessages([
                'actor' => ['Le même utilisateur ne peut pas soumettre et approuver une mission.'],
            ]);
        }
    }
}
