<?php

namespace App\Modules\Core\Enums;

enum MamiModule: string
{
    case Taxi = 'taxi';
    case Carpool = 'carpool';
    case Transport = 'transport';
    case Commerce = 'commerce';
    case Municipality = 'municipality';
    case JbLudo = 'jb_ludo';

    public function label(): string
    {
        return match ($this) {
            self::Taxi => 'Taxi',
            self::Carpool => 'Covoiturage',
            self::Transport => 'Transport',
            self::Commerce => 'Commerce',
            self::Municipality => 'Mairie',
            self::JbLudo => 'JB Ludo',
        };
    }
}
