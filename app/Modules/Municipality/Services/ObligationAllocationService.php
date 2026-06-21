<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\FiscalObligationStatus;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\FiscalObligation;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\MunicipalPaymentAllocation;
use App\Modules\Municipality\Models\OperatorTaxAssignment;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ObligationAllocationService
{
    public function __construct(
        private readonly FiscalObligationGeneratorService $obligationGenerator,
    ) {}

    /**
     * @return Collection<int, array{obligation: FiscalObligation, amount: float}>
     */
    public function allocate(EconomicOperator $operator, float $amount, ?User $actor = null): Collection
    {
        $obligations = $this->loadOpenObligations($operator);

        if ($obligations->isEmpty()) {
            $this->ensureInitialObligations($operator, $actor);
            $obligations = $this->loadOpenObligations($operator);
        }

        $remaining = round($amount, 2);
        $allocations = collect();

        foreach ($obligations as $obligation) {
            if ($remaining <= 0) {
                break;
            }

            $payable = min((float) $obligation->balance_due, $remaining);
            if ($payable <= 0) {
                continue;
            }

            $allocations->push([
                'obligation' => $obligation,
                'amount' => $payable,
            ]);

            $remaining = round($remaining - $payable, 2);
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'amount_xaf' => ['Le montant dépasse le solde dû de l\'opérateur.'],
            ]);
        }

        return $allocations;
    }

    private function ensureInitialObligations(EconomicOperator $operator, ?User $actor): void
    {
        $assignments = OperatorTaxAssignment::query()
            ->where('operator_id', $operator->id)
            ->where('is_active', true)
            ->whereHas('taxType', fn ($query) => $query->where('is_active', true))
            ->exists();

        if (! $assignments) {
            throw ValidationException::withMessages([
                'operator_id' => ['Aucune taxe n\'est affectée à ce commerce.'],
            ]);
        }

        $result = $this->obligationGenerator->ensureForOperator($operator, $actor);

        if ($result['created'] === 0 && $this->loadOpenObligations($operator)->isEmpty()) {
            throw ValidationException::withMessages([
                'operator_id' => ['Aucune taxe active applicable n\'a été trouvée pour ce commerce.'],
            ]);
        }
    }

    /**
     * @param  list<int>  $obligationIds
     * @return array{
     *     allocations: Collection<int, array{obligation: FiscalObligation, amount: float}>,
     *     amount: float
     * }
     */
    public function allocateSelected(EconomicOperator $operator, array $obligationIds, ?User $actor = null): array
    {
        $obligationIds = array_values(array_unique(array_map('intval', $obligationIds)));

        if ($obligationIds === []) {
            throw ValidationException::withMessages([
                'obligation_ids' => ['Sélectionnez au moins une créance à régler.'],
            ]);
        }

        $openObligations = $this->loadOpenObligations($operator);

        if ($openObligations->isEmpty()) {
            $this->ensureInitialObligations($operator, $actor);
            $openObligations = $this->loadOpenObligations($operator);
        }

        $selected = $openObligations->whereIn('id', $obligationIds)->values();

        if ($selected->count() !== count($obligationIds)) {
            throw ValidationException::withMessages([
                'obligation_ids' => ['Une ou plusieurs créances sélectionnées sont invalides ou déjà soldées.'],
            ]);
        }

        $allocations = $selected->map(fn (FiscalObligation $obligation) => [
            'obligation' => $obligation,
            'amount' => (float) $obligation->balance_due,
        ]);

        $amount = round((float) $allocations->sum('amount'), 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'obligation_ids' => ['Aucun montant à encaisser pour la sélection.'],
            ]);
        }

        return [
            'allocations' => $allocations,
            'amount' => $amount,
        ];
    }

    /**
     * @return Collection<int, FiscalObligation>
     */
    private function loadOpenObligations(EconomicOperator $operator): Collection
    {
        return FiscalObligation::query()
            ->where('operator_id', $operator->id)
            ->whereIn('status', [FiscalObligationStatus::Open, FiscalObligationStatus::Partial])
            ->where('balance_due', '>', 0)
            ->orderBy('due_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  Collection<int, array{obligation: FiscalObligation, amount: float}>  $allocations
     */
    public function apply(MunicipalPayment $payment, Collection $allocations): void
    {
        foreach ($allocations as $item) {
            /** @var FiscalObligation $obligation */
            $obligation = $item['obligation'];
            $amount = $item['amount'];

            MunicipalPaymentAllocation::query()->create([
                'municipal_payment_id' => $payment->id,
                'fiscal_obligation_id' => $obligation->id,
                'amount_allocated' => $amount,
            ]);

            $obligation->amount_paid = round((float) $obligation->amount_paid + $amount, 2);
            $obligation->recalculateBalance();

            if ($obligation->balance_due <= 0) {
                $obligation->status = FiscalObligationStatus::Paid;
            } elseif ((float) $obligation->amount_paid > 0) {
                $obligation->status = FiscalObligationStatus::Partial;
            }

            $obligation->save();
        }
    }
}
