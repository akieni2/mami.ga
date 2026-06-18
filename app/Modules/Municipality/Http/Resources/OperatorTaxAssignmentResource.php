<?php

namespace App\Modules\Municipality\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Municipality\Models\OperatorTaxAssignment */
class OperatorTaxAssignmentResource extends JsonResource
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
                'id' => $this->operator?->id,
                'public_id' => $this->operator?->public_id,
                'commercial_name' => $this->operator?->commercial_name,
            ]),
            'tax_type_id' => $this->tax_type_id,
            'tax_type' => $this->whenLoaded('taxType', fn () => [
                'id' => $this->taxType?->id,
                'code' => $this->taxType?->code,
                'name' => $this->taxType?->name,
            ]),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'assigned_by' => $this->whenLoaded('assignedBy', fn () => [
                'id' => $this->assignedBy?->id,
                'name' => $this->assignedBy?->name,
            ]),
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
