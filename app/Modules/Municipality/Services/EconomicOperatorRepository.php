<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\EconomicOperator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EconomicOperatorRepository
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(?int $registeredBy = null, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->baseQuery($filters, $registeredBy)
            ->with(['category', 'sector', 'operationalZone', 'economicZone', 'arrondissement', 'registeredBy', 'attachments'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function mapQuery(array $filters = []): Builder
    {
        return $this->baseQuery($filters)
            ->where('is_active', true)
            ->with(['category']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function baseQuery(array $filters = [], ?int $registeredBy = null): Builder
    {
        $query = EconomicOperator::query();

        if ($registeredBy !== null) {
            $query->where('registered_by', $registeredBy);
        }

        if (! empty($filters['tax_status'])) {
            $query->where('current_tax_status', $filters['tax_status']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['sector_id'])) {
            $query->where('sector_id', (int) $filters['sector_id']);
        }

        if (! empty($filters['economic_zone_id'])) {
            $query->where('economic_zone_id', (int) $filters['economic_zone_id']);
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('commercial_name', 'like', $term)
                    ->orWhere('public_id', 'like', $term)
                    ->orWhere('responsible_name', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['bbox'])) {
            $parts = array_map('floatval', explode(',', (string) $filters['bbox']));
            if (count($parts) === 4) {
                [$swLng, $swLat, $neLng, $neLat] = $parts;
                $query->whereBetween('latitude', [$swLat, $neLat])
                    ->whereBetween('longitude', [$swLng, $neLng]);
            }
        }

        return $query;
    }
}
