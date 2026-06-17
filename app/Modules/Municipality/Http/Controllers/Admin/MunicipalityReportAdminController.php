<?php

namespace App\Modules\Municipality\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Enums\ReportCategory;
use App\Modules\Municipality\Enums\ReportStatus;
use App\Modules\Municipality\Models\MunicipalSector;
use App\Modules\Municipality\Models\MunicipalityReport;
use App\Modules\Municipality\Services\MunicipalityReportRepository;
use App\Modules\Municipality\Services\MunicipalityReportService;
use App\Modules\Municipality\Enums\ReportStatus as StatusEnum;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MunicipalityReportAdminController extends Controller
{
    public function __construct(
        private readonly MunicipalityReportRepository $repository,
        private readonly MunicipalityReportService $reportService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'category', 'sector_id', 'date_from', 'date_to']);
        $reports = $this->repository->paginateForUser(null, $filters, 30);

        $quartiers = MunicipalSector::query()
            ->where('sector_type', 'quartier')
            ->orderBy('name')
            ->get();

        return view('admin.municipality.reports.index', [
            'reports' => $reports,
            'quartiers' => $quartiers,
            'filters' => $filters,
            'statuses' => ReportStatus::cases(),
            'categories' => ReportCategory::cases(),
        ]);
    }

    public function show(MunicipalityReport $report): View
    {
        $report->load(['citizen', 'sector', 'operationalZone', 'assignee', 'attachments', 'updates.user']);

        $agents = User::query()
            ->where('is_admin', true)
            ->orWhereHas('roles', fn ($q) => $q->where('slug', 'municipal_agent'))
            ->orderBy('name')
            ->get();

        return view('admin.municipality.reports.show', [
            'report' => $report,
            'agents' => $agents,
            'statuses' => ReportStatus::cases(),
        ]);
    }

    public function assign(Request $request, MunicipalityReport $report): RedirectResponse
    {
        $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->reportService->assign(
            $request->user(),
            $report,
            (int) $request->input('assigned_to'),
            $request->input('notes'),
        );

        return back()->with('success', 'Signalement assigné.');
    }

    public function updateStatus(Request $request, MunicipalityReport $report): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:'.implode(',', ReportStatus::values())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->reportService->updateStatus(
            $request->user(),
            $report,
            StatusEnum::from($request->input('status')),
            $request->input('notes'),
        );

        return back()->with('success', 'Statut mis à jour.');
    }
}
