<?php

namespace App\Services;

use App\Enums\DriverStatus;
use App\Enums\RideEventType;
use App\Enums\RideOfferStatus;
use App\Enums\RideStatus;
use App\Events\RideOfferAccepted;
use App\Events\RideOfferCreated;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\RideOffer;
use App\Support\Dispatch\DispatchLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RideOfferService
{
    public function __construct(
        private readonly RideEventRecorder $rideEventRecorder,
    ) {}

    public function createOffer(
        Ride $ride,
        Driver $driver,
        float $distanceKm,
        float $score,
        string $radiusWave,
    ): RideOffer {
        $timeoutSeconds = (int) config('mami.offer_timeout_seconds', 30);

        $offer = RideOffer::query()->create([
            'ride_id' => $ride->id,
            'driver_id' => $driver->id,
            'status' => RideOfferStatus::Pending,
            'offered_price' => (float) $ride->proposed_price,
            'distance_to_pickup_km' => $distanceKm,
            'dispatch_score' => $score,
            'radius_wave' => $radiusWave,
            'expires_at' => Carbon::now()->addSeconds($timeoutSeconds),
        ]);

        $offer->load(['ride.client', 'driver.user', 'driver.vehicle']);

        DispatchLogger::offer("Ride #{$ride->id} offered to driver #{$driver->id}");

        RideOfferCreated::dispatch($offer);

        return $offer;
    }

    public function accept(RideOffer $offer, Driver $driver): Ride
    {
        return DB::transaction(function () use ($offer, $driver) {
            $offer = RideOffer::query()->lockForUpdate()->findOrFail($offer->id);
            $ride = Ride::query()->lockForUpdate()->findOrFail($offer->ride_id);

            if ($offer->driver_id !== $driver->id) {
                throw new RuntimeException('This offer does not belong to you.');
            }

            if ($offer->status !== RideOfferStatus::Pending) {
                throw new RuntimeException('Offer is no longer available.');
            }

            if ($offer->expires_at->isPast()) {
                $offer->update([
                    'status' => RideOfferStatus::Expired,
                    'responded_at' => now(),
                ]);

                throw new RuntimeException('Offer has expired.');
            }

            if ($ride->status !== RideStatus::Searching) {
                throw new RuntimeException('Ride is no longer searching for a driver.');
            }

            $offer->update([
                'status' => RideOfferStatus::Accepted,
                'responded_at' => now(),
            ]);

            RideOffer::query()
                ->where('ride_id', $ride->id)
                ->where('id', '!=', $offer->id)
                ->where('status', RideOfferStatus::Pending)
                ->update([
                    'status' => RideOfferStatus::Expired,
                    'responded_at' => now(),
                ]);

            $ride->update([
                'driver_id' => $driver->id,
                'status' => RideStatus::Accepted,
                'agreed_price' => $ride->proposed_price,
                'accepted_at' => now(),
            ]);

            $driver->update([
                'is_available' => false,
                'status' => DriverStatus::OnRide,
            ]);

            $ride = $ride->fresh(['client', 'driver.user', 'driver.vehicle']);

            DispatchLogger::accept("Ride #{$ride->id} accepted by driver #{$driver->id}");

            $this->rideEventRecorder->record($ride, RideEventType::RideAccepted, [
                'offer_id' => $offer->id,
                'agreed_price' => $ride->agreed_price,
            ]);

            RideOfferAccepted::dispatch($offer->fresh(['ride.client', 'driver.user']), $ride);

            return $ride;
        });
    }

    public function reject(RideOffer $offer, Driver $driver): RideOffer
    {
        if ($offer->driver_id !== $driver->id) {
            throw new RuntimeException('This offer does not belong to you.');
        }

        if ($offer->status !== RideOfferStatus::Pending) {
            throw new RuntimeException('Offer cannot be rejected in its current status.');
        }

        $offer->update([
            'status' => RideOfferStatus::Rejected,
            'responded_at' => now(),
        ]);

        DispatchLogger::offer("Ride #{$offer->ride_id} offer #{$offer->id} rejected by driver #{$driver->id}");

        return $offer->fresh();
    }

    /**
     * @return \Illuminate\Support\Collection<int, RideOffer>
     */
    public function pendingOffersForDriver(Driver $driver)
    {
        $staleCount = RideOffer::query()
            ->where('driver_id', $driver->id)
            ->where('status', RideOfferStatus::Pending)
            ->where('expires_at', '<=', now())
            ->count();

        if ($staleCount > 0) {
            DispatchLogger::offersApi(
                "driver #{$driver->id} has {$staleCount} time-expired pending offer(s) hidden from API",
            );
        }

        return RideOffer::query()
            ->with(['ride.client'])
            ->where('driver_id', $driver->id)
            ->where('status', RideOfferStatus::Pending)
            ->where('expires_at', '>', now())
            ->whereHas('ride', fn ($q) => $q->where('status', RideStatus::Searching))
            ->latest('id')
            ->get();
    }

    public function expirePendingOffers(Ride $ride): int
    {
        return RideOffer::query()
            ->where('ride_id', $ride->id)
            ->where('status', RideOfferStatus::Pending)
            ->update([
                'status' => RideOfferStatus::Expired,
                'responded_at' => now(),
            ]);
    }
}
