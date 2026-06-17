<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\VisitType;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\FieldVisit;
use Illuminate\Support\Facades\DB;

class FieldVisitService
{
    public function __construct(
        private readonly EconomicOperatorAuditService $auditService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(User $agent, EconomicOperator $operator, array $data): FieldVisit
    {
        return DB::transaction(function () use ($agent, $operator, $data): FieldVisit {
            $visit = FieldVisit::query()->create([
                'operator_id' => $operator->id,
                'agent_id' => $agent->id,
                'visit_type' => VisitType::from($data['visit_type']),
                'visit_date' => $data['visit_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            $operator->update([
                'last_visit_at' => now(),
                'last_modified_by' => $agent->id,
            ]);

            $this->auditService->log($agent, $operator, 'operator.field_visit', [
                'visit_id' => $visit->id,
                'visit_type' => $visit->visit_type->value,
            ]);

            return $visit->fresh(['agent']);
        });
    }
}
