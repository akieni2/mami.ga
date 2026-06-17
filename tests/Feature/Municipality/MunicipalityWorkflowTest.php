<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Municipality\Models\MunicipalityReport;
use Illuminate\Support\Facades\Notification;
use App\Notifications\MunicipalityReportStatusNotification;
use Laravel\Sanctum\Sanctum;

class MunicipalityWorkflowTest extends MunicipalityTestCase
{
    public function test_full_workflow_assign_to_closed(): void
    {
        Notification::fake();

        $citizen = $this->citizenUser();
        $agent = $this->municipalAgent();
        $assignee = User::factory()->create(['is_admin' => true]);

        Sanctum::actingAs($citizen);
        $reportId = $this->postJson('/api/municipality/reports', $this->validReportPayload())
            ->assertCreated()
            ->json('data.id');

        Sanctum::actingAs($agent);

        $this->postJson("/api/municipality/reports/{$reportId}/assign", [
            'assigned_to' => $assignee->id,
            'notes' => 'Équipe voirie',
        ])->assertOk()->assertJsonPath('data.status', 'assigned');

        $this->postJson("/api/municipality/reports/{$reportId}/status", [
            'status' => 'in_progress',
            'notes' => 'Intervention démarrée',
        ])->assertOk()->assertJsonPath('data.status', 'in_progress');

        $this->postJson("/api/municipality/reports/{$reportId}/status", [
            'status' => 'resolved',
            'notes' => 'Réparé',
        ])->assertOk()->assertJsonPath('data.status', 'resolved');

        $this->postJson("/api/municipality/reports/{$reportId}/status", [
            'status' => 'closed',
        ])->assertOk()->assertJsonPath('data.status', 'closed');

        $this->assertDatabaseCount('municipality_report_updates', 5);

        Notification::assertSentTo($citizen, MunicipalityReportStatusNotification::class);
    }

    public function test_invalid_status_transition_rejected(): void
    {
        Sanctum::actingAs($this->municipalAgent());

        $report = MunicipalityReport::query()->create([
            'reference' => 'OWE-SIG-000050',
            'citizen_id' => $this->citizenUser()->id,
            'category' => 'voirie',
            'title' => 'Test',
            'description' => 'Desc',
            'latitude' => 0.338,
            'longitude' => 9.471,
            'territory_id' => $this->territoryId(),
            'status' => 'closed',
        ]);

        $this->postJson("/api/municipality/reports/{$report->id}/status", [
            'status' => 'in_progress',
        ])->assertStatus(422);
    }

    public function test_taxi_ride_request_still_works_with_municipality_enabled(): void
    {
        $client = $this->citizenUser();
        Sanctum::actingAs($client);

        $this->postJson('/api/rides/request', [
            'pickup_label' => 'Carrefour STFO',
            'destination_label' => 'Owendo SNI',
            'proposed_price' => 3000,
            'payment_method' => 'cash',
            'pickup_latitude' => 0.4160,
            'pickup_longitude' => 9.4670,
            'destination_latitude' => 0.4200,
            'destination_longitude' => 9.4800,
        ])->assertCreated();
    }
}
