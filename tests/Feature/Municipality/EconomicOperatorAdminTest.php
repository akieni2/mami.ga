<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorCategory;
use App\Modules\Municipality\Services\EconomicOperatorReferenceGenerator;
use Database\Seeders\EconomicOperatorCategorySeeder;
use Database\Seeders\EconomicZoneSeeder;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

class EconomicOperatorAdminTest extends MunicipalityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EconomicOperatorCategorySeeder::class);
        $this->seed(EconomicZoneSeeder::class);
    }

    public function test_admin_can_view_operators_index(): void
    {
        $admin = $this->adminUser();
        $this->createOperatorViaApi();

        $this->actingAs($admin)
            ->get(route('admin.municipality.operators.index'))
            ->assertOk()
            ->assertSee('OWE-COM-00000001')
            ->assertSee('Boutique SNI');
    }

    public function test_municipal_supervisor_can_view_operators_index(): void
    {
        $supervisor = $this->supervisorUser();
        $this->createOperatorViaApi();

        $this->actingAs($supervisor)
            ->get(route('admin.municipality.operators.index'))
            ->assertOk()
            ->assertSee('Opérateurs économiques');
    }

    public function test_municipal_agent_cannot_access_admin_operators(): void
    {
        $agent = $this->municipalAgentUser();

        $this->actingAs($agent)
            ->get(route('admin.municipality.operators.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_operator_show_and_download_qr_png(): void
    {
        $admin = $this->adminUser();
        $operator = $this->createOperatorViaApi();

        $this->actingAs($admin)
            ->get(route('admin.municipality.operators.show', $operator))
            ->assertOk()
            ->assertSee('Informations générales')
            ->assertSee('Fiscalité');

        $response = $this->actingAs($admin)
            ->get(route('admin.municipality.operators.qr.png', $operator));

        $response->assertOk();
        $this->assertTrue(str_starts_with($response->headers->get('Content-Type'), 'image/'));
    }

    public function test_reference_generator_uses_eight_digits(): void
    {
        $this->assertSame('OWE-COM-00000001', app(EconomicOperatorReferenceGenerator::class)->next());
        $this->assertSame('OWE-COM-00000002', app(EconomicOperatorReferenceGenerator::class)->next());
    }

    public function test_admin_can_export_csv(): void
    {
        $admin = $this->adminUser();
        $this->createOperatorViaApi();

        $response = $this->actingAs($admin)
            ->get(route('admin.municipality.operators.export.csv'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('OWE-COM-00000001', $response->streamedContent());
    }

    public function test_qr_batch_rejects_ranges_over_limit(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post(route('admin.municipality.operators.qr-batch.generate'), [
                'start' => 1,
                'end' => 20000,
            ])
            ->assertSessionHasErrors('end');
    }

    private function adminUser(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $role = Role::query()->where('slug', MamiRole::Admin->value)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user;
    }

    private function supervisorUser(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', MamiRole::MunicipalSupervisor->value)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user;
    }

    private function createOperatorViaApi(): EconomicOperator
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

        return EconomicOperator::query()->firstOrFail();
    }
}
