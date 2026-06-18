<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use App\Modules\Municipality\Enums\PaymentMethod;
use App\Modules\Municipality\Enums\PaymentStatus;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorCategory;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\MunicipalReceipt;
use App\Modules\Municipality\Services\MunicipalReceiptReferenceGenerator;
use Database\Seeders\EconomicOperatorCategorySeeder;
use Database\Seeders\EconomicZoneSeeder;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

class MunicipalReceiptTest extends MunicipalityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EconomicOperatorCategorySeeder::class);
        $this->seed(EconomicZoneSeeder::class);
    }

    public function test_receipt_reference_generator_produces_expected_format(): void
    {
        $generator = app(MunicipalReceiptReferenceGenerator::class);

        $this->assertSame('OWE-RCP-2026-000001', $generator->next(2026));
        $this->assertSame('QR-OWE-RCP-2026-000001', $generator->buildReceiptQrValue('OWE-RCP-2026-000001'));
    }

    public function test_receipt_reference_increments_after_persist(): void
    {
        $generator = app(MunicipalReceiptReferenceGenerator::class);
        $agent = $this->municipalAgentUser();
        $operator = $this->createOperator($agent);

        $payment = MunicipalPayment::query()->create([
            'operator_id' => $operator->id,
            'agent_id' => $agent->id,
            'amount' => 1000,
            'payment_method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Pending,
        ]);

        $firstNumber = $generator->next(2026);
        MunicipalReceipt::query()->create([
            'payment_id' => $payment->id,
            'receipt_number' => $firstNumber,
            'receipt_qr_value' => $generator->buildReceiptQrValue($firstNumber),
            'generated_at' => now(),
        ]);

        $this->assertSame('OWE-RCP-2026-000002', $generator->next(2026));
    }

    public function test_municipal_receipt_table_accepts_foundation_record(): void
    {
        $agent = $this->municipalAgentUser();
        $operator = $this->createOperator($agent);

        $payment = MunicipalPayment::query()->create([
            'operator_id' => $operator->id,
            'agent_id' => $agent->id,
            'amount' => 25000,
            'payment_method' => PaymentMethod::Cash,
            'payment_period' => '2026-06',
            'status' => PaymentStatus::Completed,
        ]);

        $generator = app(MunicipalReceiptReferenceGenerator::class);
        $receiptNumber = $generator->next(2026);

        $receipt = MunicipalReceipt::query()->create([
            'payment_id' => $payment->id,
            'receipt_number' => $receiptNumber,
            'receipt_qr_value' => $generator->buildReceiptQrValue($receiptNumber),
            'generated_at' => now(),
        ]);

        $this->assertDatabaseHas('municipal_receipts', [
            'id' => $receipt->id,
            'receipt_number' => 'OWE-RCP-2026-000001',
        ]);

        $this->assertSame($payment->id, $receipt->payment->id);
    }

    public function test_dashboard_exposes_receipt_placeholder_kpi(): void
    {
        $agent = $this->municipalAgentUser();
        Sanctum::actingAs($agent);

        $this->getJson('/api/municipality/operators/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'v3_preparatory' => [
                        'receipts_today' => ['value', 'placeholder', 'note'],
                        'amounts_collected' => ['value', 'placeholder', 'note'],
                    ],
                ],
            ]);
    }

    private function createOperator(User $agent): EconomicOperator
    {
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
