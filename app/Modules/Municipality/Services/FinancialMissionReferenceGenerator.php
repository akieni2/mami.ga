<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\FinancialMission;
use Illuminate\Support\Str;

class FinancialMissionReferenceGenerator
{
    public function next(): string
    {
        $year = now()->year;
        $prefix = "OWE-FM-{$year}-";

        $last = FinancialMission::query()
            ->where('reference', 'like', $prefix.'%')
            ->orderByDesc('reference')
            ->value('reference');

        $sequence = 1;
        if ($last !== null && preg_match('/-(\d{6})$/', $last, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.Str::padLeft((string) $sequence, 6, '0');
    }
}
