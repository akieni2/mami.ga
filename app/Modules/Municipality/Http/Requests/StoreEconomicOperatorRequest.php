<?php

namespace App\Modules\Municipality\Http\Requests;

use App\Modules\Municipality\Enums\SyncStatus;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEconomicOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', EconomicOperator::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxAccuracy = (float) config('municipality.gps_max_accuracy_m', 20);

        return [
            'commercial_name' => ['required', 'string', 'max:255'],
            'activity_label' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', Rule::exists(EconomicOperatorCategory::class, 'id')],
            'responsible_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'gps_accuracy_m' => ['required', 'numeric', 'min:0', 'max:'.$maxAccuracy],
            'gps_captured_at' => ['nullable', 'date'],
            'location_confirmed' => ['required', 'accepted'],
            'sync_status' => ['nullable', Rule::in(SyncStatus::values())],
            'facade' => ['required', 'image', 'max:5120'],
            'trade_registry' => ['nullable', 'image', 'max:5120'],
            'business_license' => ['nullable', 'image', 'max:5120'],
            'municipal_authorization' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gps_accuracy_m.max' => 'Position GPS insuffisamment précise. Veuillez patienter.',
            'location_confirmed.accepted' => 'Vous devez confirmer l\'emplacement sur la carte.',
            'facade.required' => 'La photo de façade est obligatoire.',
        ];
    }
}
