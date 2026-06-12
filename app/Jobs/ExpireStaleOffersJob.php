<?php

namespace App\Jobs;

use App\Enums\RideOfferStatus;
use App\Models\RideOffer;
use App\Support\Dispatch\DispatchLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireStaleOffersJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $count = RideOffer::query()
            ->where('status', RideOfferStatus::Pending)
            ->where('expires_at', '<=', now())
            ->update([
                'status' => RideOfferStatus::Expired,
                'responded_at' => now(),
            ]);

        if ($count > 0) {
            DispatchLogger::expire("{$count} stale offer(s) marked expired");
        }
    }
}
