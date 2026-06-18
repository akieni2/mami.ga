<?php

namespace App\Modules\Municipality\Enums;

enum CashSessionStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Validated = 'validated';

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
            self::Closed => 'Fermée',
            self::Validated => 'Validée',
        };
    }
}
