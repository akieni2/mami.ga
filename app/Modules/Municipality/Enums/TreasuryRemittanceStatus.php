<?php

namespace App\Modules\Municipality\Enums;

enum TreasuryRemittanceStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Remitted = 'remitted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Pending => 'En attente',
            self::Remitted => 'Reversé',
            self::Cancelled => 'Annulé',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
