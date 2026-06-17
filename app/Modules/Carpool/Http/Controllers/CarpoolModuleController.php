<?php

namespace App\Modules\Carpool\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CarpoolModuleController extends Controller
{
    public function status(): JsonResponse
    {
        return ApiResponse::success([
            'module' => 'carpool',
            'status' => 'scaffold',
            'message' => 'Module Covoiturage — activation progressive via MAMI_MODULE_CARPOOL.',
        ], 'Carpool module status');
    }
}
