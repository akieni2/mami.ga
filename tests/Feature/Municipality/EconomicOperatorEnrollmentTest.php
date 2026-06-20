<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorCategory;
use Database\Seeders\EconomicOperatorCategorySeeder;
use Database\Seeders\EconomicZoneSeeder;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

class EconomicOperatorEnrollmentTest extends MunicipalityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EconomicOperatorCategorySeeder::class);
        $this->seed(EconomicZoneSeeder::class);
    }

    public function test_municipal_agent_can_enroll_operator_with_gps_and_facade(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $categoryId = EconomicOperatorCategory::query()->where('slug', 'boutique')->value('id');

        $response = $this->postJson('/api/municipality/operators', [
            'commercial_name' => 'Boutique SNI',
            'activity_label' => 'Alimentation générale',
            'category_id' => $categoryId,
            'responsible_name' => 'Jean Obame',
            'phone' => '+24106000001',
            'latitude' => 0.3380,
            'longitude' => 9.4710,
            'gps_accuracy_m' => 4.5,
            'location_confirmed' => true,
            'facade' => UploadedFile::fake()->image('facade.jpg'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.public_id', 'OWE-COM-00000001')
            ->assertJsonPath('data.quartier', 'Cité SNI')
            ->assertJsonPath('data.tax_status', 'a_jour');

        $this->assertDatabaseHas('economic_operators', [
            'public_id' => 'OWE-COM-00000001',
            'registered_by' => $agent->id,
            'sync_status' => 'synced',
        ]);
    }

    public function test_enrollment_rejected_when_gps_accuracy_too_low(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $categoryId = EconomicOperatorCategory::query()->where('slug', 'boutique')->value('id');

        $response = $this->postJson('/api/municipality/operators', [
            'commercial_name' => 'Boutique SNI',
            'activity_label' => 'Alimentation générale',
            'category_id' => $categoryId,
            'responsible_name' => 'Jean Obame',
            'phone' => '+24106000001',
            'latitude' => 0.3380,
            'longitude' => 9.4710,
            'gps_accuracy_m' => 25,
            'location_confirmed' => true,
            'facade' => UploadedFile::fake()->image('facade.jpg'),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['gps_accuracy_m']);
    }

    public function test_citizen_cannot_enroll_operator(): void
    {
        Sanctum::actingAs($this->citizenUser());

        $categoryId = EconomicOperatorCategory::query()->where('slug', 'boutique')->value('id');

        $response = $this->postJson('/api/municipality/operators', [
            'commercial_name' => 'Boutique SNI',
            'activity_label' => 'Alimentation générale',
            'category_id' => $categoryId,
            'responsible_name' => 'Jean Obame',
            'phone' => '+24106000001',
            'latitude' => 0.3380,
            'longitude' => 9.4710,
            'gps_accuracy_m' => 4,
            'location_confirmed' => true,
            'facade' => UploadedFile::fake()->image('facade.jpg'),
        ]);

        $response->assertForbidden();
    }

    public function test_operator_appears_on_map_layer(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

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

        $map = $this->getJson('/api/municipality/operators/map');
        $map->assertOk()
            ->assertJsonPath('data.layer', 'economic_operators')
            ->assertJsonCount(1, 'data.geojson.features');
    }

    public function test_dashboard_kpis_include_today_count(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

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
        ]);

        $response = $this->getJson('/api/municipality/operators/dashboard');
        $response->assertOk()
            ->assertJsonPath('data.registered_today', 1)
            ->assertJsonPath('data.total_operators', 1);
    }

    protected function municipalAgentUser(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', MamiRole::MunicipalAgent->value)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user->fresh('roles.permissions');
    }
}
