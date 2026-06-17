<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\MunicipalityReport;
use Illuminate\Support\Facades\DB;

class MunicipalityReportReferenceGenerator
{
    public function next(): string
    {
        return DB::transaction(function (): string {
            $last = MunicipalityReport::query()
                ->where('reference', 'like', 'OWE-SIG-%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('reference');

            $sequence = 1;
            if (is_string($last) && preg_match('/OWE-SIG-(\d+)$/', $last, $matches)) {
                $sequence = (int) $matches[1] + 1;
            }

            return sprintf('OWE-SIG-%06d', $sequence);
        });
    }
}
