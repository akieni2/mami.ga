<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Municipality\Models\MunicipalityReport;
use Illuminate\Support\Facades\Request;

class MunicipalityAuditService
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        ?User $actor,
        MunicipalityReport $report,
        string $action,
        array $properties = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'subject_type' => 'municipality_report',
            'subject_id' => $report->id,
            'action' => $action,
            'module' => 'municipality',
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
