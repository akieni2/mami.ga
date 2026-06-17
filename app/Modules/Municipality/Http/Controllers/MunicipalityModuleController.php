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
            'version' => 'v1-signalements',
            'message' => 'Signalements citoyens géolocalisés — Owendo V1',
        ], 'Municipality module status');
    }
}
