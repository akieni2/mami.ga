<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Models\MunicipalReceipt;

class ReceiptReprintService
{
    public function __construct(
        private readonly MunicipalReceiptPdfService $pdfService,
        private readonly FiscalAuditService $audit,
    ) {}

    public function reprint(User $agent, MunicipalReceipt $receipt): MunicipalReceipt
    {
        $receipt->increment('reprint_count');

        $this->pdfService->generateAllFormats($agent, $receipt, incrementVersion: true);

        $this->audit->log($agent, $receipt, 'municipal_receipt', 'receipt.reprinted', [
            'receipt_number' => $receipt->receipt_number,
            'reprint_count' => $receipt->fresh()->reprint_count,
        ]);

        return $receipt->fresh(['documents', 'payment.operator', 'payment.agent']);
    }
}
