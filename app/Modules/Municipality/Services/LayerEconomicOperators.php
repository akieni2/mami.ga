<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Models\EconomicOperator;

class LayerEconomicOperators
{
    public function __construct(
        private readonly EconomicOperatorRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function toGeoJson(array $filters = []): array
    {
        $operators = $this->repository
            ->mapQuery($filters)
            ->get();

        return [
            'type' => 'FeatureCollection',
            'features' => $operators->map(fn (EconomicOperator $operator): array => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $operator->longitude, (float) $operator->latitude],
                ],
                'properties' => [
                    'id' => $operator->id,
                    'public_id' => $operator->public_id,
                    'commercial_name' => $operator->commercial_name,
                    'category' => $operator->category?->slug,
                    'category_label' => $operator->category?->name,
                    'tax_status' => $operator->current_tax_status->value,
                    'tax_status_label' => $operator->current_tax_status->label(),
                    'color' => $operator->current_tax_status->mapColor(),
                    'sector_id' => $operator->sector_id,
                    'economic_zone_id' => $operator->economic_zone_id,
                    'created_at' => $operator->created_at?->toIso8601String(),
                ],
            ])->values()->all(),
        ];
    }
}
