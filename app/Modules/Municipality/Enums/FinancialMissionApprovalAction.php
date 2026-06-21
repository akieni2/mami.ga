<?php

namespace App\Modules\Municipality\Enums;

enum FinancialMissionApprovalAction: string
{
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Soumission',
            self::Reviewed => 'Revue',
            self::Approved => 'Approbation',
            self::Rejected => 'Rejet',
        };
    }
}
