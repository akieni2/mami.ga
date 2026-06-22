<?php

namespace App\Modules\Municipality\Enums;

enum TreasuryRemittanceStatus: string
{
    case Draft = 'draft';
    case Controlled = 'controlled';
    case DafValidated = 'daf_validated';
    case ReceveurValidated = 'receveur_validated';
    case Deposited = 'deposited';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Controlled => 'Contrôlé',
            self::DafValidated => 'Validé DAF',
            self::ReceveurValidated => 'Validé receveur',
            self::Deposited => 'Déposé',
            self::Confirmed => 'Confirmé Trésor',
            self::Cancelled => 'Annulé',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Controlled, self::Cancelled],
            self::Controlled => [self::DafValidated, self::Draft],
            self::DafValidated => [self::ReceveurValidated, self::Draft],
            self::ReceveurValidated => [self::Deposited, self::Draft],
            self::Deposited => [self::Confirmed],
            self::Confirmed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * @return list<string>
     */
    public static function pendingValidationStatuses(): array
    {
        return [
            self::Draft->value,
            self::Controlled->value,
            self::DafValidated->value,
            self::ReceveurValidated->value,
            self::Deposited->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
