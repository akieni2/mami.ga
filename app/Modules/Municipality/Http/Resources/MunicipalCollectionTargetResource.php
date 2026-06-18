<?php

namespace App\Modules\Municipality\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Municipality\Models\MunicipalCollectionTarget */
class MunicipalCollectionTargetResource extends JsonResource
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
                'code' => $this->taxType?->code,
                'name' => $this->taxType?->name,
            ]),
            'fiscal_year' => $this->fiscal_year,
            'target_amount_xaf' => (string) $this->target_amount_xaf,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
