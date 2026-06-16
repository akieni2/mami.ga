<?php

namespace App\Jobs;

use App\Models\DriverLocation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class PruneDriverLocationsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $minutes = (int) config('mami.driver_location_history_minutes', 10);

        DriverLocation::query()
            ->where('recorded_at', '<', Carbon::now()->subMinutes($minutes))
            ->delete();
    }
}
