<?php

namespace App\Http\Requests\Drivers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitDriverApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $currentYear = (int) date('Y');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'national_id_number' => ['required', 'string', 'max:50'],
            'driving_license_number' => ['required', 'string', 'max:50'],
            'vehicle_brand' => ['required', 'string', 'max:100'],
            'vehicle_model' => ['required', 'string', 'max:100'],
            'vehicle_color' => ['required', 'string', 'max:50'],
            'vehicle_year' => ['required', 'integer', 'min:1990', 'max:'.$currentYear],
            'plate_number' => ['required', 'string', 'max:20'],
            'vehicle_type' => ['required', 'string', Rule::in(['sedan', 'suv', 'taxi', 'van', 'moto'])],
            'driver_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'license_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'vehicle_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }
}
