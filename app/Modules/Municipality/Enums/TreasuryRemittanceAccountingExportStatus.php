<?php

namespace App\Modules\Municipality\Enums;

enum TreasuryRemittanceAccountingExportStatus: string
{
    case Pending = 'pending';
    case Exported = 'exported';
    case Posted = 'posted';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
