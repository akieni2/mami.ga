<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Enums\CashSessionStatus;
use App\Modules\Municipality\Enums\PaymentStatus;
use App\Modules\Municipality\Models\CashSession;
use App\Modules\Municipality\Models\MunicipalPayment;
use Illuminate\Support\Collection;

class FiscalSupervisorDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function build(?string $date = null): array
    {
        $date ??= now()->toDateString();

        $openSessions = CashSession::query()
            ->with('agent:id,name')
            ->where('status', CashSessionStatus::Open)
            ->orderByDesc('opened_at')
            ->get();

        $collectedToday = (float) MunicipalPayment::query()
            ->whereDate('collected_at', $date)
            ->where('status', PaymentStatus::Completed)
            ->sum('amount');

        $byAgent = $this->collectionsByAgent($date);
        $byDay = $this->collectionsByDay();
        $byQuartier = $this->collectionsByQuartier($date);

        return [
            'open_sessions_count' => $openSessions->count(),
            'open_sessions' => $openSessions,
            'collected_today_xaf' => (string) $collectedToday,
            'collections_by_agent' => $byAgent,
            'collections_by_day' => $byDay,
            'collections_by_quartier' => $byQuartier,
        ];
    }

    /**
     * @return Collection<int, object>
     */
    private function collectionsByAgent(string $date): Collection
    {
        return MunicipalPayment::query()
            ->selectRaw('agent_id, SUM(amount) as total, COUNT(*) as count')
            ->whereDate('collected_at', $date)
            ->where('status', PaymentStatus::Completed)
            ->groupBy('agent_id')
            ->with('agent:id,name')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function collectionsByDay(): Collection
    {
        return MunicipalPayment::query()
            ->selectRaw('DATE(collected_at) as day, SUM(amount) as total, COUNT(*) as count')
            ->where('status', PaymentStatus::Completed)
            ->where('collected_at', '>=', now()->subDays(14))
            ->groupByRaw('DATE(collected_at)')
            ->orderByDesc('day')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function collectionsByQuartier(string $date): Collection
    {
        return MunicipalPayment::query()
            ->join('economic_operators', 'economic_operators.id', '=', 'municipal_payments.operator_id')
            ->leftJoin('municipal_sectors', 'municipal_sectors.id', '=', 'economic_operators.sector_id')
            ->whereDate('municipal_payments.collected_at', $date)
            ->where('municipal_payments.status', PaymentStatus::Completed->value)
            ->selectRaw("
                economic_operators.sector_id,
                COALESCE(municipal_sectors.name, economic_operators.secteur, 'Non renseigné') as quartier,
                SUM(municipal_payments.amount) as total,
                COUNT(*) as count
            ")
            ->groupBy('economic_operators.sector_id', 'municipal_sectors.name', 'economic_operators.secteur')
            ->orderByDesc('total')
            ->get();
    }
}
