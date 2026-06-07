<?php

namespace Tests\Feature;

use App\Enums\DriverApplicationStatus;
use App\Events\DriverApplicationRejected;
use App\Models\DriverApplication;
use App\Models\User;
use App\Notifications\DriverApplicationRejectedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DriverApplicationRejectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reject_with_reason(): void
    {
        Notification::fake();
        Event::fake([DriverApplicationRejected::class]);

        $admin = User::factory()->create(['is_admin' => true]);
        $applicant = User::factory()->create();
        $application = DriverApplication::factory()->create([
            'user_id' => $applicant->id,
            'status' => DriverApplicationStatus::Pending,
        ]);

        $reason = 'Documents illisibles, merci de resoumettre des photos nettes.';

        $this->actingAs($admin)
            ->post(route('admin.driver-applications.reject', $application), [
                'rejection_reason' => $reason,
            ])
            ->assertRedirect(route('admin.driver-applications.show', $application));

        $application->refresh();
        $this->assertSame(DriverApplicationStatus::Rejected, $application->status);
        $this->assertSame($reason, $application->rejection_reason);
        $this->assertSame($admin->id, $application->reviewed_by);

        Notification::assertSentTo($applicant, DriverApplicationRejectedNotification::class);
        Event::assertDispatched(DriverApplicationRejected::class);
    }

    public function test_rejection_requires_reason(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $application = DriverApplication::factory()->create([
            'status' => DriverApplicationStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.driver-applications.reject', $application), [
                'rejection_reason' => 'court',
            ])
            ->assertSessionHasErrors('rejection_reason');
    }
}
