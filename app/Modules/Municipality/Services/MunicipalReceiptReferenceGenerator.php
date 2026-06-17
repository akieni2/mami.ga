<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\MunicipalReceipt;
use Illuminate\Support\Facades\DB;

class MunicipalReceiptReferenceGenerator
{
    public function next(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        return DB::transaction(function () use ($year): string {
            $prefix = sprintf('OWE-RCP-%d-', $year);

            $last = MunicipalReceipt::query()
                ->where('receipt_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('receipt_number');

            $sequence = 1;
            if (is_string($last) && preg_match('/OWE-RCP-\d{4}-(\d+)$/', $last, $matches)) {
                $sequence = (int) $matches[1] + 1;
            }

            return sprintf('OWE-RCP-%d-%06d', $year, $sequence);
        });
    }

    public function buildReceiptQrValue(string $receiptNumber): string
    {
        return 'QR-'.$receiptNumber;
    }
}
