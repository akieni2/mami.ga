<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorCategory;
use App\Modules\Municipality\Models\EconomicOperatorQrcode;
use App\Modules\Municipality\Services\QRCodeManagement;
use Database\Seeders\EconomicOperatorCategorySeeder;
use Database\Seeders\EconomicZoneSeeder;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

class QRCodeManagementTest extends MunicipalityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EconomicOperatorCategorySeeder::class);
        $this->seed(EconomicZoneSeeder::class);
    }

    public function test_enrollment_generates_secure_uuid_scan_token(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $categoryId = EconomicOperatorCategory::query()->where('slug', 'boutique')->value('id');

        $response = $this->postJson('/api/municipality/operators', [
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

        $response->assertCreated()
            ->assertJsonPath('data.qr_code.display_id', 'OWE-COM-000001')
            ->assertJsonPath('data.qr_code.display_label', 'OWE-COM-000001');

        $scanToken = $response->json('data.qr_code.scan_token');
        $this->assertIsString($scanToken);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $scanToken,
        );

        $this->assertDatabaseHas('economic_operator_qrcodes', [
            'qr_uuid' => $scanToken,
            'qr_value' => 'OWE-COM-000001',
            'is_active' => true,
        ]);
    }

    public function test_agent_can_lookup_operator_by_uuid_scan_token(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $operator = $this->createOperator($agent);
        $scanToken = $operator->activeQrcode->qr_uuid;

        $this->getJson('/api/municipality/operators/by-qr/'.$scanToken)
            ->assertOk()
            ->assertJsonPath('data.qr.scan_token', $scanToken)
            ->assertJsonPath('data.qr.display_id', 'OWE-COM-000001')
            ->assertJsonPath('data.operator.public_id', 'OWE-COM-000001')
            ->assertJsonPath('data.territory.quartier', 'Cité SNI')
            ->assertJsonPath('data.tax_status.current', 'a_jour');
    }

    public function test_qr_lookup_accepts_composite_suffix_format(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $operator = $this->createOperator($agent);
        $qrcode = $operator->activeQrcode;
        $composite = app(QRCodeManagement::class)->buildDisplayLabelWithSuffix($qrcode);

        $this->getJson('/api/municipality/operators/by-qr/'.$composite)
            ->assertOk()
            ->assertJsonPath('data.operator.public_id', 'OWE-COM-000001');
    }

    public function test_qr_lookup_rejects_predictable_public_id_only(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $this->createOperator($agent);

        $this->getJson('/api/municipality/operators/by-qr/OWE-COM-000001')
            ->assertNotFound();

        $this->getJson('/api/municipality/operators/by-qr/QR-OWE-COM-000001')
            ->assertNotFound();
    }

    public function test_qr_png_encodes_uuid_not_public_id(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $operator = $this->createOperator($agent);
        $qrcode = $operator->activeQrcode;
        $management = app(QRCodeManagement::class);

        $png = $management->buildPngContent($qrcode);

        $this->assertNotEmpty($png);
        $this->assertSame($qrcode->qr_uuid, $management->scanPayload($qrcode));
        $this->assertNotSame('OWE-COM-000001', $management->scanPayload($qrcode));
    }

    public function test_qr_png_download_returns_image(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $operator = $this->createOperator($agent);

        $response = $this->get('/api/municipality/operators/'.$operator->id.'/qrcode/png');

        $response->assertOk();
        $this->assertTrue(
            str_starts_with($response->headers->get('Content-Type'), 'image/'),
        );
    }

    public function test_business_card_preview_returns_display_and_scan_token(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $operator = $this->createOperator($agent);

        $this->getJson('/api/municipality/operators/'.$operator->id.'/business-card')
            ->assertOk()
            ->assertJsonPath('data.public_id', 'OWE-COM-000001')
            ->assertJsonPath('data.display_id', 'OWE-COM-000001')
            ->assertJsonPath('data.scan_token', $operator->activeQrcode->qr_uuid)
            ->assertJsonPath('data.pdf_ready', false);
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

        return EconomicOperator::query()
            ->with('activeQrcode')
            ->firstOrFail();
    }

    private function municipalAgentUser(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('slug', MamiRole::MunicipalAgent->value)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);

        return $user;
    }
}
