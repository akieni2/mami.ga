<?php

namespace App\Modules\Municipality\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Municipality\Models\FinancialMissionApproval */
class FinancialMissionApprovalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'financial_mission_id' => $this->financial_mission_id,
            'action' => $this->action,
            'comments' => $this->comments,
            'created_at' => $this->created_at?->toIso8601String(),
            'performer' => $this->whenLoaded('performer', fn () => [
                'id' => $this->performer?->id,
                'name' => $this->performer?->name,
            ]),
            'mission' => $this->whenLoaded('mission', fn () => [
                'id' => $this->mission?->id,
                'reference' => $this->mission?->reference,
                'title' => $this->mission?->title,
                'workflow_status' => $this->mission?->workflow_status?->value,
                'workflow_status_label' => $this->mission?->workflow_status?->label(),
            ]),
        ];
    }
}
