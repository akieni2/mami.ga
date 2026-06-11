<?php

namespace App\Services;

use App\Enums\DriverStatus;
use App\Enums\RideOfferStatus;
use App\Enums\RideStatus;
use App\Jobs\DispatchWaveJob;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\RideDispatchWave;
use App\Support\Dispatch\DispatchLogger;
use App\Support\Geo\GeoPoint;
use App\Support\GeoDistance;
use App\Support\MamiFeatures;
use Illuminate\Support\Carbon;

class RideDispatchEngine
{
    public function __construct(
        private readonly AddressHintService $addressHintService,
        private readonly RideDispatchScoringService $scoringService,
        private readonly RideOfferService $rideOfferService,
    ) {}

    public function start(Ride $ride): void
    {
        if (! MamiFeatures::dispatchV2Enabled()) {
            return;
        }

        if ($ride->status !== RideStatus::Searching) {
            DispatchLogger::dispatch("Ride #{$ride->id} skip start: status={$ride->status->value}");

            return;
        }

        if ($ride->dispatch_started_at !== null) {
            DispatchLogger::dispatch("Ride #{$ride->id} dispatch already started");

            return;
        }

        $ride->update(['dispatch_started_at' => now()]);

        DispatchLogger::dispatch("Ride #{$ride->id} searching");

        DispatchWaveJob::dispatch($ride->id, 0);
    }

    public function recoverPendingSearches(): void
    {
        if (! MamiFeatures::dispatchV2Enabled()) {
            return;
        }

        Ride::query()
            ->where('status', RideStatus::Searching)
            ->whereNull('dispatch_started_at')
            ->where('dispatch_expires_at', '>', now())
            ->each(fn (Ride $ride) => $this->start($ride));
    }

    public function processWave(int $rideId, int $waveIndex): void
    {
        if (! MamiFeatures::dispatchV2Enabled()) {
            return;
        }

        $ride = Ride::query()->find($rideId);

        if ($ride === null || $ride->status !== RideStatus::Searching) {
            DispatchLogger::wave("Ride #{$rideId} wave {$waveIndex} skipped: not searching");

            return;
        }

        if ($ride->dispatch_expires_at !== null && $ride->dispatch_expires_at->isPast()) {
            DispatchLogger::wave("Ride #{$rideId} wave {$waveIndex} skipped: search expired");

            return;
        }

        $waves = config('mami.dispatch_radius_waves', []);

        if (! isset($waves[$waveIndex])) {
            DispatchLogger::wave("Ride #{$rideId} all waves completed");

            return;
        }

        $wave = $waves[$waveIndex];
        $minKm = (float) $wave['min'];
        $maxKm = (float) $wave['max'];
        $waveLabel = "{$minKm}-{$maxKm}km";

        DispatchLogger::wave("Ride #{$rideId} wave {$waveLabel} started");

        $searchPoint = $this->resolveSearchPoint($ride);
        $maxDrivers = (int) config('mami.dispatch_wave_max_drivers', 5);

        $waveRecord = RideDispatchWave::query()->create([
            'ride_id' => $ride->id,
            'radius_min_km' => $minKm,
            'radius_max_km' => $maxKm,
            'drivers_notified' => 0,
            'started_at' => now(),
        ]);

        $alreadyOfferedIds = $ride->offers()->pluck('driver_id')->all();

        $candidates = $this->findEligibleDrivers(
            $searchPoint,
            $minKm,
            $maxKm,
            $alreadyOfferedIds,
        );

        $scored = $candidates
            ->map(function (Driver $driver) use ($maxKm) {
                $metrics = $this->scoringService->score(
                    $driver,
                    (float) $driver->distance_km,
                    $maxKm,
                );
                $driver->dispatch_score = $metrics['score'];
                $driver->distance_km = $metrics['distance_km'];

                return $driver;
            })
            ->sortByDesc('dispatch_score')
            ->take($maxDrivers)
            ->values();

        $notified = 0;

        foreach ($scored as $driver) {
            $this->rideOfferService->createOffer(
                $ride,
                $driver,
                (float) $driver->distance_km,
                (float) $driver->dispatch_score,
                $waveLabel,
            );
            $notified++;
        }

        $waveRecord->update([
            'drivers_notified' => $notified,
            'ended_at' => now(),
        ]);

        DispatchLogger::wave("Ride #{$rideId} wave {$waveLabel} ended drivers_notified={$notified}");

        $ride->refresh();

        if ($ride->status === RideStatus::Searching) {
            $this->scheduleNextWave($rideId, $waveIndex);
        }
    }

    public function resolveSearchPoint(Ride $ride): GeoPoint
    {
        if ($ride->hasPickupCoordinates()) {
            return GeoPoint::fromRidePickup($ride);
        }

        $hint = $this->addressHintService->resolve($ride->pickup_label);

        if ($hint !== null) {
            DispatchLogger::dispatch("Ride #{$ride->id} search point from address hint");

            return $hint;
        }

        DispatchLogger::dispatch("Ride #{$ride->id} search point fallback Libreville center");

        return $this->addressHintService->fallbackSearchPoint();
    }

    /**
     * @param  array<int, int>  $excludeDriverIds
     * @return \Illuminate\Support\Collection<int, Driver>
     */
    private function findEligibleDrivers(
        GeoPoint $searchPoint,
        float $minKm,
        float $maxKm,
        array $excludeDriverIds,
    ) {
        return Driver::query()
            ->with(['user', 'vehicle'])
            ->get()
            ->filter(function (Driver $driver) use ($excludeDriverIds, $minKm, $maxKm, $searchPoint) {
                if (in_array($driver->id, $excludeDriverIds, true)) {
                    DispatchLogger::driverFilter("Driver #{$driver->id} rejected: already solicited");

                    return false;
                }

                if (! $driver->hasGpsPosition()) {
                    DispatchLogger::driverFilter("Driver #{$driver->id} rejected: no coordinates");

                    return false;
                }

                if ($driver->status !== DriverStatus::Online) {
                    DispatchLogger::driverFilter("Driver #{$driver->id} rejected: offline");

                    return false;
                }

                if (! $driver->is_available) {
                    DispatchLogger::driverFilter("Driver #{$driver->id} rejected: unavailable");

                    return false;
                }

                $distanceKm = GeoDistance::kilometers(
                    $searchPoint->latitude,
                    $searchPoint->longitude,
                    (float) $driver->latitude,
                    (float) $driver->longitude,
                );

                if ($distanceKm < $minKm || $distanceKm > $maxKm) {
                    return false;
                }

                $driver->distance_km = $distanceKm;

                return true;
            })
            ->values();
    }

    private function scheduleNextWave(int $rideId, int $currentWaveIndex): void
    {
        $waves = config('mami.dispatch_radius_waves', []);
        $nextIndex = $currentWaveIndex + 1;

        if (! isset($waves[$nextIndex])) {
            return;
        }

        $delaySeconds = (int) config('mami.dispatch_wave_delay_seconds', 15);

        DispatchWaveJob::dispatch($rideId, $nextIndex)
            ->delay(Carbon::now()->addSeconds($delaySeconds));
    }

    public function expireRide(Ride $ride): void
    {
        if ($ride->status !== RideStatus::Searching) {
            return;
        }

        $ride->update(['status' => RideStatus::Expired]);

        $this->rideOfferService->expirePendingOffers($ride);

        DispatchLogger::expire("Ride #{$ride->id} expired after search timeout");

        \App\Events\RideSearchExpired::dispatch($ride->fresh(['client']));
    }
}
