<?php

namespace App\Modules\Municipality\Http\Requests;

use App\Modules\Municipality\Models\EconomicOperator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEconomicOperatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $operator = $this->route('operator');

        return $operator instanceof EconomicOperator
            && ($this->user()?->can('update', $operator) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'commercial_name' => ['sometimes', 'string', 'max:255'],
            'activity_label' => ['sometimes', 'string', 'max:255'],
            'responsible_name' => ['sometimes', 'string', 'max:150'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
        ];
    }
}
