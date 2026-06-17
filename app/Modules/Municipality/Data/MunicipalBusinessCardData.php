<?php

namespace App\Modules\Municipality\Data;

/**
 * Données préparatoires pour la carte professionnelle municipale (V3).
 */
class MunicipalBusinessCardData
{
    public function __construct(
        public readonly string $publicId,
        public readonly string $commercialName,
        public readonly string $activityLabel,
        public readonly int $exerciseYear,
        public readonly string $qrValue,
        public readonly ?string $qrUuid,
        public readonly ?string $displayLabelWithSuffix,
        public readonly string $territoryName,
        public readonly ?string $quartier,
        public readonly ?string $economicZone,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'commercial_name' => $this->commercialName,
            'activity_label' => $this->activityLabel,
            'exercise_year' => $this->exerciseYear,
            'display_id' => $this->publicId,
            'display_label' => $this->qrValue,
            'display_label_with_suffix' => $this->displayLabelWithSuffix,
            'scan_token' => $this->qrUuid,
            'territory_name' => $this->territoryName,
            'quartier' => $this->quartier,
            'economic_zone' => $this->economicZone,
        ];
    }
}
