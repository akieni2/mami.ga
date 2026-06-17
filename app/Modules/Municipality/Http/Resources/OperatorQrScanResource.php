<?php

namespace App\Modules\Municipality\Http\Resources;

use App\Modules\Municipality\Models\EconomicOperatorQrcode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EconomicOperatorQrcode */
class OperatorQrScanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $operator = $this->operator;

        return [
            'qr' => [
                'display_id' => $operator?->public_id ?? $this->qr_value,
                'display_label' => $this->qr_value,
                'display_label_with_suffix' => app(\App\Modules\Municipality\Services\QRCodeManagement::class)
                    ->buildDisplayLabelWithSuffix($this->resource),
                'scan_token' => $this->qr_uuid,
                'generated_at' => $this->generated_at?->toIso8601String(),
            ],
            'operator' => $operator !== null
                ? (new EconomicOperatorResource($operator))->resolve()
                : null,
            'tax_status' => $operator !== null ? [
                'current' => $operator->current_tax_status->value,
                'label' => $operator->current_tax_status->label(),
                'color' => $operator->current_tax_status->mapColor(),
                'history' => $operator->relationLoaded('taxStatuses')
                    ? $operator->taxStatuses->map(fn ($row) => [
                        'status' => $row->status->value,
                        'label' => $row->status->label(),
                        'effective_from' => $row->effective_from?->toDateString(),
                        'effective_to' => $row->effective_to?->toDateString(),
                    ])->values()->all()
                    : [],
            ] : null,
            'territory' => [
                'quartier' => $operator?->sector?->name,
                'arrondissement' => $operator?->secteur ?? $operator?->arrondissement?->name,
                'operational_zone' => $operator?->operationalZone?->name,
                'economic_zone' => $operator?->economicZone?->name,
            ],
            'field_visits' => $operator !== null && $operator->relationLoaded('fieldVisits')
                ? $operator->fieldVisits->map(fn ($visit) => [
                    'id' => $visit->id,
                    'visit_type' => $visit->visit_type->value,
                    'visit_type_label' => $visit->visit_type->label(),
                    'visit_date' => $visit->visit_date?->toDateString(),
                    'agent_name' => $visit->agent?->name,
                    'notes' => $visit->notes,
                ])->values()->all()
                : [],
        ];
    }
}
