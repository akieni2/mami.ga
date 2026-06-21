<?php

namespace App\Modules\Municipality\Enums;

enum FinancialMissionStatus: string
{
    case Draft = 'draft';
    case Authorized = 'authorized';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Authorized => 'Autorisée',
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
}
