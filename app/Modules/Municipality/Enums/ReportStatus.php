<?php

namespace App\Modules\Municipality\Enums;

enum ReportStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::Assigned => 'Assigné',
            self::InProgress => 'En cours',
            self::Resolved => 'Résolu',
            self::Closed => 'Clôturé',
        };
    }

    public function mapColor(): string
    {
        return match ($this) {
            self::New => '#E53935',
            self::Assigned, self::InProgress => '#FB8C00',
            self::Resolved, self::Closed => '#43A047',
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
