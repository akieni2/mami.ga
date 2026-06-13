<?php

namespace App\Jobs;

use App\Enums\RideStatus;
use App\Models\Ride;
use App\Services\RideDispatchEngine;
use App\Support\MamiFeatures;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireRideSearchJob implements ShouldQueue
{
    use Queueable;

    public function handle(RideDispatchEngine $engine): void
    {
        if (! MamiFeatures::dispatchV2Enabled()) {
            return;
        }

        $engine->recoverPendingSearches();
        $engine->recoverStuckDispatches();

        Ride::query()
            ->where('status', RideStatus::Searching)
            ->whereNotNull('dispatch_expires_at')
            ->where('dispatch_expires_at', '<=', now())
            ->each(fn (Ride $ride) => $engine->expireRide($ride));
    }
}
