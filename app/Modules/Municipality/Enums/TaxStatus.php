<?php

namespace App\Modules\Municipality\Enums;

enum TaxStatus: string
{
    case A_Jour = 'a_jour';
    case Retard90 = 'retard_90';
    case RetardPlus90 = 'retard_plus_90';
    case NonEnregistre = 'non_enregistre';

    public function label(): string
    {
        return match ($this) {
            self::A_Jour => 'À jour',
            self::Retard90 => 'Retard < 90 j',
            self::RetardPlus90 => 'Retard > 90 j',
            self::NonEnregistre => 'Non enregistré',
        };
    }

    public function mapColor(): string
    {
        return match ($this) {
            self::A_Jour => '#22c55e',
            self::Retard90 => '#f59e0b',
            self::RetardPlus90 => '#ef4444',
            self::NonEnregistre => '#6b7280',
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
