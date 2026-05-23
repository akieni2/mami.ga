<?php

namespace App\Services;

use App\Enums\DriverStatus;
use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Ride;
use Illuminate\Support\Carbon;

class AdminDashboardService
{
    public function stats(): array
    {
        $offlineCutoff = Carbon::now()->subSeconds(
            (int) config('mami.driver_offline_threshold_seconds', 300)
        );

        $activeRideStatuses = [
            RideStatus::Pending,
            RideStatus::Accepted,
            RideStatus::Arrived,
            RideStatus::Started,
        ];

        return [
            'total_drivers' => Driver::query()->count(),
            'online_drivers' => Driver::query()
                ->where('status', DriverStatus::Online)
                ->where('is_available', true)
                ->where('last_seen_at', '>=', $offlineCutoff)
                ->count(),
            'busy_drivers' => Driver::query()
                ->where('status', DriverStatus::OnRide)
                ->count(),
            'active_rides' => Ride::query()
                ->whereIn('status', $activeRideStatuses)
                ->count(),
            'completed_rides' => Ride::query()
                ->where('status', RideStatus::Completed)
                ->count(),
        ];
    }
}
