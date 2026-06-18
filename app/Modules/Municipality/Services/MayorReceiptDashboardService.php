<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Enums\CashSessionStatus;
use App\Modules\Municipality\Enums\PaymentStatus;
use App\Modules\Municipality\Enums\ReceiptStatus;
use App\Modules\Municipality\Models\CashSession;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\MunicipalPaymentAllocation;
use App\Modules\Municipality\Models\MunicipalReceipt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MayorReceiptDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function build(?string $date = null): array
    {
        $date ??= now()->toDateString();

        $issuedToday = MunicipalReceipt::query()->whereDate('generated_at', $date)->count();
        $annulledToday = MunicipalReceipt::query()
            ->where('status', ReceiptStatus::Annulled)
            ->whereDate('annulled_at', $date)
            ->count();

        $collectedToday = (float) MunicipalPayment::query()
            ->whereDate('collected_at', $date)
            ->where('status', PaymentStatus::Completed)
            ->sum('amount');

        $byQuartier = MunicipalPayment::query()
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

        $byAgent = MunicipalPayment::query()
            ->selectRaw('agent_id, SUM(amount) as total, COUNT(*) as count')
            ->whereDate('collected_at', $date)
            ->where('status', PaymentStatus::Completed)
            ->groupBy('agent_id')
            ->with('agent:id,name')
            ->get();

        $byTax = MunicipalPaymentAllocation::query()
            ->join('municipal_payments', 'municipal_payments.id', '=', 'municipal_payment_allocations.municipal_payment_id')
            ->join('fiscal_obligations', 'fiscal_obligations.id', '=', 'municipal_payment_allocations.fiscal_obligation_id')
            ->join('municipal_tax_types', 'municipal_tax_types.id', '=', 'fiscal_obligations.tax_type_id')
            ->whereDate('municipal_payments.collected_at', $date)
            ->where('municipal_payments.status', PaymentStatus::Completed->value)
            ->selectRaw('municipal_tax_types.code as tax_code, municipal_tax_types.name as tax_name, SUM(municipal_payment_allocations.amount_allocated) as total, COUNT(*) as count')
            ->groupBy('municipal_tax_types.id', 'municipal_tax_types.code', 'municipal_tax_types.name')
            ->orderByDesc('total')
            ->get();

        return [
            'date' => $date,
            'receipts_issued' => $issuedToday,
            'receipts_annulled' => $annulledToday,
            'collected_today_xaf' => $collectedToday,
            'by_quartier' => $byQuartier,
            'by_agent' => $byAgent,
            'by_tax' => $byTax,
        ];
    }
}
