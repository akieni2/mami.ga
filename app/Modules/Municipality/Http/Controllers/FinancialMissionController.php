<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFinanceAccess;
use App\Modules\Municipality\Http\Resources\FinancialMissionResource;
use App\Modules\Municipality\Models\FinancialMission;
use App\Modules\Municipality\Services\FinancialMissionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialMissionController extends Controller
{
    use AuthorizesFinanceAccess;

    public function __construct(
        private readonly FinancialMissionService $missionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeMissionView($request->user());

        $missions = FinancialMission::query()
            ->with(['agent:id,name', 'operationalZone:id,name', 'creator:id,name', 'authorizer:id,name', 'controller:id,name', 'daf:id,name'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('workflow_status'), fn ($q, $workflowStatus) => $q->where('workflow_status', $workflowStatus))
            ->when($request->query('agent_id'), fn ($q, $agentId) => $q->where('agent_id', $agentId))
            ->orderByDesc('created_at')
            ->paginate(30);

        $missions->getCollection()->transform(
            fn (FinancialMission $mission) => new FinancialMissionResource($mission),
        );

        return ApiResponse::paginated($missions, 'Missions financières');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeMissionManage($request->user());

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'agent_id' => ['required', 'integer', 'exists:users,id'],
            'operational_zone_id' => ['nullable', 'integer', 'exists:municipal_sectors,id'],
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:valid_from'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $mission = $this->missionService->create($request->user(), $data);

        return ApiResponse::success(
            new FinancialMissionResource($mission),
            'Mission financière créée',
            201,
        );
    }

    public function show(Request $request, FinancialMission $mission): JsonResponse
    {
        $this->authorizeMissionView($request->user());

        $mission->load(['agent', 'operationalZone', 'creator', 'authorizer', 'closer', 'controller', 'daf', 'approvals.performer']);

        return ApiResponse::success(new FinancialMissionResource($mission));
    }

    public function update(Request $request, FinancialMission $mission): JsonResponse
    {
        $this->authorizeMissionManage($request->user());

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:200'],
            'agent_id' => ['sometimes', 'integer', 'exists:users,id'],
            'operational_zone_id' => ['nullable', 'integer', 'exists:municipal_sectors,id'],
            'valid_from' => ['sometimes', 'date'],
            'valid_until' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $mission = $this->missionService->update($request->user(), $mission, $data);

        return ApiResponse::success(new FinancialMissionResource($mission), 'Mission mise à jour');
    }

    public function authorizeMission(Request $request, FinancialMission $mission): JsonResponse
    {
        $this->authorizeMissionAuthorize($request->user());

        $mission = $this->missionService->authorize($request->user(), $mission);

        return ApiResponse::success(new FinancialMissionResource($mission), 'Mission autorisée');
    }

    public function close(Request $request, FinancialMission $mission): JsonResponse
    {
        $this->authorizeMissionManage($request->user());

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $mission = $this->missionService->close($request->user(), $mission, $data['notes'] ?? null);

        return ApiResponse::success(new FinancialMissionResource($mission), 'Mission clôturée');
    }

    public function current(Request $request): JsonResponse
    {
        $this->authorizeMissionView($request->user());

        $mission = $this->missionService->activeForAgent($request->user());

        return ApiResponse::success(
            $mission ? new FinancialMissionResource($mission->load(['operationalZone', 'agent'])) : null,
            'Mission active de l\'agent',
        );
    }
}
