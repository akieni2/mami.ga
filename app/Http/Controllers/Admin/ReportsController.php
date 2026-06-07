<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminReportsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function __construct(
        private readonly AdminReportsService $reportsService,
    ) {}

    public function index(Request $request): View
    {
        $period = in_array($request->query('period'), ['day', 'week', 'month'], true)
            ? $request->query('period')
            : 'day';

        $summary = $this->reportsService->summary($period);

        return view('admin.reports.index', compact('summary', 'period'));
    }
}
