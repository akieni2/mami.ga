<?php

namespace App\Modules\Municipality\Enums;

enum ReceiptDocumentFormat: string
{
    case A4Pdf = 'a4_pdf';
    case Thermal58mm = 'thermal_58mm';

    public function label(): string
    {
        return match ($this) {
            self::A4Pdf => 'PDF A4',
            self::Thermal58mm => 'Thermique 58 mm',
        };
    }
}
