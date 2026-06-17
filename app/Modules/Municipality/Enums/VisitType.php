<?php

namespace App\Modules\Municipality\Enums;

enum VisitType: string
{
    case Inspection = 'inspection';
    case Verification = 'verification';
    case Collection = 'collection';
    case Awareness = 'awareness';

    public function label(): string
    {
        return match ($this) {
            self::Inspection => 'Inspection',
            self::Verification => 'Vérification',
            self::Collection => 'Recouvrement',
            self::Awareness => 'Sensibilisation',
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
