<?php

namespace App\Modules\Municipality\Http\Requests;

use App\Modules\Municipality\Models\EconomicOperator;
use Illuminate\Foundation\Http\FormRequest;

class InspectEconomicOperatorRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
