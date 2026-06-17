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
            'status' => 'scaffold',
            'message' => 'Module Municipalité — activation via MAMI_MODULE_MUNICIPALITY.',
        ], 'Municipality module status');
    }
}
