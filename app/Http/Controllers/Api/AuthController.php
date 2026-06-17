<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $request->input('phone'),
            'password' => $request->string('password')->toString(),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return ApiResponse::success([
            'user' => $this->userPayload($user),
            'token' => $token,
        ], 'User registered successfully', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->with(['driver.vehicle', 'roles.permissions'])
            ->where('email', $request->string('email')->toString())
            ->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return ApiResponse::success([
            'user' => $this->userPayload($user),
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['driver.vehicle', 'roles.permissions']);

        return ApiResponse::success([
            'user' => $this->userPayload($user),
        ], 'Current user retrieved');
    }

    private function userPayload(User $user): array
    {
        $roles = $user->relationLoaded('roles')
            ? $user->roles->pluck('slug')->values()->all()
            : [];

        $permissions = $user->relationLoaded('roles')
            ? $user->roles
                ->flatMap(fn ($role) => $role->permissions)
                ->pluck('slug')
                ->unique()
                ->values()
                ->all()
            : [];

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_driver' => $user->relationLoaded('driver') ? $user->driver !== null : $user->isDriver(),
            'roles' => $roles,
            'permissions' => $permissions,
            'driver' => $user->relationLoaded('driver') && $user->driver
                ? (new \App\Http\Resources\DriverResource($user->driver))->resolve()
                : null,
        ];
    }
}
