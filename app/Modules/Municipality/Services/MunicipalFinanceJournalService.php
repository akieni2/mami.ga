<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Models\CashSession;
use App\Modules\Municipality\Models\FinancialMission;
use App\Modules\Municipality\Models\MunicipalFinanceJournalEntry;
use App\Modules\Municipality\Models\MunicipalTreasuryRemittance;
use Illuminate\Database\Eloquent\Model;

class MunicipalFinanceJournalService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(
        string $eventType,
        Model $subject,
        ?User $actor = null,
        ?FinancialMission $mission = null,
        ?CashSession $cashSession = null,
        array $payload = [],
    ): MunicipalFinanceJournalEntry {
        return MunicipalFinanceJournalEntry::query()->create([
            'event_type' => $eventType,
            'subject_type' => $this->subjectType($subject),
            'subject_id' => $subject->getKey(),
            'financial_mission_id' => $mission?->id ?? ($subject instanceof FinancialMission ? $subject->id : null),
            'cash_session_id' => $cashSession?->id ?? ($subject instanceof CashSession ? $subject->id : null),
            'actor_id' => $actor?->id,
            'payload' => $payload,
            'occurred_at' => now(),
            'created_at' => now(),
        ]);
    }

    private function subjectType(Model $subject): string
    {
        return match ($subject::class) {
            FinancialMission::class => 'financial_mission',
            CashSession::class => 'cash_session',
            MunicipalTreasuryRemittance::class => 'treasury_remittance',
            default => class_basename($subject),
        };
    }
}
