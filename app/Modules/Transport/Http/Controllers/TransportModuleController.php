<?php

namespace App\Modules\Transport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TransportModuleController extends Controller
{
    public function status(): JsonResponse
    {
        return ApiResponse::success([
            'module' => 'transport',
            'status' => 'scaffold',
            'message' => 'Module Transport — activation via MAMI_MODULE_TRANSPORT.',
        ], 'Transport module status');
    }
}
