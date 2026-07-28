<?php

namespace App\Modules\JbLudo\Enums;

enum MatchStatus: string
{
    case Waiting = 'waiting';
    case InProgress = 'in_progress';
    case Grace = 'grace';
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
