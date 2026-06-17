<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorCategory;
use App\Modules\Municipality\Models\FieldVisit;
use Database\Seeders\EconomicOperatorCategorySeeder;
use Database\Seeders\EconomicZoneSeeder;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

class FieldVisitTest extends MunicipalityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EconomicOperatorCategorySeeder::class);
        $this->seed(EconomicZoneSeeder::class);
    }

    public function test_agent_can_record_field_visit(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $operator = $this->createOperator($agent);

        $response = $this->postJson('/api/municipality/operators/'.$operator->id.'/field-visits', [
            'visit_type' => 'inspection',
            'notes' => 'Contrôle de conformité',
            'latitude' => 0.3381,
            'longitude' => 9.4711,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.visit_type', 'inspection');

        $this->assertDatabaseHas('field_visits', [
            'operator_id' => $operator->id,
            'agent_id' => $agent->id,
            'visit_type' => 'inspection',
        ]);

        $operator->refresh();
        $this->assertNotNull($operator->last_visit_at);
    }

    public function test_field_visit_appears_in_qr_scan_history(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $operator = $this->createOperator($agent);

        $this->postJson('/api/municipality/operators/'.$operator->id.'/field-visits', [
            'visit_type' => 'verification',
            'notes' => 'Vérification documents',
        ])->assertCreated();

        $qrcode = $operator->activeQrcode;

        $this->getJson('/api/municipality/operators/by-qr/'.$qrcode->qr_uuid)
            ->assertOk()
            ->assertJsonCount(1, 'data.field_visits')
            ->assertJsonPath('data.field_visits.0.visit_type', 'verification');
    }

    public function test_dashboard_counts_field_visits(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $operator = $this->createOperator($agent);

        FieldVisit::query()->create([
            'operator_id' => $operator->id,
            'agent_id' => $agent->id,
            'visit_type' => 'awareness',
            'visit_date' => now()->toDateString(),
        ]);

        $this->getJson('/api/municipality/operators/dashboard')
            ->assertOk()
            ->assertJsonPath('data.v3_preparatory.field_visits_total', 1);
    }

    private function createOperator(User $agent): EconomicOperator
    {
        $categoryId = EconomicOperatorCategory::query()->where('slug', 'boutique')->value('id');

        $this->postJson('/api/municipality/operators', [
            'commercial_name' => 'Boutique SNI',
            'activity_label' => 'Alimentation',
            'category_id' => $categoryId,
            'responsible_name' => 'Jean Obame',
            'phone' => '+24106000001',
            'latitude' => 0.3380,
            'longitude' => 9.4710,
            'gps_accuracy_m' => 4,
            'location_confirmed' => true,
            'facade' => UploadedFile::fake()->image('facade.jpg'),
        ])->assertCreated();

        return EconomicOperator::query()->firstOrFail();
    }

    private function municipalAgentUser(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', MamiRole::MunicipalAgent->value)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user;
    }
}
