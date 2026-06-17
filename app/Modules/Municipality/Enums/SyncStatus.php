<?php

namespace App\Modules\Municipality\Enums;

enum SyncStatus: string
{
    case PendingSync = 'pending_sync';
    case Synced = 'synced';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PendingSync => 'En attente de synchronisation',
            self::Synced => 'Synchronisé',
            self::Failed => 'Échec de synchronisation',
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
