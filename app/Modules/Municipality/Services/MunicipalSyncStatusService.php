<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Enums\PaymentStatus;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\MunicipalReceipt;

class MunicipalSyncStatusService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'server_time' => now()->toIso8601String(),
            'api_status' => 'ok',
            'operators_count' => EconomicOperator::query()->count(),
            'payments_count' => MunicipalPayment::query()
                ->where('status', PaymentStatus::Completed)
                ->count(),
            'receipts_count' => MunicipalReceipt::query()->count(),
        ];
    }
}
