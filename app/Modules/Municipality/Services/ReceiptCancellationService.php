<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\ReceiptStatus;
use App\Modules\Municipality\Models\MunicipalReceipt;
use Illuminate\Validation\ValidationException;

class ReceiptCancellationService
{
    public function __construct(
        private readonly FiscalAuditService $audit,
    ) {}

    public function annul(User $actor, MunicipalReceipt $receipt, string $reason): MunicipalReceipt
    {
        if ($receipt->status !== ReceiptStatus::Valid) {
            throw ValidationException::withMessages([
                'receipt' => ['Cette quittance ne peut plus être annulée.'],
            ]);
        }

        $receipt->update([
            'status' => ReceiptStatus::Annulled,
            'annulled_at' => now(),
            'annulled_by' => $actor->id,
            'annulled_reason' => $reason,
        ]);

        $this->audit->log($actor, $receipt, 'municipal_receipt', 'receipt.annulled', [
            'receipt_number' => $receipt->receipt_number,
            'reason' => $reason,
        ]);

        return $receipt->fresh(['payment.operator', 'annulledBy']);
    }

    public function refund(User $actor, MunicipalReceipt $receipt, string $reason): MunicipalReceipt
    {
        if ($receipt->status !== ReceiptStatus::Valid) {
            throw ValidationException::withMessages([
                'receipt' => ['Cette quittance ne peut plus être remboursée.'],
            ]);
        }

        $receipt->update([
            'status' => ReceiptStatus::Refunded,
            'refunded_at' => now(),
            'refunded_by' => $actor->id,
            'annulled_reason' => $reason,
        ]);

        $this->audit->log($actor, $receipt, 'municipal_receipt', 'receipt.refunded', [
            'receipt_number' => $receipt->receipt_number,
            'reason' => $reason,
        ]);

        return $receipt->fresh(['payment.operator']);
    }
}
