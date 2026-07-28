<?php

namespace App\Modules\JbLudo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\JbLudo\Http\Resources\PlayerProfileResource;
use App\Modules\JbLudo\Models\PlayerProfile;
use App\Modules\JbLudo\Services\PlayerProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly PlayerProfileService $profiles,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->profiles->forUser($request->user());

        $items = PlayerProfile::query()
            ->where('is_suspended', false)
            ->orderByDesc('points')
            ->orderBy('id')
            ->limit(100)
            ->get();

        return ApiResponse::success(PlayerProfileResource::collection($items));
    }
}
