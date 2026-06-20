<?php

namespace App\Modules\Municipality\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorCategory;
use App\Modules\Municipality\Models\MunicipalSector;
use App\Modules\Municipality\Services\EconomicOperatorExportService;
use App\Modules\Municipality\Services\EconomicOperatorQrBatchService;
use App\Modules\Municipality\Services\EconomicOperatorQrDocumentService;
use App\Modules\Municipality\Services\EconomicOperatorRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EconomicOperatorAdminController extends Controller
{
    public function __construct(
        private readonly EconomicOperatorRepository $repository,
        private readonly EconomicOperatorExportService $exportService,
        private readonly EconomicOperatorQrDocumentService $qrDocuments,
        private readonly EconomicOperatorQrBatchService $qrBatchService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EconomicOperator::class);

        $filters = $request->only([
            'q',
            'public_id',
            'commercial_name',
            'responsible_name',
            'phone',
            'sector_id',
            'category_id',
        ]);

        $operators = $this->repository->adminPaginate($filters);

        return view('admin.municipality.operators.index', [
            'operators' => $operators,
            'filters' => $filters,
            'categories' => EconomicOperatorCategory::query()->orderBy('name')->get(['id', 'name']),
            'sectors' => MunicipalSector::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(EconomicOperator $operator): View
    {
        $this->authorize('view', $operator);

        $operator = $this->repository->findForAdminShow($operator->id);
        $fiscal = $this->repository->fiscalSnapshot($operator);

        return view('admin.municipality.operators.show', [
            'operator' => $operator,
            'fiscal' => $fiscal,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorize('export', EconomicOperator::class);

        return $this->exportService->streamCsv($this->exportFilters($request));
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->authorize('export', EconomicOperator::class);

        return $this->exportService->streamExcel($this->exportFilters($request));
    }

    public function exportPdf(Request $request): StreamedResponse
    {
        $this->authorize('export', EconomicOperator::class);

        return $this->exportService->streamPdf($this->exportFilters($request));
    }

    public function downloadQrPng(EconomicOperator $operator): Response
    {
        $this->authorize('downloadQr', $operator);

        return $this->qrDocuments->pngResponse($operator);
    }

    public function downloadQrPdf(EconomicOperator $operator): Response
    {
        $this->authorize('downloadQr', $operator);

        return $this->qrDocuments->pdfResponse($operator);
    }

    public function downloadBusinessCard(EconomicOperator $operator): Response
    {
        $this->authorize('downloadQr', $operator);

        return $this->qrDocuments->businessCardResponse($operator);
    }

    public function qrBatchForm(): View
    {
        $this->authorize('batchQr', EconomicOperator::class);

        return view('admin.municipality.operators.qr-batch', [
            'presets' => EconomicOperatorQrBatchService::PRESET_SIZES,
            'maxBatch' => EconomicOperatorQrBatchService::MAX_BATCH_SIZE,
        ]);
    }

    public function qrBatchGenerate(Request $request): Response
    {
        $this->authorize('batchQr', EconomicOperator::class);

        $data = $request->validate([
            'start' => ['required', 'integer', 'min:1', 'max:99999999'],
            'end' => ['required', 'integer', 'min:1', 'max:99999999'],
        ]);

        return $this->qrBatchService->pdfResponse((int) $data['start'], (int) $data['end']);
    }

    /**
     * @return array<string, mixed>
     */
    private function exportFilters(Request $request): array
    {
        return $request->only([
            'q',
            'public_id',
            'commercial_name',
            'responsible_name',
            'phone',
            'sector_id',
            'category_id',
        ]);
    }
}
