<?php

namespace App\Modules\Municipality\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Municipality\Models\MunicipalTreasuryRemittance */
class TreasuryRemittanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'amount_xaf' => (string) $this->amount_xaf,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'remitted_at' => $this->remitted_at?->toIso8601String(),
            'notes' => $this->notes,
            'preparer' => $this->whenLoaded('preparer', fn () => [
                'id' => $this->preparer?->id,
                'name' => $this->preparer?->name,
            ]),
        ];
    }
}
