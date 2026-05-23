<?php

namespace App\Services;

use App\Enums\DriverStatus;
use App\Enums\RideStatus;
use App\Models\Driver;
use Illuminate\Support\Carbon;

class DriverPresenceService
{
    public function touch(Driver $driver): Driver
    {
        $driver->last_seen_at = Carbon::now();

        return $this->applyResolvedStatus($driver);
    }

    public function applyResolvedStatus(Driver $driver): Driver
    {
        $presence = $this->resolvePresence($driver);

        $driver->status = match ($presence) {
            'busy' => DriverStatus::OnRide,
            'online' => DriverStatus::Online,
            default => DriverStatus::Offline,
        };

        if ($presence === 'offline') {
            $driver->is_available = false;
        }

        $driver->save();

        return $driver->fresh();
    }

    public function resolvePresence(Driver $driver): string
    {
        if ($this->hasActiveRide($driver)) {
            return 'busy';
        }

        if (! $this->isRecentlySeen($driver)) {
            return 'offline';
        }

        return $driver->is_available ? 'online' : 'offline';
    }

    public function isRecentlySeen(Driver $driver): bool
    {
        if ($driver->last_seen_at === null) {
            return false;
        }

        $threshold = (int) config('mami.driver_offline_threshold_seconds', 300);

        return $driver->last_seen_at->greaterThanOrEqualTo(Carbon::now()->subSeconds($threshold));
    }

    public function hasActiveRide(Driver $driver): bool
    {
        return $driver->rides()
            ->whereIn('status', [
                RideStatus::Pending,
                RideStatus::Accepted,
                RideStatus::Arrived,
                RideStatus::Started,
            ])
            ->exists();
    }

    public function markStaleDriversOffline(): int
    {
        $threshold = (int) config('mami.driver_offline_threshold_seconds', 300);
        $cutoff = Carbon::now()->subSeconds($threshold);

        return Driver::query()
            ->where('status', '!=', DriverStatus::OnRide)
            ->where(function ($query) use ($cutoff) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', $cutoff);
            })
            ->update([
                'status' => DriverStatus::Offline,
                'is_available' => false,
            ]);
    }
}
