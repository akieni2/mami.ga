<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\MunicipalTaxType;
use App\Modules\Municipality\Models\OperatorTaxAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FiscalAssignmentService
{
    public function __construct(
        private readonly FiscalAuditService $audit,
        private readonly TaxRateService $taxRateService,
        private readonly FiscalObligationGeneratorService $obligationGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = OperatorTaxAssignment::query()
            ->with(['operator', 'taxType', 'assignedBy'])
            ->orderByDesc('assigned_at');

        if (! empty($filters['operator_id'])) {
            $query->where('operator_id', $filters['operator_id']);
        }

        if (! empty($filters['tax_type_id'])) {
            $query->where('tax_type_id', $filters['tax_type_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{assignment: OperatorTaxAssignment, obligation_created: bool}
     */
    public function assign(User $actor, EconomicOperator $operator, MunicipalTaxType $taxType, array $data = []): array
    {
        return DB::transaction(function () use ($actor, $operator, $taxType, $data): array {
            $this->assertNoActiveDuplicate($operator->id, $taxType->id);
            $this->assertOperatorEligible($operator);
            $this->assertTaxTypeAssignable($taxType);

            $assignment = OperatorTaxAssignment::query()->create([
                'operator_id' => $operator->id,
                'tax_type_id' => $taxType->id,
                'assigned_at' => $data['assigned_at'] ?? now(),
                'assigned_by' => $actor->id,
                'is_active' => true,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->audit->log($actor, $assignment, 'operator_tax_assignment', 'tax_assignment.created', [
                'operator_id' => $operator->id,
                'operator_public_id' => $operator->public_id,
                'tax_type_id' => $taxType->id,
                'tax_code' => $taxType->code,
            ]);

            $assignment = $assignment->fresh(['operator', 'taxType', 'assignedBy']);
            $obligationResult = $this->obligationGenerator->generateForAssignment($assignment, now(), $actor);

            return [
                'assignment' => $assignment,
                'obligation_created' => $obligationResult['created'] > 0,
            ];
        });
    }

    public function activate(User $actor, OperatorTaxAssignment $assignment): OperatorTaxAssignment
    {
        return $this->setActive($actor, $assignment, true);
    }

    public function deactivate(User $actor, OperatorTaxAssignment $assignment): OperatorTaxAssignment
    {
        return $this->setActive($actor, $assignment, false);
    }

    private function setActive(User $actor, OperatorTaxAssignment $assignment, bool $active): OperatorTaxAssignment
    {
        if ($assignment->is_active === $active) {
            return $assignment;
        }

        if ($active) {
            $this->assertNoActiveDuplicate($assignment->operator_id, $assignment->tax_type_id, $assignment->id);
            $assignment->loadMissing(['operator', 'taxType']);
            $this->assertOperatorEligible($assignment->operator);
            $this->assertTaxTypeAssignable($assignment->taxType);
        }

        $assignment->update(['is_active' => $active]);

        $this->audit->log($actor, $assignment, 'operator_tax_assignment', $active ? 'tax_assignment.activated' : 'tax_assignment.deactivated', [
            'operator_id' => $assignment->operator_id,
            'tax_type_id' => $assignment->tax_type_id,
        ]);

        $assignment = $assignment->fresh(['operator', 'taxType', 'assignedBy']);

        if ($active) {
            $this->obligationGenerator->generateForAssignment($assignment, now(), $actor);
        }

        return $assignment;
    }

    private function assertOperatorEligible(EconomicOperator $operator): void
    {
        if (! $operator->is_active || $operator->trashed()) {
            throw ValidationException::withMessages([
                'operator_id' => ['Ce commerce est inactif ou archivé — affectation impossible.'],
            ]);
        }
    }

    private function assertTaxTypeAssignable(MunicipalTaxType $taxType): void
    {
        if (! $taxType->is_active) {
            throw ValidationException::withMessages([
                'tax_type_id' => ['Cette taxe est inactive.'],
            ]);
        }

        if ($this->taxRateService->resolveActiveRate($taxType) === null) {
            throw ValidationException::withMessages([
                'tax_type_id' => [
                    'Cette taxe n\'a pas de barème actif. Créez un taux fiscal avec une date de validité ≤ aujourd\'hui avant l\'affectation.',
                ],
            ]);
        }
    }

    private function assertNoActiveDuplicate(int $operatorId, int $taxTypeId, ?int $exceptId = null): void
    {
        $exists = OperatorTaxAssignment::query()
            ->where('operator_id', $operatorId)
            ->where('tax_type_id', $taxTypeId)
            ->where('is_active', true)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'tax_type_id' => ['Cette taxe est déjà affectée activement à cet opérateur.'],
            ]);
        }
    }
}
