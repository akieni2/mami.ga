<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\TreasuryRemittanceStatus;
use App\Modules\Municipality\Models\MunicipalTreasuryRemittance;
use Illuminate\Support\Str;

class TreasuryRemittanceService
{
    public function __construct(
        private readonly MunicipalFinanceJournalService $journal,
        private readonly FiscalAuditService $audit,
    ) {}

    /**
     * Préparation module Reversement Trésor Public — création brouillon.
     *
     * @param  array<string, mixed>  $data
     */
    public function createDraft(User $preparer, array $data): MunicipalTreasuryRemittance
    {
        $remittance = MunicipalTreasuryRemittance::query()->create([
            'reference' => $this->nextReference(),
            'amount_xaf' => $data['amount_xaf'],
            'status' => TreasuryRemittanceStatus::Draft,
            'prepared_by' => $preparer->id,
            'period_start' => $data['period_start'] ?? null,
            'period_end' => $data['period_end'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->journal->record('remittance.draft_created', $remittance, $preparer, null, null, [
            'reference' => $remittance->reference,
            'amount_xaf' => (string) $remittance->amount_xaf,
        ]);

        $this->audit->log($preparer, $remittance, 'treasury_remittance', 'treasury_remittance.draft_created');

        return $remittance->fresh(['preparer']);
    }

    /**
     * @return list<MunicipalTreasuryRemittance>
     */
    public function listRecent(int $limit = 30): array
    {
        return MunicipalTreasuryRemittance::query()
            ->with(['preparer:id,name', 'validator:id,name'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->all();
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
