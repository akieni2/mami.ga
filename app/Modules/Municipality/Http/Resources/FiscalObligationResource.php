<?php

namespace App\Modules\Municipality\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Municipality\Models\FiscalObligation */
class FiscalObligationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'operator_id' => $this->operator_id,
            'operator' => $this->whenLoaded('operator', fn () => [
                'public_id' => $this->operator?->public_id,
                'commercial_name' => $this->operator?->commercial_name,
            ]),
            'tax_type_id' => $this->tax_type_id,
            'tax_type' => $this->whenLoaded('taxType', fn () => [
                'code' => $this->taxType?->code,
                'name' => $this->taxType?->name,
            ]),
            'tax_rate_id' => $this->tax_rate_id,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'amount_due' => (string) $this->amount_due,
            'amount_paid' => (string) $this->amount_paid,
            'balance_due' => (string) $this->balance_due,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'generated_at' => $this->generated_at?->toIso8601String(),
            'due_date' => $this->due_date?->toDateString(),
        ];
    }
}
