<?php

namespace App\Modules\Municipality\Enums;

enum ReceiptStatus: string
{
    case Valid = 'valid';
    case Annulled = 'annulled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valide',
            self::Annulled => 'Annulée',
            self::Refunded => 'Remboursée',
        };
    }

    public function isValid(): bool
    {
        return $this === self::Valid;
    }
}
