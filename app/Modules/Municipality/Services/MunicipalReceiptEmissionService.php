<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\ReceiptStatus;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\MunicipalReceipt;

class MunicipalReceiptEmissionService
{
    public function __construct(
        private readonly MunicipalReceiptReferenceGenerator $referenceGenerator,
        private readonly ReceiptDocumentHasher $documentHasher,
        private readonly ReceiptVerificationService $verificationService,
        private readonly ReceiptVerificationUrlBuilder $urlBuilder,
        private readonly MunicipalReceiptPdfService $pdfService,
        private readonly FiscalAuditService $audit,
    ) {}

    public function emit(User $agent, MunicipalPayment $payment): MunicipalReceipt
    {
        $existing = MunicipalReceipt::query()->where('payment_id', $payment->id)->first();
        if ($existing !== null) {
            return $existing->load(['documents', 'payment.operator']);
        }

        $payment->loadMissing(['operator.sector', 'agent', 'allocations.fiscalObligation.taxType']);

        $receiptNumber = $this->referenceGenerator->next();
        $verificationToken = $this->verificationService->buildToken();

        $receipt = MunicipalReceipt::query()->make([
            'payment_id' => $payment->id,
            'receipt_number' => $receiptNumber,
            'verification_token' => $verificationToken,
            'generated_at' => now(),
            'status' => ReceiptStatus::Valid,
        ]);

        $receipt->document_hash = $this->documentHasher->hash($receipt, $payment);
        $receipt->signed_at = now();
        $receipt->receipt_qr_value = $this->urlBuilder->build($verificationToken);
        $receipt->save();

        $this->pdfService->generateAllFormats($agent, $receipt);

        $this->audit->log($agent, $receipt, 'municipal_receipt', 'receipt.issued', [
            'receipt_number' => $receiptNumber,
            'payment_id' => $payment->id,
            'document_hash' => $receipt->document_hash,
        ]);

        return $receipt->fresh(['documents', 'payment.operator', 'payment.agent', 'payment.allocations.fiscalObligation.taxType']);
    }
}
