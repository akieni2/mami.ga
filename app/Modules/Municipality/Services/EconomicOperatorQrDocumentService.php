<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorQrcode;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EconomicOperatorQrDocumentService
{
    public function __construct(
        private readonly QRCodeManagement $qrCodeManagement,
    ) {}

    public function ensureActiveQrcode(EconomicOperator $operator): EconomicOperatorQrcode
    {
        $operator->loadMissing('activeQrcode');

        if ($operator->activeQrcode !== null) {
            return $operator->activeQrcode;
        }

        return $this->qrCodeManagement->generateForOperator($operator);
    }

    public function pngResponse(EconomicOperator $operator): Response
    {
        $qrcode = $this->ensureActiveQrcode($operator);
        $binary = $this->qrCodeManagement->buildPngContent($qrcode);
        $isSvg = str_starts_with(trim($binary), '<svg');

        return response($binary, 200, [
            'Content-Type' => $isSvg ? 'image/svg+xml' : 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$operator->public_id.'-qr.'.($isSvg ? 'svg' : 'png').'"',
        ]);
    }

    public function pdfResponse(EconomicOperator $operator): Response
    {
        $qrcode = $this->ensureActiveQrcode($operator);
        $pdf = $this->renderSingleQrPdf($operator, $qrcode);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$operator->public_id.'-qr.pdf"',
        ]);
    }

    public function businessCardResponse(EconomicOperator $operator): Response
    {
        $qrcode = $this->ensureActiveQrcode($operator);
        $pdf = $this->renderBusinessCardPdf($operator, $qrcode);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$operator->public_id.'-carte-commerce.pdf"',
        ]);
    }

    public function renderSingleQrPdf(EconomicOperator $operator, EconomicOperatorQrcode $qrcode): string
    {
        $pngBase64 = base64_encode($this->qrCodeManagement->buildPngContent($qrcode));

        $html = View::make('admin.municipality.operators.exports.qr-single-pdf', [
            'operator' => $operator,
            'qrcode' => $qrcode,
            'pngBase64' => $pngBase64,
        ])->render();

        return $this->renderPdf($html, 'A4', 'portrait');
    }

    public function renderBusinessCardPdf(EconomicOperator $operator, EconomicOperatorQrcode $qrcode): string
    {
        $pngBase64 = base64_encode($this->qrCodeManagement->buildPngContent($qrcode));

        $html = View::make('admin.municipality.operators.exports.business-card-pdf', [
            'operator' => $operator,
            'qrcode' => $qrcode,
            'pngBase64' => $pngBase64,
        ])->render();

        return $this->renderPdf($html, [0, 0, 595.28, 841.89], 'portrait');
    }

    private function renderPdf(string $html, array|string $paper, string $orientation): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
