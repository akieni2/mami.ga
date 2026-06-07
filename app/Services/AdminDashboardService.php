<?php

namespace App\Services;

use App\Enums\DriverStatus;
use App\Enums\RideStatus;
use App\Enums\DriverApplicationStatus;
use App\Models\Driver;
use App\Models\DriverApplication;
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

        $today = Carbon::today();

        $onlineDrivers = Driver::query()
            ->where('status', DriverStatus::Online)
            ->where('is_available', true)
            ->where('last_seen_at', '>=', $offlineCutoff)
            ->count();

        $busyDrivers = Driver::query()
            ->where('status', DriverStatus::OnRide)
            ->count();

        $totalDrivers = Driver::query()->count();

        return [
            'total_drivers' => $totalDrivers,
            'online_drivers' => $onlineDrivers,
            'offline_drivers' => max(0, $totalDrivers - $onlineDrivers - $busyDrivers),
            'busy_drivers' => $busyDrivers,
            'active_rides' => Ride::query()
                ->whereIn('status', $activeRideStatuses)
                ->count(),
            'rides_today' => Ride::query()
                ->whereDate('created_at', $today)
                ->count(),
            'completed_rides' => Ride::query()
                ->where('status', RideStatus::Completed)
                ->count(),
            'completed_rides_today' => Ride::query()
                ->where('status', RideStatus::Completed)
                ->whereDate('completed_at', $today)
                ->count(),
            'estimated_revenue_today' => (float) Ride::query()
                ->where('status', RideStatus::Completed)
                ->whereDate('completed_at', $today)
                ->sum('estimated_price'),
            'estimated_revenue_total' => (float) Ride::query()
                ->where('status', RideStatus::Completed)
                ->sum('estimated_price'),
            'pending_applications' => DriverApplication::query()
                ->where('status', DriverApplicationStatus::Pending)
                ->count(),
            'approved_applications' => DriverApplication::query()
                ->where('status', DriverApplicationStatus::Approved)
                ->count(),
            'rejected_applications' => DriverApplication::query()
                ->where('status', DriverApplicationStatus::Rejected)
                ->count(),
        ];
    }
}
