<?php

namespace Tests\Feature;

use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverApplicationApprovalRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_approval_assigns_taxi_driver_role(): void
    {
        $admin = \App\Models\User::factory()->create(['is_admin' => true]);
        $applicant = \App\Models\User::factory()->create(['email' => 'chauffeur@mami.ga']);

        $application = \App\Models\DriverApplication::factory()->create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'status' => \App\Enums\DriverApplicationStatus::Pending,
            'driving_license_number' => 'GA-3333-CC',
            'plate_number' => 'GA-444-DD',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.driver-applications.approve', $application))
            ->assertRedirect();

        $this->assertTrue($applicant->fresh()->hasRole(MamiRole::TaxiDriver->value));
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $applicant->id,
            'role_id' => Role::query()->where('slug', MamiRole::TaxiDriver->value)->value('id'),
        ]);
    }
}
