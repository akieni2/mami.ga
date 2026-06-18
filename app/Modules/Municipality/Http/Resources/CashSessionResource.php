<?php

namespace App\Modules\Municipality\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Municipality\Models\CashSession */
class CashSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'agent_id' => $this->agent_id,
            'agent' => $this->whenLoaded('agent', fn () => [
                'id' => $this->agent?->id,
                'name' => $this->agent?->name,
            ]),
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'opening_amount_xaf' => (string) $this->opening_amount_xaf,
            'expected_amount_xaf' => (string) $this->expected_amount_xaf,
            'actual_amount_xaf' => $this->actual_amount_xaf !== null ? (string) $this->actual_amount_xaf : null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'device_id' => $this->device_id,
            'notes' => $this->notes,
        ];
    }
}
