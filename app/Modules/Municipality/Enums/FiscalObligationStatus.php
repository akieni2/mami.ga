<?php

namespace App\Modules\Municipality\Enums;

enum FiscalObligationStatus: string
{
    case Open = 'open';
    case Partial = 'partial';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Ouverte',
            self::Partial => 'Partielle',
            self::Paid => 'Payée',
            self::Cancelled => 'Annulée',
        };
    }
}
