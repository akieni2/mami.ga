<?php

namespace App\Services;

use App\Enums\DriverStatus;
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
use Illuminate\Support\Number;

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

        if (! $ride->hasPickupCoordinates()) {
            DispatchLogger::dispatch(
                "Ride #{$ride->id} aborted: no client pickup GPS — cannot dispatch text-only ride",
            );

            return;
        }

        $ride->update(['dispatch_started_at' => now()]);

        DispatchLogger::dispatch(
            "Ride #{$ride->id} searching from client GPS "
            ."({$ride->pickup_latitude}, {$ride->pickup_longitude})",
        );

        $this->processWave($ride->id, 0);
    }

    public function recoverStuckDispatches(): void
    {
        if (! MamiFeatures::dispatchV2Enabled()) {
            return;
        }

        Ride::query()
            ->where('status', RideStatus::Searching)
            ->whereNotNull('dispatch_started_at')
            ->where('dispatch_expires_at', '>', now())
            ->whereDoesntHave('dispatchWaves')
            ->whereNotNull('pickup_latitude')
            ->whereNotNull('pickup_longitude')
            ->each(function (Ride $ride) {
                DispatchLogger::dispatch("Ride #{$ride->id} recover stuck dispatch (no waves executed)");
                $this->processWave($ride->id, 0);
            });
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
        $maxKm = (float) ($wave['max'] ?? 1);
        $waveLabel = "{$maxKm}km";

        DispatchLogger::wave("Ride #{$rideId} wave radius {$waveLabel} started");

        $searchPoint = $this->resolveSearchPoint($ride);
        $maxDrivers = (int) config('mami.dispatch_wave_max_drivers', 5);

        $waveRecord = RideDispatchWave::query()->create([
            'ride_id' => $ride->id,
            'radius_min_km' => 0,
            'radius_max_km' => $maxKm,
            'drivers_notified' => 0,
            'started_at' => now(),
        ]);

        $alreadyOfferedIds = $ride->offers()->pluck('driver_id')->all();

        $candidates = $this->findEligibleDrivers(
            $searchPoint,
            $maxKm,
            $alreadyOfferedIds,
        );

        if ($candidates->isEmpty()) {
            DispatchLogger::wave("Ride #{$rideId} wave {$waveLabel} zero eligible drivers");
        }

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

        if ($ride->status !== RideStatus::Searching) {
            return;
        }

        $nextIndex = $waveIndex + 1;

        if ($notified === 0 && isset($waves[$nextIndex])) {
            DispatchLogger::wave("Ride #{$rideId} chaining to wave index {$nextIndex} (no drivers in {$waveLabel})");
            $this->processWave($rideId, $nextIndex);

            return;
        }

        if (isset($waves[$nextIndex])) {
            $this->scheduleNextWave($rideId, $waveIndex);
        }
    }

    public function resolveSearchPoint(Ride $ride): GeoPoint
    {
        if ($ride->hasPickupCoordinates()) {
            return GeoPoint::fromRidePickup($ride);
        }

        return $this->addressHintService->fallbackSearchPoint();
    }

    /**
     * @param  array<int, int>  $excludeDriverIds
     * @return \Illuminate\Support\Collection<int, Driver>
     */
    private function findEligibleDrivers(
        GeoPoint $searchPoint,
        float $maxKm,
        array $excludeDriverIds,
    ) {
        $freshnessSeconds = (int) config('mami.driver_gps_freshness_seconds', 120);

        return Driver::query()
            ->with(['user', 'vehicle'])
            ->get()
            ->filter(function (Driver $driver) use ($excludeDriverIds, $maxKm, $searchPoint, $freshnessSeconds) {
                $clientGps = "{$searchPoint->latitude},{$searchPoint->longitude}";
                $driverGps = $driver->latitude !== null && $driver->longitude !== null
                    ? "{$driver->latitude},{$driver->longitude}"
                    : 'null';
                $gpsAt = $driver->last_gps_at ?? $driver->last_seen_at;
                $gpsAgeSeconds = $gpsAt?->diffInSeconds(now());

                if (in_array($driver->id, $excludeDriverIds, true)) {
                    DispatchLogger::driverFilter(
                        "Driver #{$driver->id} rejected: already solicited client_gps={$clientGps} "
                        ."driver_gps={$driverGps} distance_km=n/a gps_age_seconds=".($gpsAgeSeconds ?? 'n/a')
                    );

                    return false;
                }

                if (! $driver->hasGpsPosition()) {
                    DispatchLogger::driverFilter(
                        "Driver #{$driver->id} rejected: no coordinates client_gps={$clientGps} "
                        ."driver_gps={$driverGps} distance_km=n/a gps_age_seconds=".($gpsAgeSeconds ?? 'n/a')
                    );

                    return false;
                }

                if ($gpsAt === null || $gpsAt->lt(now()->subSeconds($freshnessSeconds))) {
                    DispatchLogger::driverFilter(
                        "Driver #{$driver->id} rejected: stale GPS client_gps={$clientGps} "
                        ."driver_gps={$driverGps} distance_km=n/a gps_age_seconds=".($gpsAgeSeconds ?? 'n/a')
                    );

                    return false;
                }

                if ($driver->status !== DriverStatus::Online) {
                    DispatchLogger::driverFilter(
                        "Driver #{$driver->id} rejected: offline client_gps={$clientGps} "
                        ."driver_gps={$driverGps} distance_km=n/a gps_age_seconds=".($gpsAgeSeconds ?? 'n/a')
                    );

                    return false;
                }

                if (! $driver->is_available) {
                    DispatchLogger::driverFilter(
                        "Driver #{$driver->id} rejected: unavailable client_gps={$clientGps} "
                        ."driver_gps={$driverGps} distance_km=n/a gps_age_seconds=".($gpsAgeSeconds ?? 'n/a')
                    );

                    return false;
                }

                $distanceKm = GeoDistance::kilometers(
                    $searchPoint->latitude,
                    $searchPoint->longitude,
                    (float) $driver->latitude,
                    (float) $driver->longitude,
                );

                if ($distanceKm > $maxKm) {
                    DispatchLogger::driverFilter(
                        "Driver #{$driver->id} rejected: out of radius client_gps={$clientGps} "
                        ."driver_gps={$driverGps} distance_km=".Number::format($distanceKm, 3)
                        ." gps_age_seconds=".($gpsAgeSeconds ?? 'n/a')
                    );
                    return false;
                }

                DispatchLogger::driverFilter(
                    "Driver #{$driver->id} eligible client_gps={$clientGps} driver_gps={$driverGps} "
                    ."distance_km=".Number::format($distanceKm, 3)
                    ." gps_age_seconds=".($gpsAgeSeconds ?? 'n/a')
                );

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
