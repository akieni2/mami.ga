<?php

namespace App\Modules\Municipality\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignMunicipalityReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');

        return $report !== null && $this->user()?->can('assign', $report);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
