<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\FiscalObligationStatus;
use App\Modules\Municipality\Enums\FiscalObligationType;
use App\Modules\Municipality\Enums\VisitType;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\FieldVisit;
use App\Modules\Municipality\Models\FiscalObligation;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\OperatorTaxAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OperatorFiscalSummaryService
{
    public function __construct(
        private readonly FiscalAuditService $audit,
        private readonly FiscalObligationGeneratorService $obligationGenerator,
        private readonly BillingPeriodResolver $periodResolver,
    ) {}

    /**
     * Résumé fiscal historique (rétrocompatibilité Sprint 2).
     *
     * @return array<string, mixed>
     */
    public function build(User $agent, EconomicOperator $operator, ?array $gps = null): array
    {
        $context = $this->prepare($agent, $operator, $gps, recordVisit: true);

        return [
            'operator' => $context['operator'],
            'tax_assignments' => $context['tax_assignments'],
            'obligations' => $context['obligations_legacy'],
            'totals' => $context['totals_legacy'],
        ];
    }

    /**
     * Situation fiscale détaillée Sprint 3.3 — créances par type + historique règlements.
     *
     * @return array<string, mixed>
     */
    public function buildDetailed(User $agent, EconomicOperator $operator, ?array $gps = null): array
    {
        $context = $this->prepare($agent, $operator, $gps, recordVisit: true);

        return [
            'operator' => $context['operator'],
            'taxes' => $context['taxes'],
            'penalties' => $context['penalties'],
            'fines' => $context['fines'],
            'total_due' => $context['total_due'],
            'total_paid' => $context['total_paid'],
            'remaining_balance' => $context['remaining_balance'],
            'payment_history' => $context['payment_history'],
        ];
    }

    public function recordScan(User $agent, EconomicOperator $operator, ?array $gps = null): void
    {
        FieldVisit::query()->create([
            'operator_id' => $operator->id,
            'agent_id' => $agent->id,
            'visit_type' => VisitType::Scan,
            'visit_date' => now()->toDateString(),
            'latitude' => $gps['latitude'] ?? null,
            'longitude' => $gps['longitude'] ?? null,
        ]);

        $this->audit->log($agent, $operator, 'economic_operator', 'fiscal.scan', [
            'operator_public_id' => $operator->public_id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepare(
        User $agent,
        EconomicOperator $operator,
        ?array $gps,
        bool $recordVisit,
    ): array {
        if (! $operator->is_active || $operator->trashed()) {
            throw ValidationException::withMessages([
                'operator_id' => ['Ce commerce est inactif ou archivé.'],
            ]);
        }

        $operator->load(['category', 'sector', 'economicZone']);

        $taxAssignments = OperatorTaxAssignment::query()
            ->with(['taxType.activeRate'])
            ->where('operator_id', $operator->id)
            ->where('is_active', true)
            ->get();

        if ($taxAssignments->isNotEmpty()) {
            $this->obligationGenerator->ensureForOperator($operator, $agent);
        }

        $obligations = FiscalObligation::query()
            ->with(['taxType', 'taxRate'])
            ->where('operator_id', $operator->id)
            ->whereIn('status', [FiscalObligationStatus::Open, FiscalObligationStatus::Partial])
            ->where('balance_due', '>', 0)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        $remainingBalance = round((float) $obligations->sum('balance_due'), 2);
        $totalDue = round((float) $obligations->sum('amount_due'), 2);
        $totalPaid = round((float) FiscalObligation::query()
            ->where('operator_id', $operator->id)
            ->sum('amount_paid'), 2);

        if ($recordVisit) {
            FieldVisit::query()->create([
                'operator_id' => $operator->id,
                'agent_id' => $agent->id,
                'visit_type' => VisitType::Consultation,
                'visit_date' => now()->toDateString(),
                'latitude' => $gps['latitude'] ?? null,
                'longitude' => $gps['longitude'] ?? null,
                'notes' => 'Consultation fiscale',
            ]);

            $this->audit->log($agent, $operator, 'economic_operator', 'fiscal.consultation', [
                'operator_public_id' => $operator->public_id,
                'balance_due' => $remainingBalance,
            ]);
        }

        $operatorPayload = [
            'id' => $operator->id,
            'public_id' => $operator->public_id,
            'commercial_name' => $operator->commercial_name,
            'activity_label' => $operator->activity_label,
            'category' => $operator->category?->name,
            'quartier' => $operator->sector?->name ?? $operator->secteur,
            'economic_zone' => $operator->economicZone?->name,
        ];

        $taxAssignmentsPayload = $taxAssignments->map(fn ($a) => [
            'tax_type_id' => $a->tax_type_id,
            'code' => $a->taxType?->code,
            'name' => $a->taxType?->name,
            'billing_period' => $a->taxType?->activeRate?->billing_period?->value,
            'current_rate_xaf' => $a->taxType?->activeRate?->amount_xaf,
        ])->values()->all();

        $obligationsLegacy = $obligations->map(fn ($o) => $this->mapObligationLegacy($o))->values()->all();

        $grouped = $this->groupReceivablesByType($obligations);

        return [
            'operator' => $operatorPayload,
            'tax_assignments' => $taxAssignmentsPayload,
            'obligations_legacy' => $obligationsLegacy,
            'totals_legacy' => [
                'amount_due' => (string) $remainingBalance,
                'amount_paid' => (string) $totalPaid,
                'balance_remaining' => (string) $remainingBalance,
            ],
            'taxes' => $grouped['taxes'],
            'penalties' => $grouped['penalties'],
            'fines' => $grouped['fines'],
            'total_due' => (string) $totalDue,
            'total_paid' => (string) $totalPaid,
            'remaining_balance' => (string) $remainingBalance,
            'payment_history' => $this->paymentHistory($operator),
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function groupReceivablesByType(Collection $obligations): array
    {
        $taxes = [];
        $penalties = [];
        $fines = [];

        foreach ($obligations as $obligation) {
            $item = $this->mapReceivable($obligation);

            match ($obligation->obligation_type) {
                FiscalObligationType::Penalty => $penalties[] = $item,
                FiscalObligationType::Fine => $fines[] = $item,
                default => $taxes[] = $item,
            };
        }

        return compact('taxes', 'penalties', 'fines');
    }

    /**
     * @return array<string, mixed>
     */
    private function mapReceivable(FiscalObligation $obligation): array
    {
        return [
            'id' => $obligation->id,
            'reference' => $obligation->reference,
            'label' => $this->receivableLabel($obligation),
            'tax_code' => $obligation->taxType?->code,
            'tax_name' => $obligation->taxType?->name,
            'period_label' => $this->periodLabel($obligation),
            'amount_due' => (string) $obligation->amount_due,
            'amount_paid' => (string) $obligation->amount_paid,
            'balance_due' => (string) $obligation->balance_due,
            'status' => $obligation->status->value,
            'due_date' => $obligation->due_date?->toDateString(),
            'obligation_type' => $obligation->obligation_type->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapObligationLegacy(FiscalObligation $obligation): array
    {
        return [
            'id' => $obligation->id,
            'reference' => $obligation->reference,
            'tax_code' => $obligation->taxType?->code,
            'tax_name' => $obligation->taxType?->name,
            'period_start' => $obligation->period_start?->toDateString(),
            'period_end' => $obligation->period_end?->toDateString(),
            'period_label' => $this->periodLabel($obligation),
            'amount_due' => (string) $obligation->amount_due,
            'amount_paid' => (string) $obligation->amount_paid,
            'balance_due' => (string) $obligation->balance_due,
            'status' => $obligation->status->value,
            'due_date' => $obligation->due_date?->toDateString(),
            'obligation_type' => $obligation->obligation_type->value,
        ];
    }

    private function receivableLabel(FiscalObligation $obligation): string
    {
        $typeLabel = $obligation->obligation_type->label();
        $taxName = $obligation->taxType?->name ?? 'Créance';
        $period = $this->periodLabel($obligation);

        return trim("{$typeLabel} — {$taxName}".($period !== '' ? " ({$period})" : ''));
    }

    private function periodLabel(FiscalObligation $obligation): string
    {
        if ($obligation->taxRate?->billing_period === null) {
            return $obligation->period_start?->format('m/Y') ?? '';
        }

        $period = $this->periodResolver->currentPeriod(
            $obligation->taxRate->billing_period,
            Carbon::parse($obligation->period_start),
        );

        return $period['label'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paymentHistory(EconomicOperator $operator): array
    {
        return MunicipalPayment::query()
            ->with(['agent', 'cashSession', 'receipt'])
            ->where('operator_id', $operator->id)
            ->orderByDesc('collected_at')
            ->limit(20)
            ->get()
            ->map(fn (MunicipalPayment $payment) => [
                'id' => $payment->id,
                'collected_at' => $payment->collected_at?->toIso8601String(),
                'amount_xaf' => (string) $payment->amount,
                'agent_name' => $payment->agent?->name,
                'cash_session_reference' => $payment->cashSession?->reference,
                'receipt_number' => $payment->receipt?->receipt_number,
                'status' => $payment->status->value,
            ])
            ->values()
            ->all();
    }
}
