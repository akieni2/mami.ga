<?php

namespace App\Modules\Municipality\Http\Resources;

use App\Modules\Municipality\Models\EconomicOperator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin EconomicOperator */
class EconomicOperatorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $photos = $this->attachments
            ->mapWithKeys(fn ($attachment) => [
                $attachment->purpose => $attachment->disk === 'public'
                    ? Storage::disk('public')->url($attachment->path)
                    : null,
            ]);

        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'commercial_name' => $this->commercial_name,
            'activity_label' => $this->activity_label,
            'category_id' => $this->category_id,
            'category' => $this->category?->slug,
            'category_label' => $this->category?->name,
            'responsible_name' => $this->responsible_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'gps_accuracy_m' => $this->gps_accuracy_m,
            'gps_captured_at' => $this->gps_captured_at?->toIso8601String(),
            'sync_status' => $this->sync_status->value,
            'sync_status_label' => $this->sync_status->label(),
            'territory_id' => $this->territory_id,
            'sector_id' => $this->sector_id,
            'quartier' => $this->sector?->name,
            'operational_zone_id' => $this->operational_zone_id,
            'operational_zone' => $this->operationalZone?->name,
            'economic_zone_id' => $this->economic_zone_id,
            'economic_zone' => $this->economicZone?->name,
            'arrondissement' => $this->secteur ?? $this->arrondissement?->name,
            'tax_status' => $this->current_tax_status->value,
            'tax_status_label' => $this->current_tax_status->label(),
            'tax_status_color' => $this->current_tax_status->mapColor(),
            'registered_by' => $this->registered_by,
            'registered_by_name' => $this->registeredBy?->name,
            'last_modified_by' => $this->last_modified_by,
            'last_visit_at' => $this->last_visit_at?->toIso8601String(),
            'registration_date' => $this->registration_date?->toDateString(),
            'photos' => $photos,
            'qr_code' => $this->when(
                $this->relationLoaded('activeQrcode') && $this->activeQrcode !== null,
                function () {
                    $qr = $this->activeQrcode;
                    $management = app(\App\Modules\Municipality\Services\QRCodeManagement::class);

                    return [
                        'display_id' => $this->public_id,
                        'display_label' => $qr->qr_value,
                        'display_label_with_suffix' => $management->buildDisplayLabelWithSuffix($qr),
                        'scan_token' => $qr->qr_uuid,
                        'generated_at' => $qr->generated_at?->toIso8601String(),
                        'png_url' => url('/api/municipality/operators/'.$this->id.'/qrcode/png'),
                    ];
                },
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
