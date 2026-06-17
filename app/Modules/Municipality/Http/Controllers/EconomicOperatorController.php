<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Requests\InspectEconomicOperatorRequest;
use App\Modules\Municipality\Http\Requests\StoreEconomicOperatorRequest;
use App\Modules\Municipality\Http\Requests\UpdateEconomicOperatorRequest;
use App\Modules\Municipality\Http\Resources\EconomicOperatorCategoryResource;
use App\Modules\Municipality\Http\Resources\EconomicOperatorResource;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorCategory;
use App\Modules\Municipality\Services\EconomicOperatorDashboardService;
use App\Modules\Municipality\Services\EconomicOperatorRepository;
use App\Modules\Municipality\Services\EconomicOperatorService;
use App\Modules\Municipality\Services\LayerEconomicOperators;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EconomicOperatorController extends Controller
{
    public function __construct(
        private readonly EconomicOperatorService $operatorService,
        private readonly EconomicOperatorRepository $repository,
        private readonly LayerEconomicOperators $layerEconomicOperators,
        private readonly EconomicOperatorDashboardService $dashboardService,
    ) {}

    public function categories(): JsonResponse
    {
        $categories = EconomicOperatorCategory::query()->orderBy('name')->get();

        return ApiResponse::success(
            EconomicOperatorCategoryResource::collection($categories)->resolve(),
            'Catégories d\'activité',
        );
    }

    public function store(StoreEconomicOperatorRequest $request): JsonResponse
    {
        $validated = $request->validated();
        unset($validated['facade'], $validated['trade_registry'], $validated['business_license'], $validated['municipal_authorization'], $validated['location_confirmed']);

        $operator = $this->operatorService->enroll(
            $request->user(),
            $validated,
            [
                'facade' => $request->file('facade'),
                'trade_registry' => $request->file('trade_registry'),
                'business_license' => $request->file('business_license'),
                'municipal_authorization' => $request->file('municipal_authorization'),
            ],
        );

        return ApiResponse::success(
            new EconomicOperatorResource($operator),
            'Commerce enregistré',
            201,
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EconomicOperator::class);

        $paginator = $this->repository->paginate(
            null,
            $request->only(['tax_status', 'category_id', 'sector_id', 'economic_zone_id', 'q', 'date_from', 'date_to', 'bbox']),
            (int) $request->integer('per_page', 25),
        );

        return ApiResponse::paginated(
            $paginator->through(fn ($operator) => new EconomicOperatorResource($operator)),
            'Opérateurs économiques',
        );
    }

    public function show(EconomicOperator $operator): JsonResponse
    {
        $this->authorize('view', $operator);

        $operator->load(['category', 'sector', 'operationalZone', 'economicZone', 'arrondissement', 'registeredBy', 'attachments']);

        return ApiResponse::success(new EconomicOperatorResource($operator));
    }

    public function update(UpdateEconomicOperatorRequest $request, EconomicOperator $operator): JsonResponse
    {
        $operator = $this->operatorService->update(
            $request->user(),
            $operator,
            $request->validated(),
        );

        return ApiResponse::success(new EconomicOperatorResource($operator), 'Commerce mis à jour');
    }

    public function inspect(InspectEconomicOperatorRequest $request, EconomicOperator $operator): JsonResponse
    {
        $operator = $this->operatorService->recordInspection(
            $request->user(),
            $operator,
            $request->validated('notes'),
        );

        return ApiResponse::success(new EconomicOperatorResource($operator), 'Visite terrain enregistrée');
    }

    public function map(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EconomicOperator::class);

        $filters = $request->only(['tax_status', 'category_id', 'sector_id', 'economic_zone_id', 'bbox']);

        return ApiResponse::success([
            'layer' => 'economic_operators',
            'geojson' => $this->layerEconomicOperators->toGeoJson($filters),
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EconomicOperator::class);

        return ApiResponse::success($this->dashboardService->kpis(), 'KPIs recensement économique');
    }
}
