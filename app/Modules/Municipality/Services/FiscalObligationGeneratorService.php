<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\FiscalObligationStatus;
use App\Modules\Municipality\Enums\FiscalObligationType;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\FiscalObligation;
use App\Modules\Municipality\Models\OperatorTaxAssignment;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FiscalObligationGeneratorService
{
    public function __construct(
        private readonly TaxRateService $taxRateService,
        private readonly BillingPeriodResolver $periodResolver,
        private readonly FiscalObligationReferenceGenerator $referenceGenerator,
        private readonly FiscalAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = FiscalObligation::query()
            ->with(['operator', 'taxType', 'taxRate'])
            ->orderByDesc('due_date');

        if (! empty($filters['operator_id'])) {
            $query->where('operator_id', $filters['operator_id']);
        }

        if (! empty($filters['tax_type_id'])) {
            $query->where('tax_type_id', $filters['tax_type_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): FiscalObligation
    {
        return FiscalObligation::query()
            ->with(['operator', 'taxType', 'taxRate'])
            ->findOrFail($id);
    }

    /**
     * @return array{created: int, skipped: int}
     */
    public function generate(?User $actor = null, ?Carbon $on = null): array
    {
        $on ??= now();
        $created = 0;
        $skipped = 0;

        $assignments = OperatorTaxAssignment::query()
            ->with(['operator', 'taxType'])
            ->where('is_active', true)
            ->whereHas('operator', fn ($q) => $q->where('is_active', true))
            ->whereHas('taxType', fn ($q) => $q->where('is_active', true))
            ->cursor();

        foreach ($assignments as $assignment) {
            $result = $this->generateForAssignment($assignment, $on, $actor);
            $created += $result['created'];
            $skipped += $result['skipped'];
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * @return array{created: int, skipped: int}
     */
    public function generateForAssignment(OperatorTaxAssignment $assignment, ?Carbon $on = null, ?User $actor = null): array
    {
        $on ??= now();
        $taxType = $assignment->taxType;
        $rate = $this->taxRateService->resolveActiveRate($taxType, $on);

        if ($rate === null) {
            return ['created' => 0, 'skipped' => 1];
        }

        $period = $this->periodResolver->currentPeriod($rate->billing_period, $on);
        $periodStart = $period['start']->toDateString();
        $periodEnd = $period['end']->toDateString();

        $exists = FiscalObligation::query()
            ->where('operator_id', $assignment->operator_id)
            ->where('tax_type_id', $assignment->tax_type_id)
            ->where('period_start', $periodStart)
            ->where('period_end', $periodEnd)
            ->exists();

        if ($exists) {
            return ['created' => 0, 'skipped' => 1];
        }

        return DB::transaction(function () use ($assignment, $rate, $periodStart, $periodEnd, $period, $actor): array {
            $amountDue = (float) $rate->amount_xaf;
            $dueDate = $this->periodResolver->dueDate($period['end'])->toDateString();

            $obligation = FiscalObligation::query()->create([
                'operator_id' => $assignment->operator_id,
                'tax_type_id' => $assignment->tax_type_id,
                'tax_rate_id' => $rate->id,
                'obligation_type' => FiscalObligationType::Tax,
                'reference' => $this->referenceGenerator->next(),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'amount_due' => $amountDue,
                'amount_paid' => 0,
                'balance_due' => $amountDue,
                'status' => FiscalObligationStatus::Open,
                'generated_at' => now(),
                'due_date' => $dueDate,
            ]);

            if ($actor !== null) {
                $this->audit->log($actor, $obligation, 'fiscal_obligation', 'obligation.created', [
                    'reference' => $obligation->reference,
                    'operator_id' => $obligation->operator_id,
                    'tax_type_id' => $obligation->tax_type_id,
                    'period_label' => $period['label'],
                ]);
            }

            return ['created' => 1, 'skipped' => 0];
        });
    }

    /**
     * @return array{created: int, skipped: int}
     */
    public function ensureForOperator(EconomicOperator $operator, ?User $actor = null, ?Carbon $on = null): array
    {
        $on ??= now();
        $created = 0;
        $skipped = 0;

        $assignments = OperatorTaxAssignment::query()
            ->with(['operator', 'taxType'])
            ->where('operator_id', $operator->id)
            ->where('is_active', true)
            ->whereHas('taxType', fn ($query) => $query->where('is_active', true))
            ->get();

        foreach ($assignments as $assignment) {
            $result = $this->generateForAssignment($assignment, $on, $actor);
            $created += $result['created'];
            $skipped += $result['skipped'];
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function cancel(User $actor, FiscalObligation $obligation): FiscalObligation
    {
        if ($obligation->status === FiscalObligationStatus::Paid) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => ['Impossible d\'annuler une obligation déjà payée.'],
            ]);
        }

        $obligation->update([
            'status' => FiscalObligationStatus::Cancelled,
            'balance_due' => 0,
        ]);

        $this->audit->log($actor, $obligation, 'fiscal_obligation', 'obligation.cancelled', [
            'reference' => $obligation->reference,
        ]);

        return $obligation->fresh();
    }
}
