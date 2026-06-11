<?php

namespace App\Services;

use App\Enums\BookingType;
use App\Enums\LocationSource;
use App\Enums\PaymentMethod;
use App\Enums\RideStatus;
use App\Models\Ride;
use App\Models\User;
use App\Support\LocationSourceResolver;
use RuntimeException;

/**
 * P2A/P2B — création course text-first (sans dispatch).
 */
class RideBookingService
{
    public function __construct(
        private readonly RideEstimateService $rideEstimateService,
    ) {}

    public function createTextBooking(
        User $client,
        string $pickupLabel,
        string $destinationLabel,
        float $proposedPrice,
        PaymentMethod $paymentMethod,
        ?float $pickupLatitude = null,
        ?float $pickupLongitude = null,
        ?float $destinationLatitude = null,
        ?float $destinationLongitude = null,
    ): Ride {
        if ($client->isDriver()) {
            throw new RuntimeException('Drivers cannot request rides as clients.');
        }

        $pickupSource = LocationSourceResolver::resolve(
            $pickupLabel,
            $pickupLatitude,
            $pickupLongitude,
        );

        $destinationSource = LocationSourceResolver::resolve(
            $destinationLabel,
            $destinationLatitude,
            $destinationLongitude,
        );

        $suggestedPrice = null;
        $distanceKm = null;
        $durationMinutes = null;

        if ($pickupLatitude !== null
            && $pickupLongitude !== null
            && $destinationLatitude !== null
            && $destinationLongitude !== null
        ) {
            $estimate = $this->rideEstimateService->estimate(
                $pickupLatitude,
                $pickupLongitude,
                $destinationLatitude,
                $destinationLongitude,
            );

            $suggestedPrice = $estimate['suggested_price'];
            $distanceKm = $estimate['distance_km'];
            $durationMinutes = $estimate['duration_minutes'];
        }

        $searchHours = (int) config('mami.search_max_duration_hours', 2);

        return Ride::query()->create([
            'client_id' => $client->id,
            'driver_id' => null,
            'pickup_label' => $pickupLabel,
            'destination_label' => $destinationLabel,
            'pickup_source' => $pickupSource,
            'destination_source' => $destinationSource,
            'pickup_latitude' => $pickupLatitude,
            'pickup_longitude' => $pickupLongitude,
            'destination_latitude' => $destinationLatitude,
            'destination_longitude' => $destinationLongitude,
            'status' => RideStatus::Searching,
            'booking_type' => BookingType::Immediate,
            'proposed_price' => $proposedPrice,
            'payment_method' => $paymentMethod,
            'suggested_price' => $suggestedPrice,
            'distance_km' => $distanceKm,
            'duration_minutes' => $durationMinutes,
            'dispatch_expires_at' => now()->addHours($searchHours),
        ]);
    }
}
