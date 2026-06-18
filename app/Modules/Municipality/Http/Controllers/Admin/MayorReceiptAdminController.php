<?php

namespace App\Modules\Municipality\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Services\MayorReceiptDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MayorReceiptAdminController extends Controller
{
    public function __construct(
        private readonly MayorReceiptDashboardService $dashboardService,
    ) {}

    public function dashboard(Request $request): View
    {
        $date = $request->query('date', now()->toDateString());
        $data = $this->dashboardService->build($date);

        return view('admin.municipality.receipts.mayor-dashboard', [
            'date' => $date,
            'data' => $data,
        ]);
    }
}
