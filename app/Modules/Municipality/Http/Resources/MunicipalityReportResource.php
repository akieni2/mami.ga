<?php

namespace App\Modules\Municipality\Http\Resources;

use App\Modules\Municipality\Models\MunicipalityReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin MunicipalityReport */
class MunicipalityReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $photo = $this->attachments->first();

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'citizen_id' => $this->citizen_id,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'title' => $this->title,
            'description' => $this->description,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address,
            'territory_id' => $this->territory_id,
            'sector_id' => $this->sector_id,
            'sector_name' => $this->sector?->name,
            'operational_zone_id' => $this->operational_zone_id,
            'operational_zone_name' => $this->operationalZone?->name,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->mapColor(),
            'assigned_to' => $this->assigned_to,
            'assignee_name' => $this->assignee?->name,
            'photo_url' => $photo !== null && $photo->disk === 'public'
                ? Storage::disk('public')->url($photo->path)
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
        ];
    }
}
