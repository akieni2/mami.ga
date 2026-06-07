<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drivers\SubmitDriverApplicationRequest;
use App\Http\Resources\DriverApplicationResource;
use App\Models\DriverApplication;
use App\Services\DriverEnrollmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DriverApplicationController extends Controller
{
    public function __construct(
        private readonly DriverEnrollmentService $enrollmentService,
    ) {}

    public function store(SubmitDriverApplicationRequest $request): JsonResponse
    {
        try {
            $application = $this->enrollmentService->submit(
                $request->user(),
                $request->validated(),
                $request->file('driver_photo'),
                $request->file('license_photo'),
                $request->file('vehicle_photo'),
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(
            new DriverApplicationResource($application),
            'Candidature soumise avec succès.',
            201,
        );
    }

    public function status(Request $request): JsonResponse
    {
        $application = DriverApplication::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->first();

        if ($application === null) {
            return ApiResponse::success([
                'status' => null,
                'application' => null,
            ], 'Aucune candidature trouvée.');
        }

        return ApiResponse::success([
            'status' => $application->status->value,
            'application' => new DriverApplicationResource($application),
        ], 'Statut de candidature récupéré.');
    }
}
