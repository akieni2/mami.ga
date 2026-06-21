<?php

namespace Tests\Feature\Municipality;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use App\Modules\Municipality\Enums\PaymentMethod;
use App\Modules\Municipality\Enums\PaymentStatus;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorCategory;
use App\Modules\Municipality\Models\FieldVisit;
use App\Modules\Municipality\Models\MunicipalPayment;
use App\Modules\Municipality\Models\MunicipalReceipt;
use App\Modules\Municipality\Services\MunicipalReceiptReferenceGenerator;
use Database\Seeders\EconomicOperatorCategorySeeder;
use Database\Seeders\EconomicZoneSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

class EconomicOperatorIntegrityTest extends MunicipalityTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EconomicOperatorCategorySeeder::class);
        $this->seed(EconomicZoneSeeder::class);
    }

    public function test_schema_has_required_unique_constraints(): void
    {
        $this->assertTrue($this->hasUniqueIndex('economic_operator_qrcodes', 'qr_uuid'));
        $this->assertTrue($this->hasUniqueIndex('municipal_receipts', 'receipt_number'));
    }

    public function test_schema_has_required_foreign_keys(): void
    {
        $this->assertTrue($this->hasForeignKey('municipal_payments', 'operator_id', 'economic_operators'));
        $this->assertTrue($this->hasForeignKey('field_visits', 'operator_id', 'economic_operators'));
        $this->assertTrue($this->hasForeignKey('field_visits', 'agent_id', 'users'));
    }

    public function test_field_visits_operator_id_column_is_nullable(): void
    {
        $column = collect(Schema::getColumns('field_visits'))
            ->firstWhere('name', 'operator_id');

        $this->assertNotNull($column);
        $this->assertTrue($column['nullable'], 'field_visits.operator_id must be nullable for session_open/session_close visits.');
    }

    public function test_soft_delete_preserves_payments_visits_and_receipts(): void
    {
        $agent = $this->municipalAgentUser();
        $operator = $this->createOperator($agent);

        FieldVisit::query()->create([
            'operator_id' => $operator->id,
            'agent_id' => $agent->id,
            'visit_type' => 'inspection',
            'visit_date' => now()->toDateString(),
        ]);

        $payment = MunicipalPayment::query()->create([
            'operator_id' => $operator->id,
            'agent_id' => $agent->id,
            'amount' => 15000,
            'payment_method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Completed,
        ]);

        $generator = app(MunicipalReceiptReferenceGenerator::class);
        $receiptNumber = $generator->next((int) now()->format('Y'));
        MunicipalReceipt::query()->create([
            'payment_id' => $payment->id,
            'receipt_number' => $receiptNumber,
            'receipt_qr_value' => $generator->buildReceiptQrValue($receiptNumber),
            'generated_at' => now(),
        ]);

        $operator->delete();

        $this->assertSoftDeleted('economic_operators', ['id' => $operator->id]);
        $this->assertDatabaseHas('field_visits', ['operator_id' => $operator->id]);
        $this->assertDatabaseHas('municipal_payments', ['operator_id' => $operator->id]);
        $this->assertDatabaseHas('municipal_receipts', ['payment_id' => $payment->id]);
        $this->assertDatabaseHas('economic_operator_qrcodes', ['operator_id' => $operator->id]);
    }

    public function test_hard_delete_is_blocked_when_dependent_records_exist(): void
    {
        $agent = $this->municipalAgentUser();
        $operator = $this->createOperator($agent);

        FieldVisit::query()->create([
            'operator_id' => $operator->id,
            'agent_id' => $agent->id,
            'visit_type' => 'inspection',
            'visit_date' => now()->toDateString(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $operator->forceDelete();
    }

    private function hasUniqueIndex(string $table, string $column): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $index) {
            if ($index['unique'] && in_array($column, $index['columns'], true)) {
                return true;
            }
        }

        return false;
    }

    private function hasForeignKey(string $table, string $column, string $referencedTable): bool
    {
        $foreignKeys = Schema::getForeignKeys($table);

        foreach ($foreignKeys as $foreignKey) {
            if (in_array($column, $foreignKey['columns'], true)
                && $foreignKey['foreign_table'] === $referencedTable) {
                return true;
            }
        }

        return false;
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
