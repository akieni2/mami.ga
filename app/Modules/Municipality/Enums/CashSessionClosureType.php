<?php

namespace App\Modules\Municipality\Enums;

enum CashSessionClosureType: string
{
    case Agent = 'agent';
    case Administrative = 'administrative';

    public function label(): string
    {
        return match ($this) {
            self::Agent => 'Clôture agent',
            self::Administrative => 'Clôture administrative',
        };
    }
}
