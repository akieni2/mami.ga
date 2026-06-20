<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\EconomicOperator;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EconomicOperatorExportService
{
    public function __construct(
        private readonly EconomicOperatorRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function streamCsv(array $filters = []): StreamedResponse
    {
        $filename = 'operateurs-economiques-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Identifiant',
                'Commerce',
                'Responsable',
                'Téléphone',
                'Catégorie',
                'Zone',
                'Date création',
                'Statut',
            ], ';');

            $this->repository->adminExportQuery($filters)
                ->chunk(500, function ($operators) use ($handle): void {
                    foreach ($operators as $operator) {
                        /** @var EconomicOperator $operator */
                        fputcsv($handle, [
                            $operator->public_id,
                            $operator->commercial_name,
                            $operator->responsible_name,
                            $operator->phone,
                            $operator->category?->name ?? '',
                            $operator->sector?->name ?? '',
                            $operator->created_at?->format('Y-m-d H:i'),
                            $operator->is_active ? 'Actif' : 'Inactif',
                        ], ';');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Excel-compatible UTF-8 CSV export.
     *
     * @param  array<string, mixed>  $filters
     */
    public function streamExcel(array $filters = []): StreamedResponse
    {
        $filename = 'operateurs-economiques-'.now()->format('Ymd-His').'.xls';

        return response()->streamDownload(function () use ($filters): void {
            echo "\xEF\xBB\xBF";
            echo "Identifiant\tCommerce\tResponsable\tTéléphone\tCatégorie\tZone\tDate création\tStatut\n";

            $this->repository->adminExportQuery($filters)
                ->chunk(500, function ($operators): void {
                    foreach ($operators as $operator) {
                        /** @var EconomicOperator $operator */
                        echo implode("\t", [
                            $operator->public_id,
                            $this->escapeTsv($operator->commercial_name),
                            $this->escapeTsv($operator->responsible_name),
                            $operator->phone,
                            $this->escapeTsv($operator->category?->name ?? ''),
                            $this->escapeTsv($operator->sector?->name ?? ''),
                            $operator->created_at?->format('Y-m-d H:i') ?? '',
                            $operator->is_active ? 'Actif' : 'Inactif',
                        ])."\n";
                    }
                });
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function streamPdf(array $filters = []): StreamedResponse
    {
        $operators = $this->repository->adminExportQuery($filters)
            ->limit(500)
            ->get();

        $html = View::make('admin.municipality.operators.exports.list-pdf', [
            'operators' => $operators,
            'generatedAt' => now(),
            'total' => $this->repository->adminExportQuery($filters)->count(),
        ])->render();

        $pdf = $this->renderPdf($html);

        $filename = 'operateurs-economiques-'.now()->format('Ymd-His').'.pdf';

        return response()->streamDownload(fn () => print($pdf), $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function escapeTsv(string $value): string
    {
        return str_replace(["\t", "\n", "\r"], ' ', $value);
    }

    private function renderPdf(string $html): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
