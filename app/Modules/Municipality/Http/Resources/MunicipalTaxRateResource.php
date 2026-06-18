<?php

namespace App\Modules\Municipality\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Municipality\Models\MunicipalTaxRate */
class MunicipalTaxRateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tax_type_id' => $this->tax_type_id,
            'tax_type' => $this->whenLoaded('taxType', fn () => [
                'id' => $this->taxType?->id,
                'code' => $this->taxType?->code,
                'name' => $this->taxType?->name,
            ]),
            'amount_xaf' => (string) $this->amount_xaf,
            'billing_period' => $this->billing_period->value,
            'billing_period_label' => $this->billing_period->label(),
            'valid_from' => $this->valid_from?->toDateString(),
            'valid_to' => $this->valid_to?->toDateString(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
