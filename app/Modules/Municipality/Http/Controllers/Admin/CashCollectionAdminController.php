<?php

namespace App\Modules\Municipality\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Services\FiscalSupervisorDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashCollectionAdminController extends Controller
{
    public function __construct(
        private readonly FiscalSupervisorDashboardService $dashboardService,
    ) {}

    public function dashboard(Request $request): View
    {
        $date = $request->query('date', now()->toDateString());
        $data = $this->dashboardService->build($date);

        return view('admin.municipality.collection.dashboard', [
            'date' => $date,
            'openSessions' => $data['open_sessions'],
            'collectedToday' => (float) $data['collected_today_xaf'],
            'byAgent' => $data['collections_by_agent'],
            'byDay' => $data['collections_by_day'],
            'byQuartier' => $data['collections_by_quartier'],
        ]);
    }
}
