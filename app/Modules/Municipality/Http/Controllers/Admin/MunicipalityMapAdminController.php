<?php

namespace App\Modules\Municipality\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Municipality\Services\LayerSignalements;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MunicipalityMapAdminController extends Controller
{
    public function __construct(
        private readonly LayerSignalements $layerSignalements,
    ) {}

    public function index(): View
    {
        return view('admin.municipality.map.index');
    }

    public function geojson(): JsonResponse
    {
        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $this->layerSignalements->toGeoJson(request()->only([
                'status', 'category', 'sector_id', 'date_from', 'date_to', 'bbox',
            ]))['features'],
        ]);
    }
}
