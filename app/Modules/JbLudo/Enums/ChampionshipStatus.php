<?php

namespace App\Modules\JbLudo\Enums;

enum ChampionshipStatus: string
{
    case Draft = 'draft';
    case RegistrationOpen = 'registration_open';
    case Generated = 'generated';
    case InProgress = 'in_progress';
    case Finished = 'finished';
    case Cancelled = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
