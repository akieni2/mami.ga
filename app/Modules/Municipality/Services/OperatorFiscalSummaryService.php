<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\FiscalObligationStatus;
use App\Modules\Municipality\Enums\VisitType;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\FieldVisit;
use App\Modules\Municipality\Models\FiscalObligation;
use App\Modules\Municipality\Models\OperatorTaxAssignment;
use Illuminate\Validation\ValidationException;

class OperatorFiscalSummaryService
{
    public function __construct(
        private readonly FiscalAuditService $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $agent, EconomicOperator $operator, ?array $gps = null): array
    {
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

        $obligations = FiscalObligation::query()
            ->with(['taxType', 'taxRate'])
            ->where('operator_id', $operator->id)
            ->whereIn('status', [FiscalObligationStatus::Open, FiscalObligationStatus::Partial])
            ->orderBy('due_date')
            ->get();

        $amountDue = round((float) $obligations->sum('balance_due'), 2);
        $amountPaidTotal = round((float) FiscalObligation::query()
            ->where('operator_id', $operator->id)
            ->sum('amount_paid'), 2);

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
            'balance_due' => $amountDue,
        ]);

        return [
            'operator' => [
                'id' => $operator->id,
                'public_id' => $operator->public_id,
                'commercial_name' => $operator->commercial_name,
                'activity_label' => $operator->activity_label,
                'category' => $operator->category?->name,
                'quartier' => $operator->sector?->name ?? $operator->secteur,
                'economic_zone' => $operator->economicZone?->name,
            ],
            'tax_assignments' => $taxAssignments->map(fn ($a) => [
                'tax_type_id' => $a->tax_type_id,
                'code' => $a->taxType?->code,
                'name' => $a->taxType?->name,
                'billing_period' => $a->taxType?->activeRate?->billing_period?->value,
                'current_rate_xaf' => $a->taxType?->activeRate?->amount_xaf,
            ])->values()->all(),
            'obligations' => $obligations->map(fn ($o) => [
                'id' => $o->id,
                'reference' => $o->reference,
                'tax_code' => $o->taxType?->code,
                'tax_name' => $o->taxType?->name,
                'period_start' => $o->period_start?->toDateString(),
                'period_end' => $o->period_end?->toDateString(),
                'amount_due' => (string) $o->amount_due,
                'amount_paid' => (string) $o->amount_paid,
                'balance_due' => (string) $o->balance_due,
                'status' => $o->status->value,
                'due_date' => $o->due_date?->toDateString(),
            ])->values()->all(),
            'totals' => [
                'amount_due' => (string) $amountDue,
                'amount_paid' => (string) $amountPaidTotal,
                'balance_remaining' => (string) $amountDue,
            ],
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
}
