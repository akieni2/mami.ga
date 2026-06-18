<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFiscalAccess;
use App\Modules\Municipality\Http\Resources\CashSessionResource;
use App\Modules\Municipality\Http\Resources\MunicipalPaymentCollectionResource;
use App\Modules\Municipality\Http\Resources\MunicipalReceiptResource;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Services\FiscalCollectionService;
use App\Modules\Municipality\Services\FiscalSupervisorDashboardService;
use App\Modules\Municipality\Services\OperatorFiscalSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FiscalCollectionController extends Controller
{
    use AuthorizesFiscalAccess;

    public function __construct(
        private readonly FiscalCollectionService $collectionService,
        private readonly OperatorFiscalSummaryService $summaryService,
        private readonly FiscalSupervisorDashboardService $dashboardService,
    ) {}

    public function operatorSummary(Request $request, EconomicOperator $operator): JsonResponse
    {
        $this->authorizeCollectionAgent($request->user());

        $gps = $request->only(['latitude', 'longitude', 'gps_accuracy_m']);

        return response()->json([
            'success' => true,
            'data' => $this->summaryService->build($request->user(), $operator, $gps),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeCollectionAgent($request->user());

        $data = $request->validate([
            'operator_id' => ['required', 'integer', 'exists:economic_operators,id'],
            'amount_xaf' => ['required', 'numeric', 'min:1'],
            'cash_session_id' => ['required', 'integer', 'exists:cash_sessions,id'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'gps_accuracy_m' => ['nullable', 'numeric', 'min:0'],
            'device_id' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'client_operation_id' => ['nullable', 'uuid'],
        ]);

        $result = $this->collectionService->collectCash($request->user(), $data);

        return (new MunicipalPaymentCollectionResource($result['municipal_payment']))
            ->response()
            ->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeCollectionAgent($request->user());

        $payments = MunicipalPayment::query()
            ->with(['operator', 'allocations.fiscalObligation.taxType', 'cashSession'])
            ->where('agent_id', $request->user()->id)
            ->orderByDesc('collected_at')
            ->paginate(30);

        return MunicipalPaymentCollectionResource::collection($payments);
    }

    public function supervisorDashboard(Request $request): JsonResponse
    {
        $this->authorizeFiscalView($request->user());

        $data = $this->dashboardService->build($request->query('date'));

        return response()->json([
            'success' => true,
            'data' => [
                'open_sessions_count' => $data['open_sessions_count'],
                'open_sessions' => CashSessionResource::collection($data['open_sessions']),
                'collected_today_xaf' => $data['collected_today_xaf'],
                'collections_by_agent' => $data['collections_by_agent']->map(fn ($row) => [
                    'agent_id' => $row->agent_id,
                    'agent_name' => $row->agent?->name,
                    'total_xaf' => (string) $row->total,
                    'count' => $row->count,
                ]),
                'collections_by_day' => $data['collections_by_day']->map(fn ($row) => [
                    'day' => $row->day,
                    'total_xaf' => (string) $row->total,
                    'count' => $row->count,
                ]),
                'collections_by_quartier' => $data['collections_by_quartier']->map(fn ($row) => [
                    'quartier' => $row->quartier,
                    'total_xaf' => (string) $row->total,
                    'count' => $row->count,
                ]),
                'active_agents_count' => $data['active_agents_count'],
                'active_agents' => $data['active_agents']->map(fn ($row) => [
                    'agent_id' => $row->agent_id,
                    'agent_name' => $row->agent_name,
                    'has_open_session' => $row->has_open_session,
                ]),
                'latest_receipts' => MunicipalReceiptResource::collection($data['latest_receipts']),
            ],
        ]);
    }

    private function authorizeCollectionAgent(\App\Models\User $user): void
    {
        if (! $user->isAdmin()
            && ! $user->hasPermission('municipal.payment.collect')
            && ! $user->hasPermission('municipality.collections.manage')) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Encaissement non autorisé.');
        }
    }
}
