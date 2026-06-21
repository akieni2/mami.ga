<?php

namespace App\Modules\Municipality\Enums;

enum FiscalObligationType: string
{
    case Tax = 'tax';
    case Penalty = 'penalty';
    case Fine = 'fine';

    public function label(): string
    {
        return match ($this) {
            self::Tax => 'Taxe',
            self::Penalty => 'Pénalité',
            self::Fine => 'Amende',
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
