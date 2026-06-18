<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\CashSession;
use Illuminate\Support\Facades\DB;

class CashSessionReferenceGenerator
{
    public function next(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        return DB::transaction(function () use ($year): string {
            $prefix = sprintf('OWE-CS-%d-', $year);

            $last = CashSession::query()
                ->where('reference', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('reference');

            $sequence = 1;
            if (is_string($last) && preg_match('/OWE-CS-\d{4}-(\d+)$/', $last, $matches)) {
                $sequence = (int) $matches[1] + 1;
            }

            return sprintf('OWE-CS-%d-%06d', $year, $sequence);
        });
    }
}
