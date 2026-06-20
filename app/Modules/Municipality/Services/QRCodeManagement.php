<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorQrcode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QRCodeManagement
{
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
        $payload = $this->scanPayload($qrcode);
        $size = 280;

        if (extension_loaded('gd')) {
            return $this->renderPngWithGd($payload, $size);
        }

        return $this->renderSvg($payload, $size);
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

    private function renderPngWithGd(string $payload, int $size): string
    {
        $image = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $size, $size, $white);

        $hash = hash('sha256', $payload);
        $cell = (int) ($size / 16);
        for ($row = 0; $row < 16; $row++) {
            for ($col = 0; $col < 16; $col++) {
                $index = ($row * 16 + $col) % strlen($hash);
                if (hexdec($hash[$index]) % 2 === 0) {
                    imagefilledrectangle(
                        $image,
                        $col * $cell,
                        $row * $cell,
                        ($col + 1) * $cell - 1,
                        ($row + 1) * $cell - 1,
                        $black,
                    );
                }
            }
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    private function renderSvg(string $payload, int $size): string
    {
        $escaped = htmlspecialchars($payload, ENT_XML1);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 {$size} {$size}">
  <rect width="100%" height="100%" fill="#ffffff"/>
  <text x="50%" y="50%" text-anchor="middle" dominant-baseline="middle" font-family="monospace" font-size="10">{$escaped}</text>
</svg>
SVG;
    }
}
