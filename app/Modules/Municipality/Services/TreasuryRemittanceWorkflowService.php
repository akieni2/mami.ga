<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\TreasuryRemittanceAccountingExportStatus;
use App\Modules\Municipality\Enums\TreasuryRemittanceApprovalAction;
use App\Modules\Municipality\Enums\TreasuryRemittanceStatus;
use App\Modules\Municipality\Models\MunicipalTreasuryRemittance;
use App\Modules\Municipality\Models\MunicipalTreasuryRemittanceApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TreasuryRemittanceWorkflowService
{
    public function __construct(
        private readonly MunicipalFinanceJournalService $journal,
        private readonly FiscalAuditService $audit,
        private readonly TreasuryRemittanceReconciliationService $reconciliation,
    ) {}

    public function control(User $actor, MunicipalTreasuryRemittance $remittance, ?string $comments = null): MunicipalTreasuryRemittance
    {
        $this->assertTransition($remittance, TreasuryRemittanceStatus::Controlled);
        $this->assertDistinctValidationActor($remittance, $actor);
        $this->reconciliation->assertAmountMatchesAllocations($remittance);

        return $this->transition($remittance, $actor, TreasuryRemittanceStatus::Controlled, [
            'controlled_by' => $actor->id,
            'controlled_at' => now(),
            'rejection_reason' => null,
        ], TreasuryRemittanceApprovalAction::Controlled, 'remittance.controlled', $comments);
    }

    public function validateDaf(User $actor, MunicipalTreasuryRemittance $remittance, ?string $comments = null): MunicipalTreasuryRemittance
    {
        $this->assertTransition($remittance, TreasuryRemittanceStatus::DafValidated);
        $this->assertDistinctValidationActor($remittance, $actor);

        return $this->transition($remittance, $actor, TreasuryRemittanceStatus::DafValidated, [
            'daf_validated_by' => $actor->id,
            'daf_validated_at' => now(),
            'validated_by' => $actor->id,
            'rejection_reason' => null,
        ], TreasuryRemittanceApprovalAction::DafValidated, 'remittance.daf_validated', $comments);
    }

    public function validateReceveur(User $actor, MunicipalTreasuryRemittance $remittance, ?string $comments = null): MunicipalTreasuryRemittance
    {
        $this->assertTransition($remittance, TreasuryRemittanceStatus::ReceveurValidated);
        $this->assertDistinctValidationActor($remittance, $actor);

        return $this->transition($remittance, $actor, TreasuryRemittanceStatus::ReceveurValidated, [
            'receveur_validated_by' => $actor->id,
            'receveur_validated_at' => now(),
            'rejection_reason' => null,
        ], TreasuryRemittanceApprovalAction::ReceveurValidated, 'remittance.receveur_validated', $comments);
    }

    /**
     * @param  array<string, mixed>  $depositData
     */
    public function recordDeposit(User $actor, MunicipalTreasuryRemittance $remittance, array $depositData, ?string $comments = null): MunicipalTreasuryRemittance
    {
        $this->assertTransition($remittance, TreasuryRemittanceStatus::Deposited);

        return $this->transition($remittance, $actor, TreasuryRemittanceStatus::Deposited, [
            'slip_number' => $depositData['slip_number'],
            'bank_name' => $depositData['bank_name'],
            'deposit_reference' => $depositData['deposit_reference'],
            'deposited_at' => $depositData['deposited_at'],
            'deposited_by' => $actor->id,
            'remitted_at' => $depositData['deposited_at'],
            'rejection_reason' => null,
        ], TreasuryRemittanceApprovalAction::Deposited, 'remittance.deposited', $comments, [
            'slip_number' => $depositData['slip_number'],
            'bank_name' => $depositData['bank_name'],
            'deposit_reference' => $depositData['deposit_reference'],
        ]);
    }

    public function confirm(User $actor, MunicipalTreasuryRemittance $remittance, string $treasuryReceiptRef, ?string $comments = null): MunicipalTreasuryRemittance
    {
        $this->assertTransition($remittance, TreasuryRemittanceStatus::Confirmed);

        if (trim($treasuryReceiptRef) === '') {
            throw ValidationException::withMessages([
                'treasury_receipt_ref' => ['La référence du reçu Trésor est obligatoire.'],
            ]);
        }

        return $this->transition($remittance, $actor, TreasuryRemittanceStatus::Confirmed, [
            'treasury_receipt_ref' => trim($treasuryReceiptRef),
            'confirmed_at' => now(),
            'confirmed_by' => $actor->id,
            'accounting_export_status' => TreasuryRemittanceAccountingExportStatus::Pending,
            'rejection_reason' => null,
        ], TreasuryRemittanceApprovalAction::Confirmed, 'remittance.confirmed', $comments, [
            'treasury_receipt_ref' => trim($treasuryReceiptRef),
        ]);
    }

    public function reject(User $actor, MunicipalTreasuryRemittance $remittance, string $reason, ?string $comments = null): MunicipalTreasuryRemittance
    {
        if (! in_array(TreasuryRemittanceStatus::Draft, $remittance->status->allowedTransitions(), true)) {
            throw ValidationException::withMessages([
                'status' => ['Ce reversement ne peut pas être rejeté dans son état actuel.'],
            ]);
        }

        if (strlen(trim($reason)) < 10) {
            throw ValidationException::withMessages([
                'reason' => ['Le motif de rejet doit contenir au moins 10 caractères.'],
            ]);
        }

        return $this->transition($remittance, $actor, TreasuryRemittanceStatus::Draft, [
            'rejection_reason' => trim($reason),
            'controlled_by' => null,
            'controlled_at' => null,
            'daf_validated_by' => null,
            'daf_validated_at' => null,
            'receveur_validated_by' => null,
            'receveur_validated_at' => null,
            'slip_number' => null,
            'bank_name' => null,
            'deposit_reference' => null,
            'deposited_at' => null,
            'deposited_by' => null,
            'treasury_receipt_ref' => null,
            'confirmed_at' => null,
            'confirmed_by' => null,
            'remitted_at' => null,
        ], TreasuryRemittanceApprovalAction::Rejected, 'remittance.rejected', $comments, [
            'reason' => trim($reason),
            'from_status' => $remittance->status->value,
        ]);
    }

    /**
     * @return list<MunicipalTreasuryRemittance>
     */
    public function pendingForUser(User $user): array
    {
        if ($user->isAdmin()) {
            return MunicipalTreasuryRemittance::query()
                ->with(['preparer:id,name'])
                ->whereIn('status', [
                    TreasuryRemittanceStatus::Draft,
                    TreasuryRemittanceStatus::Controlled,
                    TreasuryRemittanceStatus::DafValidated,
                    TreasuryRemittanceStatus::ReceveurValidated,
                    TreasuryRemittanceStatus::Deposited,
                ])
                ->orderByDesc('updated_at')
                ->get()
                ->all();
        }

        if ($user->hasPermission('municipal.finance.remittance.control')) {
            return MunicipalTreasuryRemittance::query()
                ->with(['preparer:id,name'])
                ->where('status', TreasuryRemittanceStatus::Draft)
                ->orderByDesc('updated_at')
                ->get()
                ->all();
        }

        if ($user->hasPermission('municipal.finance.remittance.daf_validate')) {
            return MunicipalTreasuryRemittance::query()
                ->with(['preparer:id,name'])
                ->where('status', TreasuryRemittanceStatus::Controlled)
                ->orderByDesc('controlled_at')
                ->get()
                ->all();
        }

        if ($user->hasPermission('municipal.finance.remittance.receveur_validate')) {
            return MunicipalTreasuryRemittance::query()
                ->with(['preparer:id,name'])
                ->where('status', TreasuryRemittanceStatus::DafValidated)
                ->orderByDesc('daf_validated_at')
                ->get()
                ->all();
        }

        if ($user->hasPermission('municipal.finance.remittance.deposit')) {
            return MunicipalTreasuryRemittance::query()
                ->with(['preparer:id,name'])
                ->where('status', TreasuryRemittanceStatus::ReceveurValidated)
                ->orderByDesc('receveur_validated_at')
                ->get()
                ->all();
        }

        if ($user->hasPermission('municipal.finance.remittance.confirm')) {
            return MunicipalTreasuryRemittance::query()
                ->with(['preparer:id,name'])
                ->where('status', TreasuryRemittanceStatus::Deposited)
                ->orderByDesc('deposited_at')
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
        MunicipalTreasuryRemittance $remittance,
        User $actor,
        TreasuryRemittanceStatus $targetStatus,
        array $attributes,
        TreasuryRemittanceApprovalAction $action,
        string $journalEvent,
        ?string $comments = null,
        array $extraPayload = [],
    ): MunicipalTreasuryRemittance {
        return DB::transaction(function () use ($remittance, $actor, $targetStatus, $attributes, $action, $journalEvent, $comments, $extraPayload): MunicipalTreasuryRemittance {
            $remittance->update(array_merge([
                'status' => $targetStatus,
            ], $attributes));

            $remittance = $remittance->fresh([
                'preparer:id,name',
                'controller:id,name',
                'dafValidator:id,name',
                'receveurValidator:id,name',
                'depositor:id,name',
                'confirmer:id,name',
                'paymentAllocations.payment',
            ]);

            $this->recordApproval($remittance, $action, $actor, $comments);
            $this->journal->record($journalEvent, $remittance, $actor, null, null, array_merge([
                'reference' => $remittance->reference,
                'status' => $targetStatus->value,
                'amount_xaf' => (string) $remittance->amount_xaf,
            ], $extraPayload));
            $this->audit->log($actor, $remittance, 'treasury_remittance', 'treasury_remittance.'.$targetStatus->value);

            return $remittance;
        });
    }

    private function assertTransition(MunicipalTreasuryRemittance $remittance, TreasuryRemittanceStatus $target): void
    {
        if (! $remittance->status->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => [
                    sprintf(
                        'Transition interdite : %s → %s.',
                        $remittance->status->value,
                        $target->value,
                    ),
                ],
            ]);
        }
    }

    private function assertDistinctValidationActor(MunicipalTreasuryRemittance $remittance, User $actor): void
    {
        $existingActors = $remittance->validationActorIds();

        if (in_array($actor->id, $existingActors, true)) {
            throw ValidationException::withMessages([
                'actor' => ['Le même utilisateur ne peut pas cumuler plusieurs validations sur ce reversement.'],
            ]);
        }
    }

    private function recordApproval(
        MunicipalTreasuryRemittance $remittance,
        TreasuryRemittanceApprovalAction $action,
        User $actor,
        ?string $comments,
    ): void {
        MunicipalTreasuryRemittanceApproval::query()->create([
            'remittance_id' => $remittance->id,
            'action' => $action,
            'performed_by' => $actor->id,
            'comments' => $comments,
            'created_at' => now(),
        ]);
    }
}
