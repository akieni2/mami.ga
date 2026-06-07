<?php

namespace Tests\Feature;

use App\Enums\DriverApplicationStatus;
use App\Events\DriverApplicationApproved;
use App\Models\Driver;
use App\Models\DriverApplication;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\DriverApplicationApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DriverApplicationApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_pending_application(): void
    {
        Notification::fake();
        Event::fake([DriverApplicationApproved::class]);

        $admin = User::factory()->create(['is_admin' => true]);
        $applicant = User::factory()->create(['email' => 'candidat@mami.ga']);

        $application = DriverApplication::factory()->create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'status' => DriverApplicationStatus::Pending,
            'driving_license_number' => 'GA-1111-AA',
            'plate_number' => 'GA-222-BB',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.driver-applications.approve', $application))
            ->assertRedirect(route('admin.driver-applications.show', $application));

        $application->refresh();
        $this->assertSame(DriverApplicationStatus::Approved, $application->status);
        $this->assertNotNull($application->reviewed_at);
        $this->assertSame($admin->id, $application->reviewed_by);

        $this->assertDatabaseHas('drivers', [
            'user_id' => $applicant->id,
            'license_number' => 'GA-1111-AA',
        ]);

        $driver = Driver::query()->where('user_id', $applicant->id)->first();
        $this->assertNotNull($driver);

        $this->assertDatabaseHas('vehicles', [
            'driver_id' => $driver->id,
            'plate_number' => 'GA-222-BB',
            'brand' => $application->vehicle_brand,
        ]);

        Notification::assertSentTo($applicant, DriverApplicationApprovedNotification::class);
        Event::assertDispatched(DriverApplicationApproved::class);
    }

    public function test_cannot_approve_already_reviewed_application(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $application = DriverApplication::factory()->approved()->create([
            'reviewed_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.driver-applications.approve', $application))
            ->assertSessionHasErrors('approve');
    }
}
