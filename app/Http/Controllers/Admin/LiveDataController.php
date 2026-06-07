<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Ride;
use App\Services\AdminDashboardService;
use App\Services\AdminLiveMapService;
use Illuminate\Http\JsonResponse;

class LiveDataController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboardService,
        private readonly AdminLiveMapService $liveMapService,
    ) {}

    public function dashboard(): JsonResponse
    {
        $recentRides = Ride::query()
            ->with(['client', 'driver.user'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Ride $ride) => [
                'id' => $ride->id,
                'client' => $ride->client?->name,
                'driver' => $ride->driver?->user?->name,
                'status' => $ride->status->value,
                'estimated_price' => $ride->estimated_price,
            ]);

        return response()->json([
            'stats' => $this->dashboardService->stats(),
            'recent_rides' => $recentRides,
            'refreshed_at' => now()->toIso8601String(),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'stats' => $this->dashboardService->stats(),
            'refreshed_at' => now()->toIso8601String(),
        ]);
    }

    public function drivers(): JsonResponse
    {
        return response()->json([
            'drivers' => $this->liveMapService->driversPayload(),
            'refreshed_at' => now()->toIso8601String(),
        ]);
    }

    public function map(): JsonResponse
    {
        return response()->json([
            'drivers' => $this->liveMapService->driversPayload(mapOnly: true),
            'refreshed_at' => now()->toIso8601String(),
        ]);
    }

    public function driver(Driver $driver): JsonResponse
    {
        return response()->json([
            'driver' => $this->liveMapService->driverPayload($driver),
            'refreshed_at' => now()->toIso8601String(),
        ]);
    }
}
