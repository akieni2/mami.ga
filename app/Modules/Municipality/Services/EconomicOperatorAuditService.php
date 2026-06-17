<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Municipality\Models\EconomicOperator;
use Illuminate\Support\Facades\Request;

class EconomicOperatorAuditService
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        ?User $actor,
        EconomicOperator $operator,
        string $action,
        array $properties = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'subject_type' => 'economic_operator',
            'subject_id' => $operator->id,
            'action' => $action,
            'module' => 'municipality',
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
