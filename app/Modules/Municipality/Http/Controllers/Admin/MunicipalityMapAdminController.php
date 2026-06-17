<?php

namespace App\Modules\Municipality\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Services\LayerEconomicOperators;
use App\Modules\Municipality\Services\LayerSignalements;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MunicipalityMapAdminController extends Controller
{
    public function __construct(
        private readonly LayerSignalements $layerSignalements,
        private readonly LayerEconomicOperators $layerEconomicOperators,
    ) {}

    public function index(): View
    {
        return view('admin.municipality.map.index');
    }

    public function geojson(): JsonResponse
    {
        $filters = request()->only([
            'status', 'category', 'sector_id', 'quartier', 'date_from', 'date_to', 'bbox',
            'tax_status', 'category_id', 'economic_zone_id', 'layer',
        ]);

        $layer = $filters['layer'] ?? 'signalements';

        if ($layer === 'economic_operators') {
            return response()->json($this->layerEconomicOperators->toGeoJson($filters));
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $this->layerSignalements->toGeoJson($filters)['features'],
        ]);
    }
}
