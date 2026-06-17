<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Data\MunicipalBusinessCardData;
use App\Modules\Municipality\Models\EconomicOperator;

class MunicipalBusinessCardService
{
    public function __construct(
        private readonly QRCodeManagement $qrCodeManagement,
    ) {}

    public function buildForOperator(EconomicOperator $operator): MunicipalBusinessCardData
    {
        $operator->loadMissing(['territory', 'sector', 'economicZone', 'activeQrcode']);

        $qrcode = $operator->activeQrcode;
        if ($qrcode === null) {
            $qrcode = $this->qrCodeManagement->generateForOperator($operator);
        }

        $exerciseYear = (int) ($operator->registration_date?->year ?? now()->year);

        return new MunicipalBusinessCardData(
            publicId: $operator->public_id,
            commercialName: $operator->commercial_name,
            activityLabel: $operator->activity_label,
            exerciseYear: $exerciseYear,
            qrValue: $qrcode->qr_value,
            qrUuid: $qrcode->qr_uuid,
            displayLabelWithSuffix: $this->qrCodeManagement->buildDisplayLabelWithSuffix($qrcode),
            territoryName: $operator->territory?->name ?? 'Owendo',
            quartier: $operator->sector?->name,
            economicZone: $operator->economicZone?->name,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(EconomicOperator $operator): array
    {
        $card = $this->buildForOperator($operator);

        return array_merge($card->toArray(), [
            'pdf_ready' => false,
            'print_ready' => false,
            'template' => 'municipal_business_card_v1',
        ]);
    }
}
