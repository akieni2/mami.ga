<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Municipality\Enums\ReceiptDocumentFormat;
use App\Modules\Municipality\Models\MunicipalReceipt;
use App\Modules\Municipality\Models\MunicipalReceiptDocument;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class MunicipalReceiptPdfService
{
    public function generateAllFormats(User $agent, MunicipalReceipt $receipt, bool $incrementVersion = false): MunicipalReceipt
    {
        $receipt->loadMissing([
            'payment.operator.sector',
            'payment.agent',
            'payment.allocations.fiscalObligation.taxType',
        ]);

        foreach (ReceiptDocumentFormat::cases() as $format) {
            $this->generate($agent, $receipt, $format, $incrementVersion);
        }

        return $receipt->fresh('documents');
    }

    public function generate(
        User $agent,
        MunicipalReceipt $receipt,
        ReceiptDocumentFormat $format,
        bool $incrementVersion = false,
    ): MunicipalReceiptDocument {
        $receipt->loadMissing([
            'payment.operator.sector',
            'payment.agent',
            'payment.allocations.fiscalObligation.taxType',
        ]);

        $version = $this->nextVersion($receipt, $format, $incrementVersion);
        $html = $this->renderHtml($receipt, $format);
        $pdfBinary = $this->renderPdf($html, $format);

        $path = sprintf(
            'municipality/receipts/%s/%s_v%d.pdf',
            $receipt->receipt_number,
            $format->value,
            $version,
        );

        Storage::disk('local')->put($path, $pdfBinary);

        return MunicipalReceiptDocument::query()->create([
            'municipal_receipt_id' => $receipt->id,
            'format' => $format,
            'version' => $version,
            'storage_path' => $path,
            'disk' => 'local',
            'generated_by' => $agent->id,
            'generated_at' => now(),
        ]);
    }

    public function latestDocument(MunicipalReceipt $receipt, ReceiptDocumentFormat $format): ?MunicipalReceiptDocument
    {
        return MunicipalReceiptDocument::query()
            ->where('municipal_receipt_id', $receipt->id)
            ->where('format', $format)
            ->orderByDesc('version')
            ->first();
    }

    private function nextVersion(MunicipalReceipt $receipt, ReceiptDocumentFormat $format, bool $increment): int
    {
        $current = MunicipalReceiptDocument::query()
            ->where('municipal_receipt_id', $receipt->id)
            ->where('format', $format)
            ->max('version');

        if ($current === null) {
            return 1;
        }

        return $increment ? ((int) $current + 1) : (int) $current;
    }

    private function renderHtml(MunicipalReceipt $receipt, ReceiptDocumentFormat $format): string
    {
        $view = match ($format) {
            ReceiptDocumentFormat::A4Pdf => 'municipality.receipts.a4',
            ReceiptDocumentFormat::Thermal58mm => 'municipality.receipts.thermal_58mm',
        };

        return View::make($view, ['receipt' => $receipt])->render();
    }

    private function renderPdf(string $html, ReceiptDocumentFormat $format): string
    {
        $options = new Options;
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        if ($format === ReceiptDocumentFormat::Thermal58mm) {
            $dompdf->setPaper([0, 0, 164.41, 600], 'portrait');
        } else {
            $dompdf->setPaper('A4', 'portrait');
        }

        $dompdf->render();

        return (string) $dompdf->output();
    }
}
