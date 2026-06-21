<?php

namespace App\Modules\Municipality\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Municipality\Models\FinancialMission */
class FinancialMissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'title' => $this->title,
            'agent_id' => $this->agent_id,
            'agent' => $this->whenLoaded('agent', fn () => [
                'id' => $this->agent?->id,
                'name' => $this->agent?->name,
            ]),
            'operational_zone_id' => $this->operational_zone_id,
            'operational_zone' => $this->whenLoaded('operationalZone', fn () => [
                'id' => $this->operationalZone?->id,
                'name' => $this->operationalZone?->name,
            ]),
            'valid_from' => $this->valid_from?->toDateString(),
            'valid_until' => $this->valid_until?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'authorized_at' => $this->authorized_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'notes' => $this->notes,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
            'authorizer' => $this->whenLoaded('authorizer', fn () => [
                'id' => $this->authorizer?->id,
                'name' => $this->authorizer?->name,
            ]),
        ];
    }
}
