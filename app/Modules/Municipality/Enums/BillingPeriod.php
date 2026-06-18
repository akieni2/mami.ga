<?php

namespace App\Modules\Municipality\Enums;

enum BillingPeriod: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Semiannual = 'semiannual';
    case Annual = 'annual';

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
            self::Monthly => 'Mensuel',
            self::Quarterly => 'Trimestriel',
            self::Semiannual => 'Semestriel',
            self::Annual => 'Annuel',
        };
    }
}
