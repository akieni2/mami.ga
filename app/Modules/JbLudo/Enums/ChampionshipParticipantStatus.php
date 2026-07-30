<?php

namespace App\Modules\JbLudo\Enums;

enum ChampionshipParticipantStatus: string
{
    case Registered = 'registered';
    case Bye = 'bye';
    case Active = 'active';
    case Eliminated = 'eliminated';
    case Champion = 'champion';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
