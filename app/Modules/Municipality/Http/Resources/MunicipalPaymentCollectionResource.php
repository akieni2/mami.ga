<?php

namespace App\Modules\Municipality\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Municipality\Models\MunicipalPayment */
class MunicipalPaymentCollectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'operator_id' => $this->operator_id,
            'operator' => $this->whenLoaded('operator', fn () => [
                'public_id' => $this->operator?->public_id,
                'commercial_name' => $this->operator?->commercial_name,
            ]),
            'amount_xaf' => (string) $this->amount,
            'payment_method' => $this->payment_method->value,
            'status' => $this->status->value,
            'cash_session_id' => $this->cash_session_id,
            'cash_session' => $this->whenLoaded('cashSession', fn () => [
                'reference' => $this->cashSession?->reference,
            ]),
            'collected_at' => $this->collected_at?->toIso8601String(),
            'allocations' => $this->whenLoaded('allocations', fn () => $this->allocations->map(fn ($a) => [
                'obligation_id' => $a->fiscal_obligation_id,
                'amount_allocated' => (string) $a->amount_allocated,
                'tax_code' => $a->fiscalObligation?->taxType?->code,
                'obligation_reference' => $a->fiscalObligation?->reference,
            ])),
            'core_payment_id' => $this->core_payment_id,
            'receipt' => $this->whenLoaded('receipt', fn () => new MunicipalReceiptResource($this->receipt)),
        ];
    }
}
