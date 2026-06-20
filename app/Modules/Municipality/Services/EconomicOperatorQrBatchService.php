<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\EconomicOperator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EconomicOperatorQrBatchService
{
    public const MAX_BATCH_SIZE = 10000;

    /** @var list<int> */
    public const PRESET_SIZES = [100, 500, 1000, 5000, 10000];

    public function __construct(
        private readonly EconomicOperatorRepository $repository,
        private readonly EconomicOperatorQrDocumentService $qrDocuments,
        private readonly QRCodeManagement $qrCodeManagement,
    ) {}

    /**
     * @return list<string>
     */
    public function buildPublicIdRange(int $start, int $end): array
    {
        $ids = [];

        for ($sequence = $start; $sequence <= $end; $sequence++) {
            $ids[] = EconomicOperatorRepository::formatPublicId($sequence);
        }

        return $ids;
    }

    /**
     * @return array{start: int, end: int, count: int}
     */
    public function validateRange(int $start, int $end): array
    {
        if ($start < 1 || $end < 1) {
            throw ValidationException::withMessages([
                'start' => ['Les numéros de séquence doivent être positifs.'],
            ]);
        }

        if ($end < $start) {
            throw ValidationException::withMessages([
                'end' => ['La fin doit être supérieure ou égale au début.'],
            ]);
        }

        $count = $end - $start + 1;

        if ($count > self::MAX_BATCH_SIZE) {
            throw ValidationException::withMessages([
                'end' => ['Un lot ne peut pas dépasser '.self::MAX_BATCH_SIZE.' QR codes.'],
            ]);
        }

        return [
            'start' => $start,
            'end' => $end,
            'count' => $count,
        ];
    }

    public function pdfResponse(int $start, int $end): Response
    {
        $this->validateRange($start, $end);

        $publicIds = $this->buildPublicIdRange($start, $end);
        $operators = $this->repository->findByPublicIds($publicIds);

        $pages = collect($publicIds)->map(function (string $publicId) use ($operators) {
            /** @var EconomicOperator|null $operator */
            $operator = $operators->get($publicId);

            if ($operator === null) {
                return [
                    'public_id' => $publicId,
                    'commercial_name' => '— Non enregistré —',
                    'responsible_name' => '—',
                    'png_base64' => null,
                    'registered' => false,
                ];
            }

            $qrcode = $this->qrDocuments->ensureActiveQrcode($operator);

            return [
                'public_id' => $operator->public_id,
                'commercial_name' => $operator->commercial_name,
                'responsible_name' => $operator->responsible_name,
                'png_base64' => base64_encode($this->qrCodeManagement->buildPngContent($qrcode)),
                'registered' => true,
            ];
        });

        $pdf = $this->renderBatchPdf($pages, $start, $end);

        $filename = sprintf('qr-batch-%08d-%08d.pdf', $start, $end);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $pages
     */
    private function renderBatchPdf(Collection $pages, int $start, int $end): string
    {
        $html = view('admin.municipality.operators.exports.qr-batch-pdf', [
            'pages' => $pages,
            'start' => $start,
            'end' => $end,
            'generatedAt' => now(),
        ])->render();

        $options = new \Dompdf\Options;
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
