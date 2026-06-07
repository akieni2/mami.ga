<?php

namespace App\Services;

use App\Enums\DriverApplicationStatus;
use App\Enums\DriverStatus;
use App\Events\DriverApplicationApproved;
use App\Events\DriverApplicationRejected;
use App\Models\Driver;
use App\Models\DriverApplication;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\DriverApplicationApprovedNotification;
use App\Notifications\DriverApplicationRejectedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DriverEnrollmentService
{
    public function submit(User $user, array $data, UploadedFile $driverPhoto, UploadedFile $licensePhoto, UploadedFile $vehiclePhoto): DriverApplication
    {
        if ($user->isDriver()) {
            throw new RuntimeException('Vous êtes déjà chauffeur sur la plateforme.');
        }

        if ($user->isAdmin()) {
            throw new RuntimeException('Les administrateurs ne peuvent pas soumettre de candidature.');
        }

        $pending = DriverApplication::query()
            ->where('user_id', $user->id)
            ->where('status', DriverApplicationStatus::Pending)
            ->exists();

        if ($pending) {
            throw new RuntimeException('Une candidature est déjà en cours d\'examen.');
        }

        $this->assertUniqueIdentifiers(
            $data['driving_license_number'],
            $data['plate_number'],
        );

        $application = DB::transaction(function () use ($user, $data, $driverPhoto, $licensePhoto, $vehiclePhoto) {
            $application = DriverApplication::query()->create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'national_id_number' => $data['national_id_number'],
                'driving_license_number' => $data['driving_license_number'],
                'vehicle_brand' => $data['vehicle_brand'],
                'vehicle_model' => $data['vehicle_model'],
                'vehicle_color' => $data['vehicle_color'],
                'vehicle_year' => (int) $data['vehicle_year'],
                'plate_number' => $data['plate_number'],
                'vehicle_type' => $data['vehicle_type'],
                'status' => DriverApplicationStatus::Pending,
            ]);

            $basePath = 'driver-applications/'.$application->id;

            $application->update([
                'driver_photo_path' => $this->storePhoto($driverPhoto, $basePath.'/driver'),
                'license_photo_path' => $this->storePhoto($licensePhoto, $basePath.'/license'),
                'vehicle_photo_path' => $this->storePhoto($vehiclePhoto, $basePath.'/vehicle'),
            ]);

            return $application->fresh();
        });

        return $application;
    }

    public function approve(DriverApplication $application, User $admin): DriverApplication
    {
        $this->assertReviewable($application);
        $this->assertUniqueIdentifiers(
            $application->driving_license_number,
            $application->plate_number,
            $application->id,
        );

        return DB::transaction(function () use ($application, $admin) {
            $user = $this->resolveApplicantUser($application);

            $user->update([
                'name' => $application->fullName(),
                'phone' => $application->phone,
                'email' => $application->email,
            ]);

            if (! $user->isDriver()) {
                Driver::query()->create([
                    'user_id' => $user->id,
                    'license_number' => $application->driving_license_number,
                    'is_available' => false,
                    'status' => DriverStatus::Offline,
                    'rating' => 5.0,
                ]);
            }

            $driver = $user->driver()->firstOrFail();

            if ($driver->vehicle === null) {
                Vehicle::query()->create([
                    'driver_id' => $driver->id,
                    'brand' => $application->vehicle_brand,
                    'model' => $application->vehicle_model,
                    'plate_number' => $application->plate_number,
                    'color' => $application->vehicle_color,
                    'year' => $application->vehicle_year,
                ]);
            }

            $application->update([
                'user_id' => $user->id,
                'status' => DriverApplicationStatus::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $application = $application->fresh(['user', 'reviewer']);

            $user->notify(new DriverApplicationApprovedNotification($application));
            event(new DriverApplicationApproved($application));

            return $application;
        });
    }

    public function reject(DriverApplication $application, User $admin, string $rejectionReason): DriverApplication
    {
        $this->assertReviewable($application);

        $application->update([
            'status' => DriverApplicationStatus::Rejected,
            'rejection_reason' => $rejectionReason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $application = $application->fresh(['user', 'reviewer']);

        if ($application->user) {
            $application->user->notify(new DriverApplicationRejectedNotification($application));
            event(new DriverApplicationRejected($application));
        }

        return $application;
    }

    private function resolveApplicantUser(DriverApplication $application): User
    {
        if ($application->user !== null) {
            return $application->user;
        }

        $user = User::query()->firstOrCreate(
            ['email' => $application->email],
            [
                'name' => $application->fullName(),
                'phone' => $application->phone,
                'password' => Hash::make(Str::password(16)),
            ],
        );

        $application->update(['user_id' => $user->id]);

        return $user->fresh();
    }

    private function assertReviewable(DriverApplication $application): void
    {
        if ($application->status !== DriverApplicationStatus::Pending) {
            throw new RuntimeException('Cette candidature a déjà été traitée.');
        }
    }

    private function assertUniqueIdentifiers(string $licenseNumber, string $plateNumber, ?int $excludeApplicationId = null): void
    {
        if (Driver::query()->where('license_number', $licenseNumber)->exists()) {
            throw new RuntimeException('Ce numéro de permis est déjà enregistré.');
        }

        if (Vehicle::query()->where('plate_number', $plateNumber)->exists()) {
            throw new RuntimeException('Cette plaque est déjà enregistrée.');
        }

        $pendingConflict = DriverApplication::query()
            ->where('status', DriverApplicationStatus::Pending)
            ->when($excludeApplicationId !== null, fn ($q) => $q->where('id', '!=', $excludeApplicationId))
            ->where(function ($query) use ($licenseNumber, $plateNumber) {
                $query->where('driving_license_number', $licenseNumber)
                    ->orWhere('plate_number', $plateNumber);
            })
            ->exists();

        if ($pendingConflict) {
            throw new RuntimeException('Une candidature en attente utilise déjà ce permis ou cette plaque.');
        }
    }

    private function storePhoto(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'public');
    }
}
