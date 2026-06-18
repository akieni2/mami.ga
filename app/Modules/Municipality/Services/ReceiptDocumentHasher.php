<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\MunicipalReceipt;

class ReceiptDocumentHasher
{
    public function hash(MunicipalReceipt $receipt, MunicipalPayment $payment): string
    {
        $payload = implode('|', [
            $receipt->receipt_number,
            (string) $payment->operator_id,
            number_format((float) $payment->amount, 2, '.', ''),
            (string) ($payment->payment_period ?? ''),
            (string) $payment->id,
            $payment->collected_at?->utc()->toIso8601String() ?? '',
        ]);

        return hash('sha256', $payload);
    }
}
