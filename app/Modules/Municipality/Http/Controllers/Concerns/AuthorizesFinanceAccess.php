<?php

namespace App\Modules\Municipality\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

trait AuthorizesFinanceAccess
{
    protected function authorizeFinanceDashboard(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.dashboard.view')) {
            throw new AuthorizationException('Accès tableau de bord DAF non autorisé.');
        }
    }

    protected function authorizeMissionView(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.mission.view')) {
            throw new AuthorizationException('Consultation des missions non autorisée.');
        }
    }

    protected function authorizeMissionManage(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.mission.manage')) {
            throw new AuthorizationException('Gestion des missions non autorisée.');
        }
    }

    protected function authorizeMissionAuthorize(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.mission.authorize')) {
            throw new AuthorizationException('Autorisation de mission non autorisée.');
        }
    }

    protected function authorizeMissionSubmit(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.mission.submit')
            && ! $this->hasFinancePermission($user, 'municipal.finance.mission.manage')) {
            throw new AuthorizationException('Soumission de mission non autorisée.');
        }
    }

    protected function authorizeMissionControllerReview(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.mission.controller_review')) {
            throw new AuthorizationException('Revue contrôleur non autorisée.');
        }
    }

    protected function authorizeMissionDafReview(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.mission.daf_review')
            && ! $this->hasFinancePermission($user, 'municipal.finance.mission.authorize')) {
            throw new AuthorizationException('Revue DAF non autorisée.');
        }
    }

    protected function authorizeApprovalQueue(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.approval.view_queue')
            && ! $this->hasFinancePermission($user, 'municipal.finance.mission.view')) {
            throw new AuthorizationException('Consultation de la file de validation non autorisée.');
        }
    }

    protected function authorizeCashSupervision(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.cash_session.supervise')) {
            throw new AuthorizationException('Supervision des caisses non autorisée.');
        }
    }

    protected function authorizeCashAdminClose(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.cash_session.admin_close')) {
            throw new AuthorizationException('Clôture administrative non autorisée.');
        }
    }

    protected function authorizeJournalView(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.journal.view')) {
            throw new AuthorizationException('Consultation du journal non autorisée.');
        }
    }

    protected function authorizeRemittanceView(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.remittance.view')) {
            throw new AuthorizationException('Consultation des reversements non autorisée.');
        }
    }

    protected function authorizeRemittanceManage(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.remittance.manage')) {
            throw new AuthorizationException('Gestion des reversements non autorisée.');
        }
    }

    protected function authorizeRemittanceControl(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.remittance.control')) {
            throw new AuthorizationException('Contrôle des reversements non autorisé.');
        }
    }

    protected function authorizeRemittanceDafValidate(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.remittance.daf_validate')) {
            throw new AuthorizationException('Validation DAF des reversements non autorisée.');
        }
    }

    protected function authorizeRemittanceReceveurValidate(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.remittance.receveur_validate')) {
            throw new AuthorizationException('Validation receveur des reversements non autorisée.');
        }
    }

    protected function authorizeRemittanceDeposit(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.remittance.deposit')) {
            throw new AuthorizationException('Enregistrement de dépôt non autorisé.');
        }
    }

    protected function authorizeRemittanceConfirm(User $user): void
    {
        if (! $this->hasFinancePermission($user, 'municipal.finance.remittance.confirm')) {
            throw new AuthorizationException('Confirmation Trésor non autorisée.');
        }
    }

    protected function authorizeRemittanceReject(User $user, \App\Modules\Municipality\Models\MunicipalTreasuryRemittance $remittance): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $stagePermission = match ($remittance->status->value) {
            'controlled' => 'municipal.finance.remittance.daf_validate',
            'daf_validated' => 'municipal.finance.remittance.receveur_validate',
            'receveur_validated' => 'municipal.finance.remittance.deposit',
            default => null,
        };

        if ($stagePermission !== null && $this->hasFinancePermission($user, $stagePermission)) {
            return;
        }

        if ($this->hasFinancePermission($user, 'municipal.finance.remittance.reject')) {
            return;
        }

        throw new AuthorizationException('Rejet de reversement non autorisé.');
    }

    private function hasFinancePermission(User $user, string $permission): bool
    {
        return $user->isAdmin() || $user->hasPermission($permission);
    }
}
