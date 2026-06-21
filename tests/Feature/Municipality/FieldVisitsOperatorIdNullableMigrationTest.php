<?php

namespace Tests\Feature\Municipality;

use App\Modules\Municipality\Enums\VisitType;
use App\Modules\Municipality\Models\FieldVisit;
use App\Modules\Municipality\Services\QRCodeManagement;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class FieldVisitsOperatorIdNullableMigrationTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
    }

    public function test_migration_makes_operator_id_nullable(): void
    {
        $column = collect(Schema::getColumns('field_visits'))
            ->firstWhere('name', 'operator_id');

        $this->assertNotNull($column);
        $this->assertTrue($column['nullable']);
    }

    public function test_migration_preserves_operator_id_foreign_key(): void
    {
        $foreignKeys = Schema::getForeignKeys('field_visits');

        $operatorFk = collect($foreignKeys)->first(
            fn (array $fk) => in_array('operator_id', $fk['columns'], true)
                && $fk['foreign_table'] === 'economic_operators'
        );

        $this->assertNotNull($operatorFk);
    }

    public function test_migration_preserves_operator_visit_date_index(): void
    {
        $indexes = Schema::getIndexes('field_visits');

        $hasCompositeIndex = collect($indexes)->contains(
            fn (array $index) => in_array('operator_id', $index['columns'], true)
                && in_array('visit_date', $index['columns'], true)
        );

        $this->assertTrue($hasCompositeIndex);
    }

    public function test_cash_session_open_persists_field_visit_without_operator(): void
    {
        $user = $this->fiscalManager();
        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/cash-sessions/open', [
            'opening_amount_xaf' => 10000,
            'latitude' => 0.3380,
            'longitude' => 9.4710,
        ])->assertCreated();

        $this->assertDatabaseHas('field_visits', [
            'agent_id' => $user->id,
            'visit_type' => VisitType::SessionOpen->value,
            'operator_id' => null,
        ]);
    }

    public function test_cash_session_close_persists_field_visit_without_operator(): void
    {
        $user = $this->fiscalManager();
        $session = $this->openCashSession($user);
        Sanctum::actingAs($user);

        $this->postJson("/api/municipality/fiscal/cash-sessions/{$session->id}/close", [
            'actual_amount_xaf' => 0,
        ])->assertOk();

        $this->assertDatabaseHas('field_visits', [
            'cash_session_id' => $session->id,
            'visit_type' => VisitType::SessionClose->value,
            'operator_id' => null,
        ]);
    }

    public function test_fiscal_collection_still_requires_operator_on_field_visit(): void
    {
        $user = $this->fiscalManager();
        $taxType = $this->createTaxType($user);
        $this->createTaxRate($user, $taxType);
        $operator = $this->createOperator($user);
        $this->assignTax($user, $operator, $taxType);
        $this->generateObligations($user);
        $session = $this->openCashSession($user);

        Sanctum::actingAs($user);

        $this->postJson('/api/municipality/fiscal/collections', $this->validCollectionPayload($operator, $session))
            ->assertCreated();

        $this->assertDatabaseHas('field_visits', [
            'operator_id' => $operator->id,
            'visit_type' => VisitType::Payment->value,
        ]);
    }

    public function test_commerce_field_visit_still_records_operator_id(): void
    {
        $agent = $this->municipalAgentUser();
        $operator = $this->createOperator($agent);
        Sanctum::actingAs($agent);

        $this->postJson('/api/municipality/operators/'.$operator->id.'/field-visits', [
            'visit_type' => 'inspection',
            'notes' => 'Contrôle',
        ])->assertCreated();

        $this->assertDatabaseHas('field_visits', [
            'operator_id' => $operator->id,
            'visit_type' => 'inspection',
        ]);
    }

    public function test_qr_scan_still_records_operator_on_field_visit(): void
    {
        $user = $this->fiscalManager();
        $operator = $this->createOperator($user);
        $qr = app(QRCodeManagement::class)->generateForOperator($operator);

        Sanctum::actingAs($user);

        $this->getJson('/api/municipality/operators/by-qr/'.$qr->qr_uuid)
            ->assertOk();

        $this->assertDatabaseHas('field_visits', [
            'operator_id' => $operator->id,
            'visit_type' => VisitType::Scan->value,
        ]);

        $this->assertSame(0, FieldVisit::query()->whereNull('operator_id')->count());
    }
}
