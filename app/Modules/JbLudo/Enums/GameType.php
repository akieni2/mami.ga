<?php

namespace App\Modules\JbLudo\Enums;

enum GameType: string
{
    case Damier = 'damier';
    case Ludo = 'ludo';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
