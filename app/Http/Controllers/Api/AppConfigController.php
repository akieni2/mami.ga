<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\MamiFeatures;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    public function features(): JsonResponse
    {
        return ApiResponse::success(
            MamiFeatures::publicConfig(),
            'App features retrieved',
        );
    }
}
