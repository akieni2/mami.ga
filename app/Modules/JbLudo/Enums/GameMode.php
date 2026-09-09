<?php

namespace App\Modules\JbLudo\Enums;

enum GameMode: string
{
    case Friendly = 'friendly';
    case Quick = 'quick';
    case Solo = 'solo';
    case Championship = 'championship';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
