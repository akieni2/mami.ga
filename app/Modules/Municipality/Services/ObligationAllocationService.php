<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Enums\FiscalObligationStatus;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\FiscalObligation;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\MunicipalPaymentAllocation;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ObligationAllocationService
{
    /**
     * @return Collection<int, array{obligation: FiscalObligation, amount: float}>
     */
    public function allocate(EconomicOperator $operator, float $amount): Collection
    {
        $obligations = FiscalObligation::query()
            ->where('operator_id', $operator->id)
            ->whereIn('status', [FiscalObligationStatus::Open, FiscalObligationStatus::Partial])
            ->where('balance_due', '>', 0)
            ->orderBy('due_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

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
