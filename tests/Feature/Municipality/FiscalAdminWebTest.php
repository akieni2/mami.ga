<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FiscalAdminWebTest extends MunicipalityTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config(['mami.modules.municipality' => true]);
    }

    public function test_admin_can_create_tax_type_via_web_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('admin.municipality.fiscal.tax-types.store'), [
                'code' => 'TAX-WEB-TEST',
                'name' => 'Taxe test web',
                'description' => 'Créée via formulaire admin',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('municipal_tax_types', [
            'code' => 'TAX-WEB-TEST',
            'name' => 'Taxe test web',
        ]);
    }

    public function test_invalid_tax_code_shows_validation_errors(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.municipality.fiscal.tax-types'))
            ->post(route('admin.municipality.fiscal.tax-types.store'), [
                'code' => 'taxe invalide',
                'name' => 'Test',
            ])
            ->assertRedirect(route('admin.municipality.fiscal.tax-types'))
            ->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('municipal_tax_types', ['name' => 'Test']);
    }
}
