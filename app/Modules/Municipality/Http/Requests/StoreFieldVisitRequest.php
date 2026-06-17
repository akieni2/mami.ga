<?php

namespace App\Modules\Municipality\Http\Requests;

use App\Modules\Municipality\Enums\VisitType;
use App\Modules\Municipality\Models\EconomicOperator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFieldVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $operator = $this->route('operator');

        return $operator instanceof EconomicOperator
            && ($this->user()?->can('inspect', $operator) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'visit_type' => ['required', Rule::in(VisitType::values())],
            'visit_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
