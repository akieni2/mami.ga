<?php

namespace App\Modules\JbLudo\Enums;

enum PlayerLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case Expert = 'expert';

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Débutant',
            self::Intermediate => 'Intermédiaire',
            self::Advanced => 'Avancé',
            self::Expert => 'Expert',
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
