<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\FiscalObligation;
use Illuminate\Support\Facades\DB;

class FiscalObligationReferenceGenerator
{
    public function next(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        return DB::transaction(function () use ($year): string {
            $prefix = sprintf('OWE-FO-%d-', $year);

            $last = FiscalObligation::query()
                ->where('reference', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('reference');

            $sequence = 1;
            if (is_string($last) && preg_match('/OWE-FO-\d{4}-(\d+)$/', $last, $matches)) {
                $sequence = (int) $matches[1] + 1;
            }

            return sprintf('OWE-FO-%d-%06d', $year, $sequence);
        });
    }
}
