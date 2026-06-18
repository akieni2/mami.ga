<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\MunicipalReceipt;

class MunicipalReceiptEmissionService
{
    public function __construct(
        private readonly MunicipalReceiptReferenceGenerator $referenceGenerator,
        private readonly FiscalAuditService $audit,
    ) {}

    public function emit(User $agent, MunicipalPayment $payment): MunicipalReceipt
    {
        $existing = MunicipalReceipt::query()->where('payment_id', $payment->id)->first();
        if ($existing !== null) {
            return $existing;
        }

        $receiptNumber = $this->referenceGenerator->next();
        $receipt = MunicipalReceipt::query()->create([
            'payment_id' => $payment->id,
            'receipt_number' => $receiptNumber,
            'receipt_qr_value' => $this->referenceGenerator->buildReceiptQrValue($receiptNumber),
            'generated_at' => now(),
        ]);

        $this->audit->log($agent, $receipt, 'municipal_receipt', 'receipt.issued', [
            'receipt_number' => $receiptNumber,
            'payment_id' => $payment->id,
        ]);

        return $receipt;
    }
}
