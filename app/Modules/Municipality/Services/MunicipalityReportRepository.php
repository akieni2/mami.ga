<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\MunicipalityReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MunicipalityReportRepository
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForUser(?int $citizenId, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->applyFilters($this->baseQuery(), $filters, $citizenId)
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function mapQuery(array $filters): Builder
    {
        return $this->applyFilters($this->baseQuery(), $filters);
    }

    public function findOrFail(int $id): MunicipalityReport
    {
        return $this->baseQuery()->findOrFail($id);
    }

    private function baseQuery(): Builder
    {
        return MunicipalityReport::query()
            ->with(['citizen', 'sector', 'operationalZone', 'assignee', 'attachments']);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters, ?int $citizenId = null): Builder
    {
        if ($citizenId !== null) {
            $query->where('citizen_id', $citizenId);
        }

        if (! empty($filters['mine'])) {
            $query->where('citizen_id', $filters['citizen_id'] ?? $citizenId);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['sector_id'])) {
            $query->where('sector_id', $filters['sector_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['bbox']) && is_string($filters['bbox'])) {
            $parts = array_map('floatval', explode(',', $filters['bbox']));
            if (count($parts) === 4) {
                [$swLat, $swLng, $neLat, $neLng] = $parts;
                $query->whereBetween('latitude', [$swLat, $neLat])
                    ->whereBetween('longitude', [$swLng, $neLng]);
            }
        }

        return $query;
    }
}
