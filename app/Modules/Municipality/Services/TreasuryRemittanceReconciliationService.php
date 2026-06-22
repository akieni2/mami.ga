<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Enums\PaymentStatus;
use App\Modules\Municipality\Enums\TreasuryRemittanceStatus;
use App\Modules\Municipality\Models\CashSession;
use App\Modules\Municipality\Models\MunicipalFinanceJournalEntry;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\MunicipalTreasuryRemittance;
use App\Modules\Municipality\Models\MunicipalTreasuryRemittancePayment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TreasuryRemittanceReconciliationService
{
    /**
     * @return Collection<int, MunicipalPayment>
     */
    public function eligiblePayments(
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        ?int $exceptRemittanceId = null,
    ): Collection {
        $allocatedIds = $this->allocatedPaymentIds($exceptRemittanceId);

        return MunicipalPayment::query()
            ->with(['cashSession:id,reference'])
            ->where('status', PaymentStatus::Completed)
            ->whereDate('collected_at', '>=', $periodStart->toDateString())
            ->whereDate('collected_at', '<=', $periodEnd->toDateString())
            ->when($allocatedIds !== [], fn ($query) => $query->whereNotIn('id', $allocatedIds))
            ->orderBy('collected_at')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReconciliationSummary(
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
        ?int $exceptRemittanceId = null,
    ): array {
        $payments = $this->eligiblePayments($periodStart, $periodEnd, $exceptRemittanceId);
        $paymentTotal = (string) $payments->sum('amount');
        $sessionIds = $payments->pluck('cash_session_id')->filter()->unique()->values();

        $closedSessions = CashSession::query()
            ->whereIn('id', $sessionIds)
            ->whereNotNull('closed_at')
            ->get(['id', 'reference', 'expected_amount_xaf', 'closed_at']);

        $journalEvents = MunicipalFinanceJournalEntry::query()
            ->whereIn('event_type', ['cash_session.closed', 'cash_session.admin_closed'])
            ->whereIn('cash_session_id', $sessionIds)
            ->whereDate('occurred_at', '>=', $periodStart->toDateString())
            ->whereDate('occurred_at', '<=', $periodEnd->toDateString())
            ->count();

        return [
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'payment_count' => $payments->count(),
            'payment_total_xaf' => $paymentTotal,
            'cash_session_count' => $sessionIds->count(),
            'closed_sessions_count' => $closedSessions->count(),
            'journal_closure_events_count' => $journalEvents,
            'payments' => $payments->map(fn (MunicipalPayment $payment) => [
                'id' => $payment->id,
                'amount_xaf' => (string) $payment->amount,
                'collected_at' => $payment->collected_at?->toIso8601String(),
                'cash_session_id' => $payment->cash_session_id,
                'cash_session_reference' => $payment->cashSession?->reference,
            ])->values()->all(),
        ];
    }

    /**
     * @param  list<int>  $paymentIds
     */
    public function syncAllocations(MunicipalTreasuryRemittance $remittance, array $paymentIds): MunicipalTreasuryRemittance
    {
        if ($remittance->status !== TreasuryRemittanceStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => ['Seuls les brouillons peuvent être réconciliés.'],
            ]);
        }

        if ($paymentIds === []) {
            throw ValidationException::withMessages([
                'payment_ids' => ['Au moins un encaissement est requis pour la réconciliation.'],
            ]);
        }

        $periodStart = $remittance->period_start;
        $periodEnd = $remittance->period_end;

        if ($periodStart === null || $periodEnd === null) {
            throw ValidationException::withMessages([
                'period' => ['La période du reversement doit être définie.'],
            ]);
        }

        $payments = MunicipalPayment::query()
            ->whereIn('id', $paymentIds)
            ->where('status', PaymentStatus::Completed)
            ->get();

        if ($payments->count() !== count(array_unique($paymentIds))) {
            throw ValidationException::withMessages([
                'payment_ids' => ['Un ou plusieurs encaissements sont invalides.'],
            ]);
        }

        $allocatedElsewhere = MunicipalTreasuryRemittancePayment::query()
            ->whereIn('municipal_payment_id', $paymentIds)
            ->when($remittance->exists, fn ($query) => $query->where('remittance_id', '!=', $remittance->id))
            ->whereHas('remittance', fn ($query) => $query->where('status', '!=', TreasuryRemittanceStatus::Cancelled))
            ->exists();

        if ($allocatedElsewhere) {
            throw ValidationException::withMessages([
                'payment_ids' => ['Un ou plusieurs encaissements sont déjà affectés à un autre reversement.'],
            ]);
        }

        foreach ($payments as $payment) {
            $collectedDate = $payment->collected_at?->toDateString();
            if ($collectedDate === null
                || $collectedDate < $periodStart->toDateString()
                || $collectedDate > $periodEnd->toDateString()) {
                throw ValidationException::withMessages([
                    'payment_ids' => ['Les encaissements doivent être dans la période du reversement.'],
                ]);
            }
        }

        return DB::transaction(function () use ($remittance, $payments): MunicipalTreasuryRemittance {
            MunicipalTreasuryRemittancePayment::query()
                ->where('remittance_id', $remittance->id)
                ->delete();

            $sessionIds = collect();

            foreach ($payments as $payment) {
                MunicipalTreasuryRemittancePayment::query()->create([
                    'remittance_id' => $remittance->id,
                    'municipal_payment_id' => $payment->id,
                    'cash_session_id' => $payment->cash_session_id,
                    'amount_allocated' => $payment->amount,
                    'created_at' => now(),
                ]);

                if ($payment->cash_session_id) {
                    $sessionIds->push($payment->cash_session_id);
                }
            }

            $total = (string) $payments->sum('amount');

            $remittance->update([
                'amount_xaf' => $total,
                'reconciled_amount_xaf' => $total,
                'payment_count' => $payments->count(),
                'cash_session_count' => $sessionIds->unique()->count(),
            ]);

            return $remittance->fresh([
                'preparer:id,name',
                'paymentAllocations.payment',
                'paymentAllocations.cashSession',
            ]);
        });
    }

    public function assertAmountMatchesAllocations(MunicipalTreasuryRemittance $remittance): void
    {
        $allocated = (string) MunicipalTreasuryRemittancePayment::query()
            ->where('remittance_id', $remittance->id)
            ->sum('amount_allocated');

        if (bccomp((string) $remittance->amount_xaf, $allocated, 2) !== 0) {
            throw ValidationException::withMessages([
                'amount_xaf' => ['Le montant du reversement ne correspond pas à la somme des encaissements liés.'],
            ]);
        }
    }

    /**
     * @return list<int>
     */
    private function allocatedPaymentIds(?int $exceptRemittanceId = null): array
    {
        $query = MunicipalTreasuryRemittancePayment::query()
            ->whereHas('remittance', fn ($builder) => $builder->where('status', '!=', TreasuryRemittanceStatus::Cancelled));

        if ($exceptRemittanceId !== null) {
            $query->where('remittance_id', '!=', $exceptRemittanceId);
        }

        return $query->pluck('municipal_payment_id')->all();
    }
}
