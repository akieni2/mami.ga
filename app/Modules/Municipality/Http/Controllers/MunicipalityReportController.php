<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Requests\AssignMunicipalityReportRequest;
use App\Modules\Municipality\Http\Requests\StoreMunicipalityReportRequest;
use App\Modules\Municipality\Http\Requests\UpdateMunicipalityReportStatusRequest;
use App\Modules\Municipality\Http\Resources\MunicipalityReportResource;
use App\Modules\Municipality\Models\MunicipalityReport;
use App\Modules\Municipality\Services\MunicipalityReportRepository;
use App\Modules\Municipality\Services\MunicipalityReportService;
use App\Modules\Municipality\Services\LayerSignalements;
use App\Modules\Municipality\Enums\ReportStatus;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MunicipalityReportController extends Controller
{
    public function __construct(
        private readonly MunicipalityReportService $reportService,
        private readonly MunicipalityReportRepository $repository,
        private readonly LayerSignalements $layerSignalements,
    ) {}

    public function store(StoreMunicipalityReportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        unset($validated['photo']);

        $report = $this->reportService->create(
            $request->user(),
            $validated,
            $request->file('photo'),
        );

        return ApiResponse::success(
            new MunicipalityReportResource($report),
            'Signalement enregistré',
            201,
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MunicipalityReport::class);

        $user = $request->user();
        $filters = $request->only(['status', 'category', 'sector_id', 'quartier', 'date_from', 'date_to', 'bbox']);
        $citizenOnly = $request->boolean('mine') || ! $user->isAdmin() && ! $user->hasPermission('municipality.reports.manage') && ! $user->hasRole('municipal_agent');

        if ($citizenOnly) {
            $filters['citizen_id'] = $user->id;
        }

        $paginator = $this->repository->paginateForUser(
            $citizenOnly ? $user->id : null,
            $filters,
            (int) $request->integer('per_page', 25),
        );

        return ApiResponse::paginated(
            $paginator->through(fn ($report) => new MunicipalityReportResource($report)),
            'Signalements',
        );
    }

    public function show(MunicipalityReport $report): JsonResponse
    {
        $this->authorize('view', $report);

        $report->load(['attachments', 'sector', 'operationalZone', 'assignee', 'updates.user']);

        return ApiResponse::success(new MunicipalityReportResource($report));
    }

    public function assign(AssignMunicipalityReportRequest $request, MunicipalityReport $report): JsonResponse
    {
        $report = $this->reportService->assign(
            $request->user(),
            $report,
            (int) $request->validated('assigned_to'),
            $request->validated('notes'),
        );

        return ApiResponse::success(new MunicipalityReportResource($report), 'Signalement assigné');
    }

    public function updateStatus(UpdateMunicipalityReportStatusRequest $request, MunicipalityReport $report): JsonResponse
    {
        $report = $this->reportService->updateStatus(
            $request->user(),
            $report,
            ReportStatus::from($request->validated('status')),
            $request->validated('notes'),
        );

        return ApiResponse::success(new MunicipalityReportResource($report), 'Statut mis à jour');
    }

    public function map(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MunicipalityReport::class);

        $filters = $request->only(['status', 'category', 'sector_id', 'quartier', 'date_from', 'date_to', 'bbox']);

        return ApiResponse::success([
            'layer' => 'signalements',
            'geojson' => $this->layerSignalements->toGeoJson($filters),
        ]);
    }
}
