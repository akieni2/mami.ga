<?php

namespace Tests\Feature\Municipality;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Municipality\Enums\ReceiptStatus;
use App\Modules\Municipality\Models\MunicipalReceipt;
use App\Modules\Municipality\Services\ReceiptCancellationService;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Municipality\Concerns\FiscalTestHelpers;

class ReceiptCancellationTest extends MunicipalityTestCase
{
    use FiscalTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEconomicRegistry();
    }

    public function test_supervisor_can_annul_valid_receipt(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);
        Sanctum::actingAs($agent);

        $this->postJson("/api/municipality/fiscal/receipts/{$receipt->id}/annul", [
            'reason' => 'Doublon constaté sur le terrain',
        ])->assertOk()
            ->assertJsonPath('data.status', 'annulled');

        $receipt->refresh();
        $this->assertSame(ReceiptStatus::Annulled, $receipt->status);
        $this->assertNotNull($receipt->annulled_at);
        $this->assertSame($agent->id, $receipt->annulled_by);
    }

    public function test_annulled_receipt_remains_in_database(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);

        app(ReceiptCancellationService::class)->annul($agent, $receipt, 'Erreur agent');

        $this->assertDatabaseHas('municipal_receipts', [
            'id' => $receipt->id,
            'status' => ReceiptStatus::Annulled->value,
        ]);
    }

    public function test_annul_creates_audit_log(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);

        app(ReceiptCancellationService::class)->annul($agent, $receipt, 'Test annulation');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'receipt.annulled',
            'subject_type' => 'municipal_receipt',
            'subject_id' => $receipt->id,
        ]);
    }

    public function test_cannot_annul_twice(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);

        app(ReceiptCancellationService::class)->annul($agent, $receipt, 'Première annulation');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ReceiptCancellationService::class)->annul($agent, $receipt, 'Seconde annulation');
    }

    public function test_refund_marks_receipt_as_refunded(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);

        app(ReceiptCancellationService::class)->refund($agent, $receipt, 'Remboursement caisse');

        $receipt->refresh();
        $this->assertSame(ReceiptStatus::Refunded, $receipt->status);
        $this->assertNotNull($receipt->refunded_at);
    }

    public function test_refund_creates_audit_log(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);

        app(ReceiptCancellationService::class)->refund($agent, $receipt, 'Remboursement');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'receipt.refunded',
            'subject_id' => $receipt->id,
        ]);
    }

    public function test_reprint_after_annul_still_allowed_for_history(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);
        app(ReceiptCancellationService::class)->annul($agent, $receipt, 'Annulée mais archivée');

        Sanctum::actingAs($agent);
        $this->postJson("/api/municipality/fiscal/receipts/{$receipt->id}/reprint")
            ->assertOk();

        $receipt->refresh();
        $this->assertSame(1, $receipt->reprint_count);
    }

    public function test_reprint_creates_audit_log(): void
    {
        $agent = $this->fiscalManager();
        $receipt = $this->createReceiptViaCollection($agent);
        Sanctum::actingAs($agent);

        $this->postJson("/api/municipality/fiscal/receipts/{$receipt->id}/reprint")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'receipt.reprinted',
            'subject_id' => $receipt->id,
        ]);
    }

    public function test_field_agent_cannot_annul_receipt(): void
    {
        $agent = $this->municipalAgentUser();
        $supervisor = $this->fiscalManager();
        $taxType = $this->createTaxType($supervisor);
        $this->createTaxRate($supervisor, $taxType);
        $operator = $this->createOperator($supervisor);
        $this->assignTax($supervisor, $operator, $taxType);
        $this->generateObligations($supervisor);
        $session = $this->openCashSession($agent);

        app(\App\Modules\Municipality\Services\FiscalCollectionService::class)
            ->collectCash($agent, $this->validCollectionPayload($operator, $session));

        $receipt = MunicipalReceipt::query()->firstOrFail();
        Sanctum::actingAs($agent);

        $this->postJson("/api/municipality/fiscal/receipts/{$receipt->id}/annul", [
            'reason' => 'Tentative non autorisée',
        ])->assertForbidden();
    }

    public function test_agent_can_list_own_receipts(): void
    {
        $agent = $this->municipalAgentUser();
        $supervisor = $this->fiscalManager();
        $taxType = $this->createTaxType($supervisor);
        $this->createTaxRate($supervisor, $taxType);
        $operator = $this->createOperator($supervisor);
        $this->assignTax($supervisor, $operator, $taxType);
        $this->generateObligations($supervisor);
        $session = $this->openCashSession($agent);

        app(\App\Modules\Municipality\Services\FiscalCollectionService::class)
            ->collectCash($agent, $this->validCollectionPayload($operator, $session));

        Sanctum::actingAs($agent);
        $this->getJson('/api/municipality/fiscal/receipts')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    private function createReceiptViaCollection(?\App\Models\User $agent = null): MunicipalReceipt
    {
        $agent ??= $this->fiscalManager();
        $taxType = $this->createTaxType($agent);
        $this->createTaxRate($agent, $taxType);
        $operator = $this->createOperator($agent);
        $this->assignTax($agent, $operator, $taxType);
        $this->generateObligations($agent);
        $session = $this->openCashSession($agent);

        app(\App\Modules\Municipality\Services\FiscalCollectionService::class)
            ->collectCash($agent, $this->validCollectionPayload($operator, $session));

        return MunicipalReceipt::query()->firstOrFail();
    }
}
