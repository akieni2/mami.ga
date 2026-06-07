<?php

namespace Tests\Feature;

use App\Enums\DriverApplicationStatus;
use App\Models\Driver;
use App\Models\DriverApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverApplicationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(): array
    {
        return [
            'first_name' => 'Jean',
            'last_name' => 'Obame',
            'phone' => '+241061234567',
            'email' => 'jean.candidat@mami.ga',
            'national_id_number' => 'NID-12345678',
            'driving_license_number' => 'GA-9999-AB',
            'vehicle_brand' => 'Toyota',
            'vehicle_model' => 'Corolla',
            'vehicle_color' => 'Blanc',
            'vehicle_year' => 2020,
            'plate_number' => 'GA-888-CD',
            'vehicle_type' => 'sedan',
        ];
    }

    public function test_authenticated_user_can_submit_driver_application(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/driver-applications', array_merge($this->validPayload(), [
            'driver_photo' => UploadedFile::fake()->image('driver.jpg'),
            'license_photo' => UploadedFile::fake()->image('license.jpg'),
            'vehicle_photo' => UploadedFile::fake()->image('vehicle.jpg'),
        ]), ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', DriverApplicationStatus::Pending->value);

        $this->assertDatabaseHas('driver_applications', [
            'user_id' => $user->id,
            'email' => 'jean.candidat@mami.ga',
            'status' => DriverApplicationStatus::Pending->value,
        ]);
    }

    public function test_submission_requires_valid_documents(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/driver-applications', $this->validPayload());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['driver_photo', 'license_photo', 'vehicle_photo']);
    }

    public function test_existing_driver_cannot_submit_application(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Driver::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->post('/api/driver-applications', array_merge($this->validPayload(), [
            'driver_photo' => UploadedFile::fake()->image('driver.jpg'),
            'license_photo' => UploadedFile::fake()->image('license.jpg'),
            'vehicle_photo' => UploadedFile::fake()->image('vehicle.jpg'),
        ]), ['Accept' => 'application/json']);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_user_can_fetch_application_status(): void
    {
        $user = User::factory()->create();
        DriverApplication::factory()->create([
            'user_id' => $user->id,
            'status' => DriverApplicationStatus::Pending,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/driver-applications/status')
            ->assertOk()
            ->assertJsonPath('data.status', DriverApplicationStatus::Pending->value);
    }

    public function test_guest_cannot_submit_application(): void
    {
        $this->postJson('/api/driver-applications', $this->validPayload())
            ->assertUnauthorized();
    }
}
