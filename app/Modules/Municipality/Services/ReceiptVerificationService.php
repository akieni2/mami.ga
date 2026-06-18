<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Enums\ReceiptStatus;
use App\Modules\Municipality\Models\MunicipalReceipt;
use Illuminate\Support\Str;

class ReceiptVerificationService
{
    public function __construct(
        private readonly ReceiptVerificationUrlBuilder $urlBuilder,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function verify(string $token): array
    {
        $receipt = MunicipalReceipt::query()
            ->with([
                'payment.operator.sector',
                'payment.agent',
                'payment.allocations.fiscalObligation.taxType',
            ])
            ->where('verification_token', $token)
            ->first();

        if ($receipt === null) {
            return [
                'valid' => false,
                'status' => 'not_found',
                'message' => 'Quittance introuvable.',
            ];
        }

        return [
            'valid' => $receipt->status === ReceiptStatus::Valid,
            'status' => $receipt->status->value,
            'status_label' => $receipt->status->label(),
            'receipt_number' => $receipt->receipt_number,
            'issued_at' => $receipt->generated_at?->toIso8601String(),
            'amount_xaf' => (string) $receipt->payment?->amount,
            'operator' => [
                'public_id' => $receipt->payment?->operator?->public_id,
                'commercial_name' => $receipt->payment?->operator?->commercial_name,
                'quartier' => $receipt->payment?->operator?->sector?->name
                    ?? $receipt->payment?->operator?->secteur,
            ],
            'document_hash' => $receipt->document_hash,
            'verification_url' => $this->urlBuilder->build($receipt->verification_token),
            'annulled_at' => $receipt->annulled_at?->toIso8601String(),
            'annulled_reason' => $receipt->annulled_reason,
            'refunded_at' => $receipt->refunded_at?->toIso8601String(),
        ];
    }

    public function buildToken(): string
    {
        return (string) Str::uuid();
    }
}
