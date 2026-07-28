<?php

namespace App\Modules\JbLudo\Enums;

enum GameMode: string
{
    case Friendly = 'friendly';
    case Quick = 'quick';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
