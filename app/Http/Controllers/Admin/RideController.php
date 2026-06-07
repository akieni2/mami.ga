<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Services\RideTrackingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RideController extends Controller
{
    public function __construct(
        private readonly RideTrackingService $rideTrackingService,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $rides = Ride::query()
            ->with(['client', 'driver.user', 'driver.vehicle'])
            ->when(
                $status && in_array($status, ['pending', 'accepted', 'arrived', 'started', 'completed', 'cancelled'], true),
                fn ($q) => $q->where('status', $status),
            )
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.rides.index', compact('rides', 'status'));
    }

    public function show(Ride $ride): View
    {
        $ride->load(['client', 'driver.user', 'driver.vehicle']);
        $tracking = $this->rideTrackingService->snapshot($ride);

        return view('admin.rides.show', compact('ride', 'tracking'));
    }
}
