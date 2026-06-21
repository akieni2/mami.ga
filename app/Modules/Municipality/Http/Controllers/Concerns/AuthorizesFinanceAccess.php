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

    private function hasFinancePermission(User $user, string $permission): bool
    {
        return $user->isAdmin() || $user->hasPermission($permission);
    }
}
