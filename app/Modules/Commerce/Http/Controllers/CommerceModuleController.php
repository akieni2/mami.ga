<?php

namespace App\Modules\Commerce\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CommerceModuleController extends Controller
{
    public function status(): JsonResponse
    {
        return ApiResponse::success([
            'module' => 'commerce',
            'status' => 'scaffold',
            'message' => 'Module Commerce — activation via MAMI_MODULE_COMMERCE.',
        ], 'Commerce module status');
    }
}
