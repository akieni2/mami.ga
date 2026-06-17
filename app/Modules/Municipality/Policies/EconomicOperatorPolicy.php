<?php

namespace App\Modules\Municipality\Policies;

use App\Models\User;
use App\Modules\Municipality\Models\EconomicOperator;

class EconomicOperatorPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasOperatorPermission($user, 'economic_operator.view');
    }

    public function view(User $user, EconomicOperator $operator): bool
    {
        return $this->hasOperatorPermission($user, 'economic_operator.view');
    }

    public function create(User $user): bool
    {
        return $this->hasOperatorPermission($user, 'economic_operator.create');
    }

    public function update(User $user, EconomicOperator $operator): bool
    {
        return $this->hasOperatorPermission($user, 'economic_operator.update');
    }

    public function inspect(User $user, EconomicOperator $operator): bool
    {
        return $this->hasOperatorPermission($user, 'economic_operator.inspect');
    }

    private function hasOperatorPermission(User $user, string $permission): bool
    {
        return $user->isAdmin()
            || $user->hasPermission($permission)
            || $user->hasPermission('municipality.operators.manage');
    }
}
