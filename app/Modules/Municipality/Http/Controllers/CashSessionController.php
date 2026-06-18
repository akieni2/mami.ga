<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFiscalAccess;
use App\Modules\Municipality\Http\Resources\CashSessionResource;
use App\Modules\Municipality\Models\CashSession;
use App\Modules\Municipality\Services\CashSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashSessionController extends Controller
{
    use AuthorizesFiscalAccess;

    public function __construct(
        private readonly CashSessionService $cashSessionService,
    ) {}

    public function current(Request $request): JsonResponse
    {
        $this->authorizeCollection($request->user());

        $session = $this->cashSessionService->currentOpenSession($request->user());

        return response()->json([
            'success' => true,
            'data' => $session ? new CashSessionResource($session) : null,
        ]);
    }

    public function open(Request $request): JsonResponse
    {
        $this->authorizeCollection($request->user());

        $data = $request->validate([
            'opening_amount_xaf' => ['required', 'numeric', 'min:0'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'gps_accuracy_m' => ['nullable', 'numeric', 'min:0'],
            'device_id' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $session = $this->cashSessionService->open($request->user(), $data);

        return (new CashSessionResource($session))
            ->response()
            ->setStatusCode(201);
    }

    public function close(Request $request, CashSession $cashSession): CashSessionResource
    {
        $this->authorizeCollection($request->user());

        $data = $request->validate([
            'actual_amount_xaf' => ['required', 'numeric', 'min:0'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        return new CashSessionResource(
            $this->cashSessionService->close($request->user(), $cashSession, $data)
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeFiscalView($request->user());

        $sessions = CashSession::query()
            ->with('agent')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('opened_at')
            ->paginate(30);

        return response()->json([
            'success' => true,
            'data' => CashSessionResource::collection($sessions),
        ]);
    }

    private function authorizeCollection(\App\Models\User $user): void
    {
        if (! $user->isAdmin()
            && ! $user->hasPermission('municipal.payment.collect')
            && ! $user->hasPermission('municipal.cash_session.open')) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Accès recouvrement non autorisé.');
        }
    }
}
