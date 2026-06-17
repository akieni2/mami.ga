<?php

namespace App\Modules\Municipality\Services;

use App\Modules\Municipality\Enums\ReportStatus;
use App\Modules\Municipality\Models\MunicipalityReport;
use Illuminate\Database\Eloquent\Builder;

class LayerSignalements
{
    public function __construct(
        private readonly MunicipalityReportRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function toGeoJson(array $filters = []): array
    {
        $reports = $this->repository
            ->mapQuery($filters)
            ->get();

        return [
            'type' => 'FeatureCollection',
            'features' => $reports->map(fn (MunicipalityReport $report): array => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $report->longitude, (float) $report->latitude],
                ],
                'properties' => [
                    'id' => $report->id,
                    'reference' => $report->reference,
                    'category' => $report->category->value,
                    'status' => $report->status->value,
                    'title' => $report->title,
                    'color' => $report->status->mapColor(),
                    'created_at' => $report->created_at?->toIso8601String(),
                ],
            ])->values()->all(),
        ];
    }
}
