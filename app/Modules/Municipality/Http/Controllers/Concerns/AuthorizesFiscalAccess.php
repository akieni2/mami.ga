<?php

namespace App\Modules\Municipality\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

trait AuthorizesFiscalAccess
{
    protected function authorizeFiscalView(User $user): void
    {
        if (! $this->userCanFiscal($user, 'municipal.tax.view')) {
            throw new AuthorizationException('Accès fiscal non autorisé.');
        }
    }

    protected function authorizeFiscalManage(User $user): void
    {
        if (! $this->userCanFiscal($user, 'municipal.tax.manage')) {
            throw new AuthorizationException('Gestion fiscale non autorisée.');
        }
    }

    protected function authorizeFiscalAssign(User $user): void
    {
        if (! $this->userCanFiscal($user, 'municipal.tax.assign')) {
            throw new AuthorizationException('Affectation fiscale non autorisée.');
        }
    }

    private function userCanFiscal(User $user, string $permission): bool
    {
        return $user->isAdmin()
            || $user->hasPermission($permission)
            || $user->hasPermission('municipality.operators.manage');
    }
}
