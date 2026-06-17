<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\EconomicOperator;
use Illuminate\Support\Facades\DB;

class EconomicOperatorReferenceGenerator
{
    public function next(): string
    {
        return DB::transaction(function (): string {
            $last = EconomicOperator::query()
                ->where('public_id', 'like', 'OWE-COM-%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('public_id');

            $sequence = 1;
            if (is_string($last) && preg_match('/OWE-COM-(\d+)$/', $last, $matches)) {
                $sequence = (int) $matches[1] + 1;
            }

            return sprintf('OWE-COM-%06d', $sequence);
        });
    }
}
