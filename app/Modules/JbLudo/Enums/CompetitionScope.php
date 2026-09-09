<?php

namespace App\Modules\JbLudo\Enums;

enum CompetitionScope: string
{
    case Neighborhood = 'neighborhood';
    case City = 'city';
    case National = 'national';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
