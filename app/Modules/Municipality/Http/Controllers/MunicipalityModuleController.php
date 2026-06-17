<?php

namespace App\Modules\Municipality\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class MunicipalityModuleController extends Controller
{
    public function status(): JsonResponse
    {
        return ApiResponse::success([
            'module' => 'municipality',
            'status' => 'active',
            'version' => 'v2.5-economic-foundation',
            'message' => 'Recensement économique + fondations V3 fiscalité (QR, visites, quittances)',
        ], 'Municipality module status');
    }
}
