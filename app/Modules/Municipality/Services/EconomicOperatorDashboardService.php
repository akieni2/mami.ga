<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorQrcode;
use App\Modules\Municipality\Models\FieldVisit;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\MunicipalReceipt;
use App\Modules\Municipality\Models\MunicipalSector;
use Illuminate\Support\Facades\DB;

class EconomicOperatorDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function kpis(): array
    {
        $today = now()->toDateString();
        $totalQuartiers = DB::table('municipal_sectors')
            ->where('sector_type', 'quartier')
            ->count();

        $quartiersWithOperators = EconomicOperator::query()
            ->whereNotNull('sector_id')
            ->distinct('sector_id')
            ->count('sector_id');

        $byAgent = EconomicOperator::query()
            ->whereNotNull('registered_by')
            ->selectRaw('registered_by, COUNT(*) as total')
            ->groupBy('registered_by')
            ->get();

        $agentNames = User::query()
            ->whereIn('id', $byAgent->pluck('registered_by'))
            ->pluck('name', 'id');

        $byQuartier = EconomicOperator::query()
            ->whereNotNull('sector_id')
            ->selectRaw('sector_id, COUNT(*) as total')
            ->groupBy('sector_id')
            ->get();

        $quartierNames = MunicipalSector::query()
            ->whereIn('id', $byQuartier->pluck('sector_id'))
            ->pluck('name', 'id');

        return [
            'registered_today' => EconomicOperator::query()
                ->whereDate('created_at', $today)
                ->count(),
            'registered_by_agent' => $byAgent
                ->map(fn ($row) => [
                    'agent_id' => $row->registered_by,
                    'agent_name' => $agentNames[$row->registered_by] ?? null,
                    'total' => (int) $row->total,
                ])
                ->values()
                ->all(),
            'registered_by_quartier' => $byQuartier
                ->map(fn ($row) => [
                    'sector_id' => $row->sector_id,
                    'quartier_name' => $quartierNames[$row->sector_id] ?? null,
                    'total' => (int) $row->total,
                ])
                ->values()
                ->all(),
            'coverage' => [
                'quartiers_total' => $totalQuartiers,
                'quartiers_with_operators' => $quartiersWithOperators,
                'coverage_percent' => $totalQuartiers > 0
                    ? round(($quartiersWithOperators / $totalQuartiers) * 100, 1)
                    : 0.0,
            ],
            'total_operators' => EconomicOperator::query()->count(),
            'v3_preparatory' => [
                'qr_codes_generated' => EconomicOperatorQrcode::query()->count(),
                'field_visits_total' => FieldVisit::query()->count(),
                'amounts_collected' => [
                    'value' => (float) MunicipalPayment::query()
                        ->where('status', 'completed')
                        ->sum('amount'),
                    'placeholder' => true,
                    'note' => 'Calcul financier complet prévu en V3.',
                ],
                'receipts_today' => [
                    'value' => MunicipalReceipt::query()
                        ->whereDate('generated_at', $today)
                        ->count(),
                    'placeholder' => true,
                    'note' => 'Émission de quittances prévue en V3.',
                ],
            ],
        ];
    }
}
