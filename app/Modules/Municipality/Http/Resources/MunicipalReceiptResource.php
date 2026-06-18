<?php

namespace App\Modules\Municipality\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Municipality\Models\MunicipalReceipt */
class MunicipalReceiptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payment = $this->payment;

        return [
            'id' => $this->id,
            'receipt_number' => $this->receipt_number,
            'verification_token' => $this->verification_token,
            'verification_url' => $this->receipt_qr_value,
            'document_hash' => $this->document_hash,
            'signed_at' => $this->signed_at?->toIso8601String(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'generated_at' => $this->generated_at?->toIso8601String(),
            'reprint_count' => $this->reprint_count,
            'annulled_at' => $this->annulled_at?->toIso8601String(),
            'annulled_reason' => $this->annulled_reason,
            'amount_xaf' => $payment ? (string) $payment->amount : null,
            'operator' => $this->whenLoaded('payment', fn () => [
                'id' => $payment?->operator_id,
                'public_id' => $payment?->operator?->public_id,
                'commercial_name' => $payment?->operator?->commercial_name,
                'quartier' => $payment?->operator?->sector?->name ?? $payment?->operator?->secteur,
            ]),
            'agent' => $this->whenLoaded('payment', fn () => [
                'id' => $payment?->agent_id,
                'name' => $payment?->agent?->name,
            ]),
            'tax_lines' => $this->when(
                $payment?->relationLoaded('allocations'),
                fn () => $payment->allocations->map(fn ($a) => [
                    'tax_code' => $a->fiscalObligation?->taxType?->code,
                    'tax_name' => $a->fiscalObligation?->taxType?->name,
                    'period' => $a->fiscalObligation?->period_start?->format('Y-m'),
                    'amount_allocated' => (string) $a->amount_allocated,
                ]),
            ),
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($d) => [
                'format' => $d->format->value,
                'version' => $d->version,
                'generated_at' => $d->generated_at?->toIso8601String(),
            ])),
            'print_payload' => [
                'commune' => "Commune d'Owendo",
                'receipt_number' => $this->receipt_number,
                'commercial_name' => $payment?->operator?->commercial_name,
                'public_id' => $payment?->operator?->public_id,
                'amount_xaf' => $payment ? (string) $payment->amount : null,
                'issued_at' => $this->generated_at?->toIso8601String(),
                'agent_name' => $payment?->agent?->name,
                'verification_url' => $this->receipt_qr_value,
                'document_hash_short' => substr((string) $this->document_hash, 0, 16),
            ],
        ];
    }
}
