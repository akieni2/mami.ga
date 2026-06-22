<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\TreasuryRemittanceAccountingExportStatus;
use App\Modules\Municipality\Enums\TreasuryRemittanceApprovalAction;
use App\Modules\Municipality\Enums\TreasuryRemittanceStatus;
use App\Modules\Municipality\Models\MunicipalTreasuryRemittance;
use App\Modules\Municipality\Models\MunicipalTreasuryRemittanceApproval;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TreasuryRemittanceService
{
    public function __construct(
        private readonly MunicipalFinanceJournalService $journal,
        private readonly FiscalAuditService $audit,
        private readonly TreasuryRemittanceReconciliationService $reconciliation,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(User $preparer, array $data): MunicipalTreasuryRemittance
    {
        $remittance = MunicipalTreasuryRemittance::query()->create([
            'reference' => $this->nextReference(),
            'amount_xaf' => $data['amount_xaf'] ?? 0,
            'status' => TreasuryRemittanceStatus::Draft,
            'prepared_by' => $preparer->id,
            'period_start' => $data['period_start'] ?? null,
            'period_end' => $data['period_end'] ?? null,
            'notes' => $data['notes'] ?? null,
            'accounting_export_status' => TreasuryRemittanceAccountingExportStatus::Pending,
        ]);

        if (! empty($data['payment_ids']) && is_array($data['payment_ids'])) {
            $remittance = $this->reconciliation->syncAllocations($remittance, array_map('intval', $data['payment_ids']));
        }

        $this->recordCreated($preparer, $remittance);

        return $remittance->fresh([
            'preparer:id,name',
            'paymentAllocations.payment',
            'paymentAllocations.cashSession',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(User $actor, MunicipalTreasuryRemittance $remittance, array $data): MunicipalTreasuryRemittance
    {
        if ($remittance->status !== TreasuryRemittanceStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => ['Seuls les brouillons peuvent être modifiés.'],
            ]);
        }

        $remittance->update([
            'period_start' => $data['period_start'] ?? $remittance->period_start,
            'period_end' => $data['period_end'] ?? $remittance->period_end,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $remittance->notes,
        ]);

        if (array_key_exists('payment_ids', $data) && is_array($data['payment_ids'])) {
            $remittance = $this->reconciliation->syncAllocations($remittance, array_map('intval', $data['payment_ids']));
        } elseif (array_key_exists('amount_xaf', $data) && empty($data['payment_ids'])) {
            $remittance->update(['amount_xaf' => $data['amount_xaf']]);
        }

        $this->journal->record('remittance.updated', $remittance, $actor, null, null, [
            'reference' => $remittance->reference,
            'amount_xaf' => (string) $remittance->amount_xaf,
        ]);

        MunicipalTreasuryRemittanceApproval::query()->create([
            'remittance_id' => $remittance->id,
            'action' => TreasuryRemittanceApprovalAction::Updated,
            'performed_by' => $actor->id,
            'created_at' => now(),
        ]);

        return $remittance->fresh([
            'preparer:id,name',
            'paymentAllocations.payment',
            'paymentAllocations.cashSession',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function generateFromPeriod(User $preparer, array $data): MunicipalTreasuryRemittance
    {
        $periodStart = Carbon::parse($data['period_start'])->startOfDay();
        $periodEnd = Carbon::parse($data['period_end'])->endOfDay();

        $summary = $this->reconciliation->buildReconciliationSummary($periodStart, $periodEnd);

        if ($summary['payment_count'] === 0) {
            throw ValidationException::withMessages([
                'period' => ['Aucun encaissement éligible sur cette période.'],
            ]);
        }

        $paymentIds = collect($summary['payments'])->pluck('id')->map(fn ($id) => (int) $id)->all();

        return $this->createDraft($preparer, [
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'payment_ids' => $paymentIds,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * @return list<MunicipalTreasuryRemittance>
     */
    public function listRecent(?string $status = null, int $limit = 50): array
    {
        return MunicipalTreasuryRemittance::query()
            ->with([
                'preparer:id,name',
                'controller:id,name',
                'dafValidator:id,name',
                'receveurValidator:id,name',
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function find(int $id): MunicipalTreasuryRemittance
    {
        return MunicipalTreasuryRemittance::query()
            ->with([
                'preparer:id,name',
                'controller:id,name',
                'dafValidator:id,name',
                'receveurValidator:id,name',
                'depositor:id,name',
                'confirmer:id,name',
                'paymentAllocations.payment.agent:id,name',
                'paymentAllocations.cashSession:id,reference',
                'approvals.performer:id,name',
            ])
            ->findOrFail($id);
    }

    private function recordCreated(User $preparer, MunicipalTreasuryRemittance $remittance): void
    {
        MunicipalTreasuryRemittanceApproval::query()->create([
            'remittance_id' => $remittance->id,
            'action' => TreasuryRemittanceApprovalAction::Created,
            'performed_by' => $preparer->id,
            'created_at' => now(),
        ]);

        $this->journal->record('remittance.created', $remittance, $preparer, null, null, [
            'reference' => $remittance->reference,
            'amount_xaf' => (string) $remittance->amount_xaf,
            'payment_count' => $remittance->payment_count,
        ]);

        $this->audit->log($preparer, $remittance, 'treasury_remittance', 'treasury_remittance.created');
    }

    private function nextReference(): string
    {
        $year = now()->year;
        $prefix = "OWE-RT-{$year}-";

        $last = MunicipalTreasuryRemittance::query()
            ->where('reference', 'like', $prefix.'%')
            ->orderByDesc('reference')
            ->value('reference');

        $sequence = 1;
        if ($last !== null && preg_match('/-(\d{6})$/', $last, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.Str::padLeft((string) $sequence, 6, '0');
    }
}
