<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorQrcode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QRCodeManagement
{
    private const PNG_SIZE = 400;

    private const QUIET_ZONE_MARGIN = 10;

    /**
     * Jeton encodé dans l'image QR (UUID v4 — non devinable).
     */
    public function scanPayload(EconomicOperatorQrcode $qrcode): string
    {
        return $qrcode->qr_uuid;
    }

    public function generateForOperator(EconomicOperator $operator): EconomicOperatorQrcode
    {
        return DB::transaction(function () use ($operator): EconomicOperatorQrcode {
            $this->deactivateActiveCodes($operator);

            $scanUuid = (string) Str::uuid();

            return EconomicOperatorQrcode::query()->create([
                'operator_id' => $operator->id,
                'qr_uuid' => $scanUuid,
                'qr_value' => $this->buildDisplayLabel($operator->public_id),
                'generated_at' => now(),
                'is_active' => true,
            ]);
        });
    }

    public function findByValue(string $value): ?EconomicOperatorQrcode
    {
        $trimmed = trim($value);

        if (! $this->isResolvableScanToken($trimmed)) {
            return null;
        }

        $query = EconomicOperatorQrcode::query()
            ->where('is_active', true)
            ->with([
                'operator.category',
                'operator.sector',
                'operator.operationalZone',
                'operator.economicZone',
                'operator.taxStatuses' => fn ($q) => $q->orderByDesc('effective_from')->limit(5),
                'operator.fieldVisits' => fn ($q) => $q->with('agent')->orderByDesc('visit_date')->limit(10),
            ]);

        if ($this->isUuid($trimmed)) {
            return $query->where('qr_uuid', $trimmed)->first();
        }

        if (preg_match('/^QR-OWE-COM-\d{6,8}-([0-9A-F]{8})$/i', $trimmed, $matches)) {
            $suffix = strtoupper($matches[1]);

            return $query
                ->whereRaw('UPPER(SUBSTRING(REPLACE(qr_uuid, "-", ""), 1, 8)) = ?', [$suffix])
                ->first();
        }

        return null;
    }

    public function markPrinted(EconomicOperatorQrcode $qrcode): EconomicOperatorQrcode
    {
        $qrcode->update(['printed_at' => now()]);

        return $qrcode->fresh();
    }

    public function buildPngContent(EconomicOperatorQrcode $qrcode): string
    {
        return $this->renderStandardQrImage($this->scanPayload($qrcode));
    }

    public function buildPdfPlaceholder(EconomicOperatorQrcode $qrcode): array
    {
        return [
            'status' => 'not_implemented',
            'message' => 'Génération PDF carte professionnelle prévue en V3.',
            'display_id' => $qrcode->qr_value,
            'scan_token' => $qrcode->qr_uuid,
            'operator_public_id' => $qrcode->operator?->public_id,
        ];
    }

    /**
     * Libellé imprimé sous le QR (référence publique lisible).
     */
    public function buildDisplayLabel(string $publicId): string
    {
        return $publicId;
    }

    /**
     * Forme lisible optionnelle incluant un suffixe court du jeton (carte pro).
     */
    public function buildDisplayLabelWithSuffix(EconomicOperatorQrcode $qrcode): string
    {
        $suffix = strtoupper(substr(str_replace('-', '', $qrcode->qr_uuid), 0, 8));

        return sprintf('QR-%s-%s', $qrcode->qr_value, $suffix);
    }

    private function renderStandardQrImage(string $payload): string
    {
        $qrCode = $this->buildQrCode($payload);

        if (extension_loaded('gd')) {
            return (new PngWriter)->write($qrCode)->getString();
        }

        return (new SvgWriter)->write($qrCode)->getString();
    }

    private function buildQrCode(string $payload): QrCode
    {
        return QrCode::create($payload)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setSize(self::PNG_SIZE)
            ->setMargin(self::QUIET_ZONE_MARGIN)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);
    }

    private function deactivateActiveCodes(EconomicOperator $operator): void
    {
        EconomicOperatorQrcode::query()
            ->where('operator_id', $operator->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    private function isResolvableScanToken(string $value): bool
    {
        if ($this->isUuid($value)) {
            return true;
        }

        return (bool) preg_match('/^QR-OWE-COM-\d{6,8}-[0-9A-F]{8}$/i', $value);
    }

    private function isUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        );
    }
}
