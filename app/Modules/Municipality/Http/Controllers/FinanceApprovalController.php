<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFinanceAccess;
use App\Modules\Municipality\Http\Resources\FinancialMissionApprovalResource;
use App\Modules\Municipality\Http\Resources\FinancialMissionResource;
use App\Modules\Municipality\Models\FinancialMission;
use App\Modules\Municipality\Models\FinancialMissionApproval;
use App\Modules\Municipality\Services\FinancialMissionWorkflowService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceApprovalController extends Controller
{
    use AuthorizesFinanceAccess;

    public function __construct(
        private readonly FinancialMissionWorkflowService $workflowService,
    ) {}

    public function pending(Request $request): JsonResponse
    {
        $this->authorizeApprovalQueue($request->user());

        $missions = collect($this->workflowService->pendingForUser($request->user()))
            ->map(fn (FinancialMission $mission) => new FinancialMissionResource($mission));

        return ApiResponse::success($missions, 'Missions en attente de validation');
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorizeMissionView($request->user());

        $approvals = FinancialMissionApproval::query()
            ->with(['performer:id,name', 'mission:id,reference,title,workflow_status'])
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', $action))
            ->when($request->query('mission_id'), fn ($q, $missionId) => $q->where('financial_mission_id', $missionId))
            ->orderByDesc('created_at')
            ->paginate(30);

        $approvals->getCollection()->transform(
            fn (FinancialMissionApproval $approval) => new FinancialMissionApprovalResource($approval),
        );

        return ApiResponse::paginated($approvals, 'Historique des validations');
    }
}
