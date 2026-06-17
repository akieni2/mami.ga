<?php

namespace App\Modules\Municipality\Policies;

use App\Models\User;
use App\Modules\Municipality\Models\MunicipalityReport;

class MunicipalityReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user) || $user->hasPermission('municipality.reports.create');
    }

    public function view(User $user, MunicipalityReport $report): bool
    {
        return $this->canManage($user) || $report->citizen_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('municipality.reports.create') || $this->canManage($user);
    }

    public function assign(User $user, MunicipalityReport $report): bool
    {
        return $this->canManage($user);
    }

    public function updateStatus(User $user, MunicipalityReport $report): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return $user->isAdmin()
            || $user->hasPermission('municipality.reports.manage')
            || $user->hasRole('municipal_agent');
    }
}
