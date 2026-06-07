<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriverApplication;
use App\Services\DriverEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class DriverApplicationController extends Controller
{
    public function __construct(
        private readonly DriverEnrollmentService $enrollmentService,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $applications = DriverApplication::query()
            ->with('user')
            ->when(
                $status && in_array($status, ['pending', 'approved', 'rejected'], true),
                fn ($q) => $q->where('status', $status),
            )
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.driver-applications.index', compact('applications', 'status'));
    }

    public function show(DriverApplication $driverApplication): View
    {
        $driverApplication->load(['user', 'reviewer']);

        return view('admin.driver-applications.show', [
            'application' => $driverApplication,
        ]);
    }

    public function approve(DriverApplication $driverApplication): RedirectResponse
    {
        try {
            $this->enrollmentService->approve($driverApplication, request()->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['approve' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.driver-applications.show', $driverApplication)
            ->with('success', 'Candidature approuvée. Chauffeur et véhicule créés.');
    }

    public function reject(Request $request, DriverApplication $driverApplication): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        try {
            $this->enrollmentService->reject(
                $driverApplication,
                $request->user(),
                $validated['rejection_reason'],
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['reject' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.driver-applications.show', $driverApplication)
            ->with('success', 'Candidature rejetée.');
    }
}
