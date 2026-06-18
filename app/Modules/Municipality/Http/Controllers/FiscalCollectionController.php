<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFiscalAccess;
use App\Modules\Municipality\Http\Resources\CashSessionResource;
use App\Modules\Municipality\Http\Resources\MunicipalPaymentCollectionResource;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Services\FiscalCollectionService;
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
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'gps_accuracy_m' => ['required', 'numeric', 'min:0'],
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

        $today = now()->toDateString();

        $openSessions = \App\Modules\Municipality\Models\CashSession::query()
            ->with('agent')
            ->where('status', 'open')
            ->get();

        $collectedToday = MunicipalPayment::query()
            ->whereDate('collected_at', $today)
            ->where('status', 'completed')
            ->sum('amount');

        $byAgent = MunicipalPayment::query()
            ->selectRaw('agent_id, SUM(amount) as total, COUNT(*) as count')
            ->whereDate('collected_at', $today)
            ->where('status', 'completed')
            ->groupBy('agent_id')
            ->with('agent:id,name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'open_sessions_count' => $openSessions->count(),
                'open_sessions' => CashSessionResource::collection($openSessions),
                'collected_today_xaf' => (string) $collectedToday,
                'collections_by_agent' => $byAgent->map(fn ($row) => [
                    'agent_id' => $row->agent_id,
                    'agent_name' => $row->agent?->name,
                    'total_xaf' => (string) $row->total,
                    'count' => $row->count,
                ]),
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
