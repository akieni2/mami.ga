<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Http\Controllers\Concerns\AuthorizesFinanceAccess;
use App\Modules\Municipality\Http\Resources\FinancialMissionResource;
use App\Modules\Municipality\Models\FinancialMission;
use App\Modules\Municipality\Services\DafDashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DafDashboardController extends Controller
{
    use AuthorizesFinanceAccess;

    public function __construct(
        private readonly DafDashboardService $dashboardService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorizeFinanceDashboard($request->user());

        return ApiResponse::success(
            $this->dashboardService->build($request->query('date')),
            'Tableau de bord DAF',
        );
    }
}
