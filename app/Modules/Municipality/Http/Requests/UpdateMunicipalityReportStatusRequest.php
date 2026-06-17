<?php

namespace App\Modules\Municipality\Http\Requests;

use App\Modules\Municipality\Enums\ReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMunicipalityReportStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');

        return $report !== null && $this->user()?->can('updateStatus', $report);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ReportStatus::values())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
