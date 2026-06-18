<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Core\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class FiscalAuditService
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        ?User $actor,
        Model $subject,
        string $subjectType,
        string $action,
        array $properties = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'subject_type' => $subjectType,
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'module' => 'municipality',
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
