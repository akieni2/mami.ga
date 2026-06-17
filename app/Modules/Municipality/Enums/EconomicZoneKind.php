<?php

namespace App\Modules\Municipality\Enums;

enum EconomicZoneKind: string
{
    case Marche = 'marche';
    case ZoneIndustrielle = 'zone_industrielle';
    case ZonePortuaire = 'zone_portuaire';
    case ZoneCommerciale = 'zone_commerciale';

    public function label(): string
    {
        return match ($this) {
            self::Marche => 'Marché',
            self::ZoneIndustrielle => 'Zone industrielle',
            self::ZonePortuaire => 'Zone portuaire',
            self::ZoneCommerciale => 'Zone commerciale',
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
