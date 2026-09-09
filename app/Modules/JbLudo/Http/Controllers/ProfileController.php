<?php

namespace App\Modules\JbLudo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\JbLudo\Enums\PlayerLevel;
use App\Modules\JbLudo\Http\Resources\PlayerProfileResource;
use App\Modules\JbLudo\Models\PlayerProfile;
use App\Modules\JbLudo\Services\PlayerProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly PlayerProfileService $profiles,
    ) {}

    public function me(Request $request): JsonResponse
    {
        $profile = PlayerProfile::query()->where('user_id', $request->user()->id)->first();

        if ($profile === null) {
            return ApiResponse::success(null, 'Profil absent');
        }

        return ApiResponse::success(new PlayerProfileResource($profile));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:80'],
            'last_name' => ['required', 'string', 'min:2', 'max:80'],
            'pseudo' => ['required', 'string', 'min:2', 'max:40'],
            'phone' => ['required', 'string', 'min:8', 'max:30'],
            'city' => ['nullable', 'string', 'max:80'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'level' => ['nullable', 'in:'.implode(',', PlayerLevel::values())],
            'club' => ['nullable', 'string', 'max:120'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store(
                'jb-ludo/profiles/'.$request->user()->id,
                'public',
            );
        }

        $profile = $this->profiles->upsert($request->user(), $data);

        return ApiResponse::success(new PlayerProfileResource($profile), 'Profil enregistré', 201);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $profile = $this->profiles->forUser($request->user());
        $this->profiles->setOnline($profile, true);

        return ApiResponse::success(['is_online' => true]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->profiles->forUser($request->user());

        $q = trim((string) $request->query('q', ''));
        $items = PlayerProfile::query()
            ->where('is_suspended', false)
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q): void {
                $inner->where('pseudo', 'like', '%'.$q.'%')
                    ->orWhere('first_name', 'like', '%'.$q.'%')
                    ->orWhere('last_name', 'like', '%'.$q.'%')
                    ->orWhere('city', 'like', '%'.$q.'%')
                    ->orWhere('neighborhood', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%');
            }))
            ->orderBy('pseudo')
            ->limit(30)
            ->get();

        return ApiResponse::success(PlayerProfileResource::collection($items));
    }
}
