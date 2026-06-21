<?php

namespace App\Modules\Municipality\Enums;

enum FinancialMissionWorkflowStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case ControllerReview = 'controller_review';
    case DafReview = 'daf_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Submitted => 'Soumise',
            self::ControllerReview => 'Revue contrôleur',
            self::DafReview => 'Revue DAF',
            self::Approved => 'Approuvée',
            self::Rejected => 'Rejetée',
            self::Closed => 'Clôturée',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::ControllerReview, self::Rejected],
            self::ControllerReview => [self::DafReview, self::Rejected],
            self::DafReview => [self::Approved, self::Rejected],
            self::Approved => [self::Closed],
            self::Rejected, self::Closed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isActiveForCollection(): bool
    {
        return $this === self::Approved;
    }

    /**
     * @return list<self>
     */
    public static function pendingValidationStatuses(): array
    {
        return [
            self::Submitted,
            self::ControllerReview,
            self::DafReview,
        ];
    }
}
