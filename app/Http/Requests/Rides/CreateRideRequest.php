<?php

namespace App\Http\Requests\Rides;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateRideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->isTextBooking()) {
            return $this->textBookingRules();
        }

        return $this->legacyGpsRules();
    }

    public function isTextBooking(): bool
    {
        return $this->filled('pickup_label') || $this->filled('destination_label');
    }

    /**
     * @return array<string, mixed>
     */
    private function textBookingRules(): array
    {
        $minPrice = (int) config('mami.min_proposed_price', 500);
        $maxPrice = (int) config('mami.max_proposed_price', 500000);
        $pickupMin = (int) config('mami.pickup_label_min_length', 3);
        $destMin = (int) config('mami.destination_label_min_length', 3);

        return [
            'pickup_label' => ['required', 'string', "min:{$pickupMin}", 'max:255'],
            'destination_label' => ['required', 'string', "min:{$destMin}", 'max:255'],
            'proposed_price' => ['required', 'numeric', "min:{$minPrice}", "max:{$maxPrice}"],
            'payment_method' => [
                'required',
                Rule::in([
                    PaymentMethod::Cash->value,
                    PaymentMethod::AirtelMoney->value,
                    PaymentMethod::MoovMoney->value,
                ]),
            ],
            'pickup_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'destination_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyGpsRules(): array
    {
        return [
            'pickup_latitude' => ['required', 'numeric', 'between:-90,90'],
            'pickup_longitude' => ['required', 'numeric', 'between:-180,180'],
            'destination_latitude' => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
