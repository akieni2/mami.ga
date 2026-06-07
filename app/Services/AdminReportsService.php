<?php

namespace App\Services;

use App\Enums\RideStatus;
use App\Models\Ride;
use Illuminate\Support\Carbon;

class AdminReportsService
{
    /**
     * @return array{period: string, from: string, to: string, rides_total: int, rides_completed: int, rides_cancelled: int, estimated_revenue: float, active_drivers_peak: int}
     */
    public function summary(string $period = 'day'): array
    {
        [$from, $to] = $this->rangeForPeriod($period);

        $query = Ride::query()->whereBetween('created_at', [$from, $to]);

        return [
            'period' => $period,
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'rides_total' => (clone $query)->count(),
            'rides_completed' => (clone $query)->where('status', RideStatus::Completed)->count(),
            'rides_cancelled' => (clone $query)->where('status', RideStatus::Cancelled)->count(),
            'estimated_revenue' => (float) (clone $query)
                ->where('status', RideStatus::Completed)
                ->sum('estimated_price'),
            'rides_by_status' => $this->countsByStatus($from, $to),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function countsByStatus(Carbon $from, Carbon $to): array
    {
        return Ride::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn ($count, $status) => [(string) $status => (int) $count])
            ->all();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangeForPeriod(string $period): array
    {
        $to = Carbon::now();

        return match ($period) {
            'week' => [Carbon::now()->startOfWeek(), $to],
            'month' => [Carbon::now()->startOfMonth(), $to],
            default => [Carbon::today()->startOfDay(), $to],
        };
    }
}
