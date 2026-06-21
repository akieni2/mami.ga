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

class FinanceWorkflowController extends Controller
{
    use AuthorizesFinanceAccess;

    public function __construct(
        private readonly FinancialMissionWorkflowService $workflowService,
    ) {}

    public function submit(Request $request, FinancialMission $mission): JsonResponse
    {
        $this->authorizeMissionSubmit($request->user());

        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $mission = $this->workflowService->submit(
            $request->user(),
            $mission,
            $data['comments'] ?? null,
        );

        return ApiResponse::success(new FinancialMissionResource($mission), 'Mission soumise pour validation');
    }

    public function review(Request $request, FinancialMission $mission): JsonResponse
    {
        $user = $request->user();

        if ($mission->workflow_status->value === 'submitted') {
            $this->authorizeMissionControllerReview($user);
        } else {
            $this->authorizeMissionDafReview($user);
        }

        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $mission = $this->workflowService->review(
            $user,
            $mission,
            $data['comments'] ?? null,
        );

        return ApiResponse::success(new FinancialMissionResource($mission), 'Mission revue');
    }

    public function approve(Request $request, FinancialMission $mission): JsonResponse
    {
        $this->authorizeMissionAuthorize($request->user());

        $data = $request->validate([
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $mission = $this->workflowService->approve(
            $request->user(),
            $mission,
            $data['comments'] ?? null,
        );

        return ApiResponse::success(new FinancialMissionResource($mission), 'Mission approuvée');
    }

    public function reject(Request $request, FinancialMission $mission): JsonResponse
    {
        $user = $request->user();

        if ($mission->workflow_status->value === 'submitted') {
            $this->authorizeMissionControllerReview($user);
        } else {
            $this->authorizeMissionDafReview($user);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $mission = $this->workflowService->reject(
            $user,
            $mission,
            $data['reason'],
            $data['comments'] ?? null,
        );

        return ApiResponse::success(new FinancialMissionResource($mission), 'Mission rejetée');
    }

    public function close(Request $request, FinancialMission $mission): JsonResponse
    {
        $this->authorizeMissionManage($request->user());

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $mission = $this->workflowService->close(
            $request->user(),
            $mission,
            $data['notes'] ?? null,
        );

        return ApiResponse::success(new FinancialMissionResource($mission), 'Mission clôturée');
    }

    public function history(Request $request, FinancialMission $mission): JsonResponse
    {
        $this->authorizeMissionView($request->user());

        $approvals = FinancialMissionApproval::query()
            ->with(['performer:id,name'])
            ->where('financial_mission_id', $mission->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (FinancialMissionApproval $approval) => new FinancialMissionApprovalResource($approval));

        return ApiResponse::success($approvals, 'Historique de validation');
    }
}
