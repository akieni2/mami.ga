<?php

namespace App\Modules\Municipality\Enums;

enum ReportCategory: string
{
    case Voirie = 'voirie';
    case Eclairage = 'eclairage';
    case Dechets = 'dechets';
    case Inondations = 'inondations';
    case Marches = 'marches';
    case Securite = 'securite';
    case Environnement = 'environnement';

    public function label(): string
    {
        return match ($this) {
            self::Voirie => 'Voirie',
            self::Eclairage => 'Éclairage public',
            self::Dechets => 'Déchets',
            self::Inondations => 'Inondations',
            self::Marches => 'Marchés',
            self::Securite => 'Sécurité',
            self::Environnement => 'Environnement',
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
