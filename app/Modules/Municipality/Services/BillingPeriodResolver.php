<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Enums\BillingPeriod;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class BillingPeriodResolver
{
    /**
     * @return array{start: CarbonInterface, end: CarbonInterface, label: string}
     */
    public function currentPeriod(BillingPeriod $period, ?CarbonInterface $on = null): array
    {
        $on ??= now();
        $date = Carbon::parse($on->toDateString());

        return match ($period) {
            BillingPeriod::Monthly => [
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
                'label' => $date->translatedFormat('F Y'),
            ],
            BillingPeriod::Quarterly => [
                'start' => $date->copy()->firstOfQuarter(),
                'end' => $date->copy()->lastOfQuarter(),
                'label' => 'T'.$date->quarter.' '.$date->year,
            ],
            BillingPeriod::Semiannual => $date->month <= 6
                ? [
                    'start' => $date->copy()->month(1)->startOfMonth(),
                    'end' => $date->copy()->month(6)->endOfMonth(),
                    'label' => 'S1 '.$date->year,
                ]
                : [
                    'start' => $date->copy()->month(7)->startOfMonth(),
                    'end' => $date->copy()->month(12)->endOfMonth(),
                    'label' => 'S2 '.$date->year,
                ],
            BillingPeriod::Annual => [
                'start' => $date->copy()->startOfYear(),
                'end' => $date->copy()->endOfYear(),
                'label' => 'Année '.$date->year,
            ],
        };
    }

    public function dueDate(CarbonInterface $periodEnd, int $dueDayOfPeriod = 1): CarbonInterface
    {
        $due = Carbon::parse($periodEnd->toDateString());

        return $due->day(min($dueDayOfPeriod, $due->daysInMonth));
    }
}
